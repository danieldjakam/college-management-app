<?php

namespace App\Http\Controllers;

use App\Models\CardLayoutSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CardLayoutSettingController extends Controller
{
    /**
     * Récupérer tous les paramètres de mise en page
     */
    public function index()
    {
        try {
            $settings = CardLayoutSetting::getAllSettings();
            
            return response()->json([
                'success' => true,
                'data' => $settings
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur récupération paramètres carte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des paramètres'
            ], 500);
        }
    }

    /**
     * Mettre à jour les paramètres de mise en page
     */
    public function update(Request $request)
    {
        try {
            $request->validate([
                'settings' => 'required|array',
                'settings.*.key' => 'required|string',
                'settings.*.value' => 'required|string',
            ]);

            foreach ($request->settings as $setting) {
                CardLayoutSetting::updateSetting($setting['key'], $setting['value']);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paramètres mis à jour avec succès'
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour paramètres carte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Réinitialiser aux valeurs par défaut
     */
    public function reset()
    {
        try {
            $defaults = [
                'photo_top' => '11',
                'photo_right' => '8',
                'photo_width' => '24',
                'photo_height' => '24',
                'qr_bottom' => '4',
                'qr_left_offset' => '0',
                'qr_width' => '14',
                'qr_height' => '14',
                'info_top' => '20.5',
                'info_left' => '33',
                'info_font_size' => '4.5',
                'info_line_height' => '3.8',
                'info_max_width' => '40',
                'info_letter_spacing' => '0',
            ];

            foreach ($defaults as $key => $value) {
                CardLayoutSetting::updateSetting($key, $value);
            }

            return response()->json([
                'success' => true,
                'message' => 'Paramètres réinitialisés avec succès',
                'data' => $defaults
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur réinitialisation paramètres carte: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la réinitialisation'
            ], 500);
        }
    }
}
