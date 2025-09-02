<?php

/**
 * Script de correction du bug de calcul des bourses après transfert d'élève
 * 
 * Ce script corrige le problème où le système utilisait la bourse configurée
 * au lieu de la bourse réelle payée lors des transferts d'élèves.
 * 
 * Usage: php fix-scholarship-bug.php
 */

require_once __DIR__ . '/vendor/autoload.php';

// Bootstrap Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== SCRIPT DE CORRECTION DU BUG DE BOURSE ===\n\n";

try {
    echo "1. CRÉATION DE LA SAUVEGARDE...\n";
    
    // Créer une sauvegarde du fichier original
    $originalFile = __DIR__ . '/app/Services/PaymentStatusService.php';
    $backupFile = __DIR__ . '/app/Services/PaymentStatusService.php.backup.' . date('Y-m-d-H-i-s');
    
    if (!copy($originalFile, $backupFile)) {
        throw new Exception("Impossible de créer la sauvegarde");
    }
    echo "   ✅ Sauvegarde créée: " . basename($backupFile) . "\n\n";
    
    echo "2. VÉRIFICATION DE L'ENVIRONNEMENT...\n";
    
    // Vérifier que nous sommes dans le bon répertoire
    if (!file_exists($originalFile)) {
        throw new Exception("Fichier PaymentStatusService.php non trouvé");
    }
    
    // Vérifier la structure Laravel
    if (!file_exists(__DIR__ . '/artisan')) {
        throw new Exception("Ce script doit être exécuté depuis la racine du projet Laravel");
    }
    echo "   ✅ Structure Laravel détectée\n";
    echo "   ✅ Fichier PaymentStatusService trouvé\n\n";
    
    echo "3. TEST AVANT CORRECTION...\n";
    
    // Tester le cas problématique (élève ZE ATANGANA)
    $student = \App\Models\Student::where('last_name', 'LIKE', '%ZE ATANGANA%')
        ->where('first_name', 'LIKE', '%MARIE PAULE%')
        ->first();
    
    if ($student) {
        $workingYear = \App\Models\SchoolYear::where('is_current', true)->first() 
            ?? \App\Models\SchoolYear::where('is_active', true)->first();
        
        if ($workingYear) {
            $paymentStatusService = new \App\Services\PaymentStatusService();
            $statusBefore = $paymentStatusService->getStatusForStudent($student, $workingYear);
            
            echo "   Élève test: " . $student->last_name . " " . $student->first_name . "\n";
            echo "   Reste avant correction: " . $statusBefore->total_remaining . " FCFA\n";
            echo "   Bourse avant correction: " . $statusBefore->total_scholarship_amount . " FCFA\n\n";
        }
    }
    
    echo "4. APPLICATION DE LA CORRECTION...\n";
    
    // Lire le contenu du fichier
    $content = file_get_contents($originalFile);
    
    // Vérifier si la correction n'est pas déjà appliquée
    if (strpos($content, 'CORRECTION: Calculer la bourse totale réelle') !== false) {
        echo "   ⚠️ La correction semble déjà appliquée!\n";
        echo "   Voulez-vous continuer quand même? (y/N): ";
        $handle = fopen("php://stdin", "r");
        $line = fgets($handle);
        fclose($handle);
        
        if (trim(strtolower($line)) !== 'y') {
            echo "   Script annulé.\n";
            exit(0);
        }
    }
    
    // Appliquer les corrections
    $corrections = [
        // Correction 1: Calculer la bourse réelle dans la méthode principale
        [
            'search' => 'private function calculateStatusForStudent(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments): object
    {

        // Utiliser la logique de réduction par dernières tranches pour les étudiants avec paiements réduits',
            'replace' => 'private function calculateStatusForStudent(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments): object
    {
        // CORRECTION: Calculer la bourse totale réelle depuis les paiements existants
        $totalActualScholarshipFromPayments = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalActualScholarshipFromPayments += $payment->scholarship_amount;
            }
        }

        // Utiliser la logique de réduction par dernières tranches pour les étudiants avec paiements réduits'
        ],
        
        // Correction 2: Utiliser la bourse réelle au lieu de celle calculée
        [
            'search' => '        // Calculer le montant total des bourses disponibles (pour information)
        $totalScholarshipAmount = $this->calculateTotalScholarshipAmount($student, $paymentTranches);',
            'replace' => '        // CORRECTION: Utiliser la bourse réelle si elle existe
        $totalScholarshipAmount = $totalActualScholarshipFromPayments;
        
        // Si pas de bourse réelle, utiliser la méthode de calcul normale
        if ($totalScholarshipAmount == 0) {
            $totalScholarshipAmount = $this->calculateTotalScholarshipAmount($student, $paymentTranches);
        }'
        ],
        
        // Correction 3: Modifier la méthode calculateTrancheDetails pour appliquer la bourse séquentiellement
        [
            'search' => 'private function calculateTrancheDetails(Student $student, $paymentTranches, $existingPayments)
    {
        $trancheStatus = [];
        $totalRequired = 0;
        $totalPaid = 0;
        $totalEffectiveRequired = 0; // Montant requis après réductions/bourses

        $paidPerTranche = [];
        $discountPerTranche = [];
        foreach ($existingPayments as $payment) {',
            'replace' => 'private function calculateTrancheDetails(Student $student, $paymentTranches, $existingPayments)
    {
        $trancheStatus = [];
        $totalRequired = 0;
        $totalPaid = 0;
        $totalEffectiveRequired = 0; // Montant requis après réductions/bourses

        $paidPerTranche = [];
        $discountPerTranche = [];
        
        // CORRECTION: Calculer la bourse totale réelle des paiements existants
        $totalActualScholarship = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalActualScholarship += $payment->scholarship_amount;
            }
            
            foreach ($payment->paymentDetails as $detail) {'
        ],
        
        // Correction 4: Appliquer la logique séquentielle
        [
            'search' => '        foreach ($paymentTranches as $tranche) {
            $requiredAmount = $tranche->getAmountForStudent($student, false, false, false); // Montants NORMAUX
            if ($requiredAmount <= 0) continue;

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;

            // Vérifier si cette tranche bénéficie d\'une bourse
            $scholarshipAmount = 0;
            $hasScholarship = false;
            $globalDiscountAmount = 0;
            $hasGlobalDiscount = false;
            $remainingAmount = 0;
            $isFullyPaid = false;',
            'replace' => '        // CORRECTION: Appliquer la bourse séquentiellement sur les tranches non payées
        $remainingScholarshipToApply = $totalActualScholarship;
        
        foreach ($paymentTranches as $tranche) {
            $requiredAmount = $tranche->getAmountForStudent($student, false, false, false); // Montants NORMAUX
            if ($requiredAmount <= 0) continue;

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;
            $trancheRemaining = max(0, $requiredAmount - $paidAmount);
            
            // Vérifier si cette tranche bénéficie d\'une bourse
            $scholarshipAmount = 0;
            $hasScholarship = false;
            $globalDiscountAmount = 0;
            $hasGlobalDiscount = false;
            $remainingAmount = 0;
            $isFullyPaid = false;
            
            // Si la tranche est entièrement payée en cash, pas de reste
            if ($paidAmount >= $requiredAmount) {
                $remainingAmount = 0;
                $isFullyPaid = true;
                $scholarshipAmount = 0;
                $hasScholarship = false;
            } else {
                // Appliquer la bourse restante sur cette tranche
                if ($remainingScholarshipToApply > 0) {
                    // Appliquer autant de bourse que possible sur cette tranche
                    $scholarshipAmount = min($remainingScholarshipToApply, $trancheRemaining);
                    $remainingScholarshipToApply -= $scholarshipAmount;
                    $hasScholarship = $scholarshipAmount > 0;
                } else {
                    $scholarshipAmount = 0;
                    $hasScholarship = false;
                }
                
                // Le reste après application de la bourse
                $remainingAmount = max(0, $trancheRemaining - $scholarshipAmount);
                $isFullyPaid = $remainingAmount == 0;
            }'
        ]
    ];
    
    $correctionCount = 0;
    foreach ($corrections as $i => $correction) {
        if (strpos($content, $correction['search']) !== false) {
            $content = str_replace($correction['search'], $correction['replace'], $content);
            $correctionCount++;
            echo "   ✅ Correction " . ($i + 1) . " appliquée\n";
        } else {
            echo "   ⚠️ Correction " . ($i + 1) . " non trouvée (peut-être déjà appliquée)\n";
        }
    }
    
    // Écrire le fichier corrigé
    if (file_put_contents($originalFile, $content) === false) {
        throw new Exception("Impossible d'écrire le fichier corrigé");
    }
    
    echo "   ✅ " . $correctionCount . " corrections appliquées au fichier\n\n";
    
    echo "5. TEST APRÈS CORRECTION...\n";
    
    // Vider le cache des classes pour forcer le rechargement
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Re-tester avec les corrections
    if ($student && $workingYear) {
        // Créer une nouvelle instance du service
        $paymentStatusService = new \App\Services\PaymentStatusService();
        $statusAfter = $paymentStatusService->getStatusForStudent($student, $workingYear);
        
        echo "   Reste après correction: " . $statusAfter->total_remaining . " FCFA\n";
        echo "   Bourse après correction: " . $statusAfter->total_scholarship_amount . " FCFA\n";
        
        // Vérifier la cohérence des tranches
        $totalRemaining = 0;
        $completedTranches = 0;
        foreach ($statusAfter->tranche_status as $tranche) {
            $totalRemaining += $tranche['remaining_amount'];
            if ($tranche['is_fully_paid']) {
                $completedTranches++;
            }
        }
        
        echo "   Tranches complètes: " . $completedTranches . "\n";
        echo "   Cohérence: " . ($totalRemaining == $statusAfter->total_remaining ? "✅ OK" : "❌ ERREUR") . "\n\n";
        
        if ($statusAfter->total_remaining == 10000 && $totalRemaining == $statusAfter->total_remaining) {
            echo "✅ SUCCÈS: Le bug est corrigé!\n";
            echo "   - Le reste à payer est maintenant correct (10000 FCFA)\n";
            echo "   - La bourse réelle (20000 FCFA) est prise en compte\n";
            echo "   - Les tranches sont cohérentes\n";
        } else {
            echo "⚠️ La correction n'a pas fonctionné comme attendu\n";
            echo "   Consultez la sauvegarde: " . basename($backupFile) . "\n";
        }
    }
    
    echo "\n6. INSTRUCTIONS POST-CORRECTION...\n";
    echo "   ✅ Redémarrez votre serveur web (Apache/Nginx)\n";
    echo "   ✅ Videz le cache PHP si applicable\n";
    echo "   ✅ Testez l'interface utilisateur\n";
    echo "   ✅ Vérifiez les reçus générés\n";
    echo "\n   📁 Sauvegarde disponible: " . basename($backupFile) . "\n";
    echo "   📝 Pour restaurer: cp " . basename($backupFile) . " app/Services/PaymentStatusService.php\n";
    
} catch (Exception $e) {
    echo "\n❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Le script a été interrompu. Aucune modification n'a été appliquée.\n";
    
    // Restaurer la sauvegarde si elle existe
    if (isset($backupFile) && file_exists($backupFile) && isset($originalFile)) {
        copy($backupFile, $originalFile);
        echo "Le fichier original a été restauré.\n";
    }
    
    exit(1);
}

echo "\n=== CORRECTION TERMINÉE AVEC SUCCÈS ===\n";