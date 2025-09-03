<?php
// Script de test en production pour reçus WhatsApp
// Usage: php test-parent-receipt-prod.php

require_once __DIR__ . "/vendor/autoload.php";
$app = require_once __DIR__ . "/bootstrap/app.php";
$app->make("Illuminate\Contracts\Console\Kernel")->bootstrap();

echo "TEST REÇUS PARENTS WHATSAPP\n";
echo "============================\n\n";

// 1. Vérifier la configuration
$settings = \App\Models\SchoolSetting::getSettings();
echo "1. Configuration:\n";
echo "   APP_URL: " . config("app.url") . "\n";
echo "   Token WhatsApp: " . ($settings->whatsapp_token ? "✓" : "✗") . "\n";
echo "   Instance ID: " . $settings->whatsapp_instance_id . "\n\n";

// 2. Tester la génération de reçu
$payment = \App\Models\Payment::with("student")->latest()->first();
if ($payment) {
    echo "2. Test génération reçu:\n";
    echo "   Paiement #" . $payment->id . "\n";
    echo "   Élève: " . $payment->student->first_name . " " . $payment->student->last_name . "\n";
    
    $controller = app()->make(\App\Http\Controllers\PaymentController::class);
    $result = $controller->generateParentReceipt($payment->id);
    
    if ($result["success"]) {
        echo "   ✓ Reçu généré avec succès\n";
        echo "   Taille HTML: " . strlen($result["html"]) . " caractères\n\n";
        
        // 3. Tester envoi WhatsApp
        echo "3. Test envoi WhatsApp:\n";
        $phoneTest = "659339778"; // Votre numéro de test
        
        // Sauvegarder temporairement avec numéro de test
        $originalPhone = $payment->student->parent_phone;
        $payment->student->parent_phone = $phoneTest;
        $payment->student->save();
        
        $whatsapp = new \App\Services\WhatsAppService();
        $sent = $whatsapp->sendPaymentNotification($payment);
        
        // Restaurer
        $payment->student->parent_phone = $originalPhone;
        $payment->student->save();
        
        if ($sent) {
            echo "   ✓ Message envoyé sur " . $phoneTest . "\n";
            echo "   Vérifiez votre WhatsApp!\n";
        } else {
            echo "   ✗ Échec envoi\n";
        }
    } else {
        echo "   ✗ Erreur: " . $result["message"] . "\n";
    }
} else {
    echo "Aucun paiement trouvé\n";
}

echo "\n============================\n";
echo "Test terminé\n";
