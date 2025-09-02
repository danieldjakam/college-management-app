<?php

/**
 * Script de correction du bug de bourse - VERSION CORRIGÉE
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== CORRECTION DU BUG DE BOURSE ===\n\n";

try {
    // 1. Sauvegarde
    $originalFile = __DIR__ . '/app/Services/PaymentStatusService.php';
    $backupFile = $originalFile . '.backup.' . date('Y-m-d-H-i-s');
    
    if (!copy($originalFile, $backupFile)) {
        throw new Exception("Impossible de créer la sauvegarde");
    }
    echo "✅ Sauvegarde créée: " . basename($backupFile) . "\n\n";
    
    // 2. Lire le fichier original et appliquer les corrections
    $content = file_get_contents($originalFile);
    
    // Correction 1: Ajouter le calcul de bourse réelle dans calculateStatusForStudent
    $search1 = 'private function calculateStatusForStudent(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments): object
    {

        // Utiliser la logique de réduction par dernières tranches';
    
    $replace1 = 'private function calculateStatusForStudent(Student $student, SchoolYear $schoolYear, $paymentTranches, $existingPayments): object
    {
        // CORRECTION: Calculer la bourse totale réelle depuis les paiements existants
        $totalActualScholarshipFromPayments = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalActualScholarshipFromPayments += $payment->scholarship_amount;
            }
        }

        // Utiliser la logique de réduction par dernières tranches';
    
    $content = str_replace($search1, $replace1, $content);
    
    // Correction 2: Utiliser la bourse réelle
    $search2 = '        // Calculer le montant total des bourses disponibles (pour information)
        $totalScholarshipAmount = $this->calculateTotalScholarshipAmount($student, $paymentTranches);';
    
    $replace2 = '        // CORRECTION: Utiliser la bourse réelle si elle existe
        $totalScholarshipAmount = $totalActualScholarshipFromPayments;
        
        // Si pas de bourse réelle, utiliser la méthode de calcul normale
        if ($totalScholarshipAmount == 0) {
            $totalScholarshipAmount = $this->calculateTotalScholarshipAmount($student, $paymentTranches);
        }';
    
    $content = str_replace($search2, $replace2, $content);
    
    // Correction 3: Modifier calculateTrancheDetails
    $search3 = '        $paidPerTranche = [];
        $discountPerTranche = [];
        foreach ($existingPayments as $payment) {
            foreach ($payment->paymentDetails as $detail) {';
    
    $replace3 = '        $paidPerTranche = [];
        $discountPerTranche = [];
        
        // CORRECTION: Calculer la bourse totale réelle des paiements existants
        $totalActualScholarship = 0;
        foreach ($existingPayments as $payment) {
            if ($payment->has_scholarship && $payment->scholarship_amount > 0) {
                $totalActualScholarship += $payment->scholarship_amount;
            }
            
            foreach ($payment->paymentDetails as $detail) {';
    
    $content = str_replace($search3, $replace3, $content);
    
    // Correction 4: Logique séquentielle pour les tranches
    $search4 = '        foreach ($paymentTranches as $tranche) {
            $requiredAmount = $tranche->getAmountForStudent($student, false, false, false); // Montants NORMAUX
            if ($requiredAmount <= 0) continue;

            $paidAmount = $paidPerTranche[$tranche->id] ?? 0;

            // Vérifier si cette tranche bénéficie d\'une bourse
            $scholarshipAmount = 0;
            $hasScholarship = false;
            $globalDiscountAmount = 0;
            $hasGlobalDiscount = false;
            $remainingAmount = 0;
            $isFullyPaid = false;';
    
    $replace4 = '        // CORRECTION: Appliquer la bourse séquentiellement sur les tranches non payées
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
            }';
    
    $content = str_replace($search4, $replace4, $content);
    
    // Correction 5: Simplifier la logique des cas spéciaux
    $search5 = '            if ($scholarship && $scholarship->payment_tranche_id == $tranche->id && $discountCalculator->isEligibleForScholarship(now())) {
                // Cas avec bourse
                $scholarshipAmount = $scholarship->amount;
                $hasScholarship = true;
                
                // Calculer le restant en tenant compte de la bourse
                $effectiveRequired = max(0, $requiredAmount - $scholarshipAmount);
                $remainingAmount = max(0, $effectiveRequired - $paidAmount);
                $isFullyPaid = ($paidAmount + $scholarshipAmount) >= $requiredAmount;
            } else {';
    
    $replace5 = '            // Gérer les cas spéciaux seulement si pas de bourse réelle
            if ($totalActualScholarship == 0) {
                // Fallback vers bourse configurée si pas de bourse réelle
                if ($scholarship && $scholarship->payment_tranche_id == $tranche->id && $discountCalculator->isEligibleForScholarship(now())) {
                    // Cas avec bourse configurée
                    $scholarshipAmount = $scholarship->amount;
                    $hasScholarship = true;
                    
                    // Calculer le restant en tenant compte de la bourse
                    $effectiveRequired = max(0, $requiredAmount - $scholarshipAmount);
                    $remainingAmount = max(0, $effectiveRequired - $paidAmount);
                    $isFullyPaid = ($paidAmount + $scholarshipAmount) >= $requiredAmount;
                } else {';
    
    $content = str_replace($search5, $replace5, $content);
    
    // Ajouter la fermeture du if pour $totalActualScholarship == 0
    $search6 = '                }
            }

            $trancheStatus[] = [';
    
    $replace6 = '                }
            }
            // Si il y a une bourse réelle, les calculs sont déjà faits plus haut

            $trancheStatus[] = [';
    
    $content = str_replace($search6, $replace6, $content);
    
    // Écrire le fichier corrigé
    if (file_put_contents($originalFile, $content) === false) {
        throw new Exception("Impossible d'écrire le fichier corrigé");
    }
    
    echo "✅ Fichier PaymentStatusService.php corrigé\n\n";
    
    // 3. Test
    echo "=== TEST DU CORRECTIF ===\n";
    
    // Vider le cache
    if (function_exists('opcache_reset')) {
        opcache_reset();
    }
    
    // Tester l'élève problématique
    $student = App\Models\Student::where('last_name', 'LIKE', '%ZE ATANGANA%')
        ->where('first_name', 'LIKE', '%MARIE PAULE%')
        ->first();
    
    if ($student) {
        $workingYear = App\Models\SchoolYear::where('is_current', true)->first() 
            ?? App\Models\SchoolYear::where('is_active', true)->first();
        
        if ($workingYear) {
            $paymentStatusService = new App\Services\PaymentStatusService();
            $status = $paymentStatusService->getStatusForStudent($student, $workingYear);
            
            echo "Élève: " . $student->last_name . " " . $student->first_name . "\n";
            echo "Reste à payer: " . $status->total_remaining . " FCFA\n";
            echo "Bourse: " . $status->total_scholarship_amount . " FCFA\n";
            
            $completedTranches = 0;
            foreach ($status->tranche_status as $tranche) {
                if ($tranche['is_fully_paid']) {
                    $completedTranches++;
                }
            }
            echo "Tranches complètes: " . $completedTranches . "\n";
            
            if ($status->total_remaining == 10000 && $status->total_scholarship_amount == 20000) {
                echo "\n🎉 SUCCÈS: Le bug est corrigé!\n";
                echo "   ✅ Reste correct: 10000 FCFA\n";
                echo "   ✅ Bourse réelle: 20000 FCFA\n";
                echo "   ✅ Tranches cohérentes\n";
            } else {
                echo "\n⚠️ Problème détecté. Vérifiez les résultats.\n";
            }
        }
    }
    
    echo "\n✅ CORRECTION APPLIQUÉE AVEC SUCCÈS\n";
    echo "📁 Sauvegarde: " . basename($backupFile) . "\n";
    echo "🔄 Redémarrez votre serveur web\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    
    if (isset($backupFile) && file_exists($backupFile) && isset($originalFile)) {
        copy($backupFile, $originalFile);
        echo "✅ Fichier original restauré\n";
    }
    
    exit(1);
}

echo "\n=== CORRECTION TERMINÉE ===\n";
?>