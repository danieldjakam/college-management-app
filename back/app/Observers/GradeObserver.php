<?php

namespace App\Observers;

use App\Models\Grade;
use App\Services\BulletinAutoGenerationService;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;

class GradeObserver
{
    /**
     * Handle the Grade "created" event.
     */
    public function created(Grade $grade): void
    {
        // Déclencher la génération automatique après création d'une note
        $this->triggerBulletinGeneration($grade);
    }

    /**
     * Handle the Grade "updated" event.
     */
    public function updated(Grade $grade): void
    {
        // Déclencher la génération automatique après mise à jour d'une note
        // Uniquement si le score a été modifié
        if ($grade->wasChanged('score')) {
            $this->triggerBulletinGeneration($grade);
        }
    }

    /**
     * Handle the Grade "deleted" event.
     */
    public function deleted(Grade $grade): void
    {
        // Déclencher la régénération pour retirer la note supprimée
        $this->triggerBulletinGeneration($grade);
    }

    /**
     * Déclenche la génération automatique des bulletins
     */
    protected function triggerBulletinGeneration(Grade $grade): void
    {
        try {
            // Utiliser dispatch pour traiter en arrière-plan (optionnel)
            // Ou traiter immédiatement pour des tests
            
            $autoGenerationService = App::make(BulletinAutoGenerationService::class);
            $autoGenerationService->checkAndGenerateBulletins($grade->id);
            
            Log::info("Génération automatique déclenchée pour grade ID: {$grade->id}, étudiant: {$grade->student_id}");
            
        } catch (\Exception $e) {
            Log::error("Erreur lors du déclenchement de la génération automatique: " . $e->getMessage());
        }
    }

    /**
     * Handle the Grade "restored" event.
     */
    public function restored(Grade $grade): void
    {
        $this->triggerBulletinGeneration($grade);
    }

    /**
     * Handle the Grade "force deleted" event.
     */
    public function forceDeleted(Grade $grade): void
    {
        $this->triggerBulletinGeneration($grade);
    }
}
