<?php

namespace App\Http\Controllers;

use App\Models\UniformItem;
use App\Models\UniformItemVariant;
use App\Models\UniformOrder;
use App\Models\UniformOrderItem;
use App\Models\UniformSale;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Barryvdh\DomPDF\Facade\Pdf;

class UniformController extends Controller
{
    // ============================================
    // STOCK MANAGEMENT
    // ============================================

    /**
     * Get all uniform items with their variants
     */
    public function getItems(Request $request)
    {
        try {
            $query = UniformItem::with(['variants']);

            // Filter by category
            if ($request->has('category') && $request->category !== 'all') {
                $query->byCategory($request->category);
            }

            // Filter by active status
            if ($request->has('active_only') && $request->active_only) {
                $query->active();
            }

            $items = $query->orderBy('name')->get();

            // Add computed fields
            $items = $items->map(function ($item) {
                $item->total_stock = $item->getTotalStockAttribute();
                $item->total_available = $item->getTotalAvailableStockAttribute();
                $item->needs_restock = $item->needsRestock();
                return $item;
            });

            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des articles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create new uniform item
     */
    public function createItem(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'code' => 'required|string|max:20|unique:uniform_items,code',
                'description' => 'nullable|string',
                'base_price' => 'required|numeric|min:0',
                'category' => 'nullable|string',
                'reorder_level' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item = UniformItem::create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Article créé avec succès',
                'data' => $item
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'article',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update uniform item
     */
    public function updateItem(Request $request, $id)
    {
        try {
            $item = UniformItem::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'code' => 'sometimes|string|max:20|unique:uniform_items,code,' . $id,
                'description' => 'nullable|string',
                'base_price' => 'sometimes|numeric|min:0',
                'category' => 'nullable|string',
                'is_active' => 'sometimes|boolean',
                'reorder_level' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $item->update($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Article mis à jour avec succès',
                'data' => $item
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Delete uniform item
     */
    public function deleteItem($id)
    {
        try {
            $item = UniformItem::findOrFail($id);

            // Check if item has orders
            $hasOrders = UniformOrderItem::where('uniform_item_id', $id)->exists();
            if ($hasOrders) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer un article avec des commandes existantes'
                ], 400);
            }

            $item->delete();

            return response()->json([
                'success' => true,
                'message' => 'Article supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create variant for an item
     */
    public function createVariant(Request $request, $itemId)
    {
        try {
            $item = UniformItem::findOrFail($itemId);

            $validator = Validator::make($request->all(), [
                'size' => 'required|string',
                'price_adjustment' => 'nullable|numeric',
                'sku' => 'required|string|max:50|unique:uniform_item_variants,sku',
                'stock_quantity' => 'nullable|integer|min:0'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            $variant = $item->variants()->create($request->all());

            return response()->json([
                'success' => true,
                'message' => 'Variante créée avec succès',
                'data' => $variant
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la variante',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update variant stock
     */
    public function updateVariantStock(Request $request, $variantId)
    {
        try {
            $variant = UniformItemVariant::findOrFail($variantId);

            $validator = Validator::make($request->all(), [
                'quantity' => 'required|integer',
                'operation' => 'required|in:add,set'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            if ($request->operation === 'add') {
                $variant->addStock($request->quantity);
            } else {
                $variant->stock_quantity = $request->quantity;
                $variant->save();
            }

            return response()->json([
                'success' => true,
                'message' => 'Stock mis à jour avec succès',
                'data' => $variant->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du stock',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get low stock items
     */
    public function getLowStockItems()
    {
        try {
            $items = UniformItem::with('variants')
                ->get()
                ->filter(function ($item) {
                    return $item->needsRestock();
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $items
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // ORDER MANAGEMENT
    // ============================================

    /**
     * Place a new order
     */
    public function placeOrder(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'student_id' => 'required|exists:students,id',
                'items' => 'required|array|min:1',
                'items.*.variant_id' => 'required|exists:uniform_item_variants,id',
                'items.*.quantity' => 'required|integer|min:1',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            // Create order
            $order = UniformOrder::create([
                'order_number' => UniformOrder::generateOrderNumber(),
                'student_id' => $request->student_id,
                'status' => 'pending',
                'notes' => $request->notes,
                'placed_by' => Auth::id(),
                'placed_at' => now()
            ]);

            $totalAmount = 0;

            // Add order items and reserve stock
            foreach ($request->items as $item) {
                $variant = UniformItemVariant::findOrFail($item['variant_id']);

                // Check available stock
                if (!$variant->hasAvailableStock($item['quantity'])) {
                    DB::rollBack();
                    return response()->json([
                        'success' => false,
                        'message' => "Stock insuffisant pour {$variant->uniformItem->name} ({$variant->size})"
                    ], 400);
                }

                // Reserve stock
                $variant->reserve($item['quantity']);

                // Create order item
                $unitPrice = $variant->getFinalPriceAttribute();
                $subtotal = $item['quantity'] * $unitPrice;

                UniformOrderItem::create([
                    'uniform_order_id' => $order->id,
                    'uniform_item_id' => $variant->uniform_item_id,
                    'uniform_item_variant_id' => $variant->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal
                ]);

                $totalAmount += $subtotal;
            }

            // Update order total
            $order->total_amount = $totalAmount;
            $order->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Commande passée avec succès',
                'data' => $order->load(['items.uniformItem', 'items.variant', 'student'])
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get orders with filters
     */
    public function getOrders(Request $request)
    {
        try {
            $query = UniformOrder::with([
                'student',
                'items.uniformItem',
                'items.variant',
                'placedBy',
                'readyBy',
                'fulfilledBy'
            ]);

            // Filter by status
            if ($request->has('status') && $request->status !== 'all') {
                $query->where('status', $request->status);
            }

            // Filter by student
            if ($request->has('student_id')) {
                $query->where('student_id', $request->student_id);
            }

            // Filter by date range
            if ($request->has('start_date') && $request->has('end_date')) {
                $query->whereBetween('placed_at', [$request->start_date, $request->end_date]);
            }

            $orders = $query->orderBy('created_at', 'desc')->get();

            return response()->json([
                'success' => true,
                'data' => $orders
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement des commandes',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get single order details
     */
    public function getOrderDetails($orderId)
    {
        try {
            $order = UniformOrder::with([
                'student',
                'items.uniformItem',
                'items.variant',
                'placedBy',
                'readyBy',
                'fulfilledBy',
                'sale'
            ])->findOrFail($orderId);

            return response()->json([
                'success' => true,
                'data' => $order
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark order as ready for pickup
     */
    public function markOrderReady(Request $request, $orderId)
    {
        try {
            $order = UniformOrder::findOrFail($orderId);

            if ($order->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'Seules les commandes en attente peuvent être marquées comme prêtes'
                ], 400);
            }

            $order->markAsReady(Auth::id());
            $order->notifyStudent(); // Mark as notified

            return response()->json([
                'success' => true,
                'message' => 'Commande marquée comme prête pour retrait',
                'data' => $order->fresh()
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Cancel order
     */
    public function cancelOrder($orderId)
    {
        try {
            $order = UniformOrder::findOrFail($orderId);

            if ($order->status === 'fulfilled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible d\'annuler une commande déjà retirée'
                ], 400);
            }

            $order->cancel();

            return response()->json([
                'success' => true,
                'message' => 'Commande annulée avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // ============================================
    // PICKUP & PAYMENT
    // ============================================

    /**
     * Process order pickup and payment
     */
    public function processPickup(Request $request, $orderId)
    {
        try {
            $validator = Validator::make($request->all(), [
                'payment_method' => 'required|in:cash,bank_transfer,mobile_money,check,other',
                'payment_reference' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur de validation',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $order = UniformOrder::with(['items.variant', 'student'])->findOrFail($orderId);

            if ($order->status === 'fulfilled') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande a déjà été retirée'
                ], 400);
            }

            if ($order->status === 'cancelled') {
                DB::rollBack();
                return response()->json([
                    'success' => false,
                    'message' => 'Cette commande a été annulée'
                ], 400);
            }

            // Mark order as fulfilled
            $order->markAsFulfilled(Auth::id());

            // Create sale record
            $sale = UniformSale::create([
                'uniform_order_id' => $order->id,
                'receipt_number' => UniformSale::generateReceiptNumber(),
                'student_id' => $order->student_id,
                'total_amount' => $order->total_amount,
                'payment_method' => $request->payment_method,
                'payment_reference' => $request->payment_reference,
                'processed_by' => Auth::id(),
                'sale_date' => now(),
                'notes' => $request->notes
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Retrait traité avec succès',
                'data' => [
                    'order' => $order->fresh(),
                    'sale' => $sale->load(['student', 'processedBy'])
                ]
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download receipt PDF
     */
    public function downloadReceipt($saleId)
    {
        try {
            $sale = UniformSale::with([
                'order.items.uniformItem',
                'order.items.variant',
                'student',
                'processedBy'
            ])->findOrFail($saleId);

            $html = $this->generateReceiptHtml($sale);

            $pdf = Pdf::loadHTML($html);
            $pdf->setPaper('A4', 'portrait');

            return $pdf->download("recu_uniforme_{$sale->receipt_number}.pdf");
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du reçu',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Generate receipt HTML
     */
    private function generateReceiptHtml($sale)
    {
        $schoolSetting = \App\Models\SchoolSetting::first();
        $schoolName = $schoolSetting ? $schoolSetting->school_name : 'Établissement Scolaire';

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; font-size: 12px; }
                .header { text-align: center; margin-bottom: 30px; }
                .header h1 { margin: 0; color: #333; font-size: 24px; }
                .header h2 { margin: 5px 0; color: #666; font-size: 18px; }
                .info-box { border: 2px solid #333; padding: 15px; margin-bottom: 20px; }
                .info-row { margin: 5px 0; }
                .info-label { font-weight: bold; display: inline-block; width: 150px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; font-weight: bold; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .footer { margin-top: 40px; text-align: center; font-size: 10px; color: #666; }
            </style>
        </head>
        <body>
            <div class="header">
                <h1>' . htmlspecialchars($schoolName) . '</h1>
                <h2>Reçu de Vente - Uniformes Scolaires</h2>
            </div>

            <div class="info-box">
                <div class="info-row">
                    <span class="info-label">N° Reçu:</span>
                    <span>' . htmlspecialchars($sale->receipt_number) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">N° Commande:</span>
                    <span>' . htmlspecialchars($sale->order->order_number) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Date:</span>
                    <span>' . $sale->sale_date->format('d/m/Y H:i') . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Étudiant:</span>
                    <span>' . htmlspecialchars($sale->student->first_name . ' ' . $sale->student->last_name) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Matricule:</span>
                    <span>' . htmlspecialchars($sale->student->matricule) . '</span>
                </div>
                <div class="info-row">
                    <span class="info-label">Mode de paiement:</span>
                    <span>' . strtoupper($sale->payment_method) . '</span>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Article</th>
                        <th>Taille</th>
                        <th>Quantité</th>
                        <th>Prix Unitaire</th>
                        <th>Sous-total</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($sale->order->items as $item) {
            $html .= '
                    <tr>
                        <td>' . htmlspecialchars($item->uniformItem->name) . '</td>
                        <td>' . htmlspecialchars($item->variant->size) . '</td>
                        <td>' . $item->quantity . '</td>
                        <td>' . number_format($item->unit_price, 0, ',', ' ') . ' FCFA</td>
                        <td>' . number_format($item->subtotal, 0, ',', ' ') . ' FCFA</td>
                    </tr>';
        }

        $html .= '
                    <tr class="total-row">
                        <td colspan="4" style="text-align: right;">TOTAL</td>
                        <td>' . number_format($sale->total_amount, 0, ',', ' ') . ' FCFA</td>
                    </tr>
                </tbody>
            </table>

            <div class="footer">
                <p>Traité par: ' . htmlspecialchars($sale->processedBy->name) . '</p>
                <p>Merci pour votre achat!</p>
            </div>
        </body>
        </html>';

        return $html;
    }

    // ============================================
    // REPORTS & STATISTICS
    // ============================================

    /**
     * Get sales report
     */
    public function getSalesReport(Request $request)
    {
        try {
            $startDate = $request->input('start_date', now()->startOfMonth());
            $endDate = $request->input('end_date', now()->endOfMonth());

            $sales = UniformSale::with(['student', 'order.items.uniformItem'])
                ->forDateRange($startDate, $endDate)
                ->get();

            $totalSales = $sales->count();
            $totalRevenue = $sales->sum('total_amount');

            // Sales by payment method
            $byPaymentMethod = $sales->groupBy('payment_method')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total' => $group->sum('total_amount')
                ];
            });

            // Most sold items
            $itemsSold = [];
            foreach ($sales as $sale) {
                foreach ($sale->order->items as $item) {
                    $itemName = $item->uniformItem->name;
                    if (!isset($itemsSold[$itemName])) {
                        $itemsSold[$itemName] = [
                            'name' => $itemName,
                            'quantity' => 0,
                            'revenue' => 0
                        ];
                    }
                    $itemsSold[$itemName]['quantity'] += $item->quantity;
                    $itemsSold[$itemName]['revenue'] += $item->subtotal;
                }
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'period' => [
                        'start' => $startDate,
                        'end' => $endDate
                    ],
                    'summary' => [
                        'total_sales' => $totalSales,
                        'total_revenue' => $totalRevenue
                    ],
                    'by_payment_method' => $byPaymentMethod,
                    'items_sold' => array_values($itemsSold),
                    'recent_sales' => $sales->take(10)
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération du rapport',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get pending orders summary
     */
    public function getPendingOrdersSummary()
    {
        try {
            $pendingOrders = UniformOrder::pending()
                ->with(['student', 'items.uniformItem'])
                ->get();

            $readyOrders = UniformOrder::ready()
                ->with(['student', 'items.uniformItem'])
                ->get();

            return response()->json([
                'success' => true,
                'data' => [
                    'pending_count' => $pendingOrders->count(),
                    'ready_count' => $readyOrders->count(),
                    'pending_value' => $pendingOrders->sum('total_amount'),
                    'ready_value' => $readyOrders->sum('total_amount'),
                    'pending_orders' => $pendingOrders,
                    'ready_orders' => $readyOrders
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du chargement',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
