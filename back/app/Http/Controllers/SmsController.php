<?php

namespace App\Http\Controllers;

use App\Models\SmsLog;
use App\Models\Student;
use App\Models\SchoolYear;
use App\Models\SchoolSetting;
use App\Services\NexahSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SmsController extends Controller
{
    /**
     * Envoyer un SMS aux parents d'un ou plusieurs eleves
     */
    public function sendToParents(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'message' => 'required|string|max:640',
            'send_to' => 'required|in:all,class,student',
            'class_series_id' => 'required_if:send_to,class|integer',
            'student_id' => 'required_if:send_to,student|integer',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $settings = SchoolSetting::getSettings();
        if (!$settings->sms_notifications_enabled) {
            return response()->json([
                'success' => false,
                'message' => 'Les notifications SMS sont desactivees. Activez-les dans les parametres.'
            ], 400);
        }

        $schoolYear = SchoolYear::where('is_current', true)->first();
        if (!$schoolYear) {
            return response()->json(['success' => false, 'message' => 'Aucune annee scolaire courante'], 400);
        }

        // Construire la requete d'eleves
        $query = Student::where('school_year_id', $schoolYear->id)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->whereNotNull('parent_phone')->orWhereNotNull('mother_phone');
            })
            ->with(['classSeries.schoolClass']);

        if ($request->send_to === 'class') {
            $query->where('class_series_id', $request->class_series_id);
        } elseif ($request->send_to === 'student') {
            $query->where('id', $request->student_id);
        }

        $students = $query->get();

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucun eleve trouve avec des contacts parents'
            ], 404);
        }

        $smsService = new NexahSmsService();
        $message = $request->message;
        $sentCount = 0;
        $failedCount = 0;

        foreach ($students as $student) {
            $phones = [];
            if (!empty($student->parent_phone)) $phones[] = $student->parent_phone;
            if (!empty($student->mother_phone)) $phones[] = $student->mother_phone;

            if (empty($phones)) continue;

            // Personnaliser le message avec le nom de l'eleve
            $personalizedMsg = str_replace(
                ['{eleve}', '{classe}'],
                [
                    trim($student->first_name . ' ' . $student->last_name),
                    $student->classSeries->schoolClass->name ?? ''
                ],
                $message
            );

            $result = $smsService->sendSms($phones, $personalizedMsg, [
                'type' => 'manual',
                'student_id' => $student->id,
                'school_year_id' => $schoolYear->id,
                'sent_by' => Auth::id(),
            ]);

            if ($result['success']) {
                $sentCount += $result['sent_count'] ?? 1;
            } else {
                $failedCount += count($phones);
            }
        }

        return response()->json([
            'success' => true,
            'message' => "SMS envoyes: {$sentCount}, Echecs: {$failedCount}",
            'sent_count' => $sentCount,
            'failed_count' => $failedCount,
            'total_students' => $students->count(),
        ]);
    }

    /**
     * Historique des SMS envoyes
     */
    public function history(Request $request)
    {
        $query = SmsLog::with(['student:id,first_name,last_name', 'sender:id,username'])
            ->orderBy('created_at', 'desc');

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $logs = $query->paginate($request->get('per_page', 50));

        return response()->json([
            'success' => true,
            'data' => $logs,
        ]);
    }

    /**
     * Statistiques SMS
     */
    public function stats()
    {
        $total = SmsLog::count();
        $success = SmsLog::where('status', 'success')->count();
        $failed = SmsLog::where('status', 'failed')->count();
        $today = SmsLog::whereDate('created_at', now())->count();

        $byType = SmsLog::selectRaw('type, count(*) as count, SUM(CASE WHEN status = "success" THEN 1 ELSE 0 END) as success_count')
            ->groupBy('type')
            ->get();

        // Solde Nexah
        $smsService = new NexahSmsService();
        $balance = $smsService->getBalance();

        return response()->json([
            'success' => true,
            'data' => [
                'total' => $total,
                'success' => $success,
                'failed' => $failed,
                'today' => $today,
                'by_type' => $byType,
                'balance' => $balance,
            ],
        ]);
    }

    /**
     * Verifier le solde SMS Nexah
     */
    public function balance()
    {
        $smsService = new NexahSmsService();
        $balance = $smsService->getBalance();

        return response()->json([
            'success' => $balance['success'],
            'data' => $balance,
        ]);
    }

    /**
     * Tester la connexion Nexah
     */
    public function testConnection()
    {
        $smsService = new NexahSmsService();
        $result = $smsService->testConnection();

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'] ?? $result['error'] ?? 'Erreur inconnue',
            'data' => $result,
        ]);
    }

    /**
     * Envoyer un SMS de test
     */
    public function sendTest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'message' => 'sometimes|string|max:320',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $smsService = new NexahSmsService();
        $message = $request->message ?? 'Test SMS - College Polyvalent Bilingue de Douala. Si vous recevez ce message, la configuration est correcte.';

        $result = $smsService->sendSms($request->phone, $message, [
            'type' => 'test',
            'sent_by' => Auth::id(),
        ]);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['success'] ? 'SMS de test envoye avec succes' : ($result['error'] ?? 'Echec de l\'envoi'),
            'data' => $result,
        ]);
    }
}
