<?php

namespace App\Services;

use App\Models\SmsLog;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NexahSmsService
{
    private string $apiUrl = 'https://smsvas.com/bulk/public/index.php/api/v1';
    private string $user;
    private string $password;
    private string $senderId;

    public function __construct()
    {
        $settings = SchoolSetting::getSettings();
        $this->user = $settings->nexah_sms_user ?? '';
        $this->password = $settings->nexah_sms_password ?? '';
        $this->senderId = $settings->nexah_sms_sender_id ?? 'CPB DOUALA';
    }

    /**
     * Envoyer un SMS a un ou plusieurs numeros
     *
     * @param string|array $phones Numero(s) de telephone
     * @param string $message Contenu du message
     * @param array $meta Donnees supplementaires pour le log (student_id, type, etc.)
     * @return array
     */
    public function sendSms($phones, string $message, array $meta = []): array
    {
        if (empty($this->user) || empty($this->password)) {
            Log::warning('Nexah SMS: credentials not configured');
            return [
                'success' => false,
                'error' => 'Identifiants Nexah SMS non configures'
            ];
        }

        // Formater les numeros
        $mobiles = $this->formatPhoneNumbers($phones);

        if (empty($mobiles)) {
            return [
                'success' => false,
                'error' => 'Aucun numero de telephone valide'
            ];
        }

        $mobilesString = implode(',', $mobiles);

        try {
            $response = Http::timeout(30)->post("{$this->apiUrl}/sendsms", [
                'user' => $this->user,
                'password' => $this->password,
                'senderid' => $this->senderId,
                'sms' => $message,
                'mobiles' => $mobilesString,
            ]);

            $result = $response->json();

            Log::info('Nexah SMS response', [
                'mobiles' => $mobilesString,
                'response_code' => $result['responsecode'] ?? null,
                'response_message' => $result['responsemessage'] ?? null,
            ]);

            // Enregistrer dans les logs
            $success = ($result['responsecode'] ?? 0) == 1;

            foreach ($mobiles as $phone) {
                $smsResult = null;
                if (isset($result['sms']) && is_array($result['sms'])) {
                    $smsResult = collect($result['sms'])->first(function ($sms) use ($phone) {
                        return str_contains($sms['mobileno'] ?? '', ltrim($phone, '+'));
                    });
                }

                SmsLog::create([
                    'phone' => $phone,
                    'message' => $message,
                    'type' => $meta['type'] ?? 'general',
                    'student_id' => $meta['student_id'] ?? null,
                    'school_year_id' => $meta['school_year_id'] ?? null,
                    'status' => ($smsResult['status'] ?? ($success ? 'success' : 'failed')),
                    'nexah_message_id' => $smsResult['messageid'] ?? null,
                    'error_code' => $smsResult['errorcode'] ?? null,
                    'error_description' => $smsResult['errordescription'] ?? ($result['responsemessage'] ?? null),
                    'sent_by' => $meta['sent_by'] ?? null,
                ]);
            }

            return [
                'success' => $success,
                'response' => $result,
                'sent_count' => $success ? count($mobiles) : 0,
                'failed_count' => $success ? 0 : count($mobiles),
            ];

        } catch (\Exception $e) {
            Log::error('Nexah SMS send failed', [
                'error' => $e->getMessage(),
                'mobiles' => $mobilesString,
            ]);

            // Log l'echec
            foreach ($mobiles as $phone) {
                SmsLog::create([
                    'phone' => $phone,
                    'message' => $message,
                    'type' => $meta['type'] ?? 'general',
                    'student_id' => $meta['student_id'] ?? null,
                    'school_year_id' => $meta['school_year_id'] ?? null,
                    'status' => 'failed',
                    'error_description' => $e->getMessage(),
                    'sent_by' => $meta['sent_by'] ?? null,
                ]);
            }

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Verifier le solde SMS du compte Nexah
     */
    public function getBalance(): array
    {
        if (empty($this->user) || empty($this->password)) {
            return ['success' => false, 'error' => 'Identifiants non configures'];
        }

        try {
            $response = Http::timeout(15)->post("{$this->apiUrl}/smscredit", [
                'user' => $this->user,
                'password' => $this->password,
            ]);

            $result = $response->json();

            return [
                'success' => true,
                'credit' => $result['credit'] ?? 0,
                'account_expiry' => $result['accountexpdate'] ?? null,
                'balance_expiry' => $result['balanceexpdate'] ?? null,
            ];
        } catch (\Exception $e) {
            Log::error('Nexah SMS balance check failed', ['error' => $e->getMessage()]);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Formater les numeros de telephone pour le Cameroun (237)
     */
    private function formatPhoneNumbers($phones): array
    {
        if (is_string($phones)) {
            $phones = [$phones];
        }

        $formatted = [];
        foreach ($phones as $phone) {
            $clean = preg_replace('/[^0-9]/', '', $phone);

            // Retirer le + ou 00 du debut
            $clean = preg_replace('/^00/', '', $clean);

            // Si commence par 237, garder tel quel
            if (str_starts_with($clean, '237')) {
                if (strlen($clean) === 12) { // 237 + 9 chiffres
                    $formatted[] = $clean;
                }
            }
            // Si 9 chiffres (6XXXXXXXX), ajouter 237
            elseif (strlen($clean) === 9 && in_array($clean[0], ['6', '2'])) {
                $formatted[] = '237' . $clean;
            }
        }

        return array_unique($formatted);
    }

    /**
     * Tester la connexion Nexah
     */
    public function testConnection(): array
    {
        $balance = $this->getBalance();
        if ($balance['success']) {
            return [
                'success' => true,
                'message' => "Connexion reussie. Credit restant: {$balance['credit']} SMS",
                'credit' => $balance['credit'],
            ];
        }
        return $balance;
    }
}
