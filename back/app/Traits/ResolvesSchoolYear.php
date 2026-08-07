<?php

namespace App\Traits;

use App\Models\SchoolYear;
use Illuminate\Support\Facades\Auth;

trait ResolvesSchoolYear
{
    /**
     * Résoudre l'année scolaire de travail de l'utilisateur connecté.
     *
     * Priorité :
     * 1. school_year_id passé en paramètre de la requête (pour navigation historique)
     * 2. working_school_year_id de l'utilisateur
     * 3. Année courante (is_current = true)
     * 4. Première année active
     */
    protected function resolveSchoolYear($requestSchoolYearId = null): ?SchoolYear
    {
        // 1. Paramètre explicite (ex: depuis le frontend)
        if ($requestSchoolYearId) {
            $year = SchoolYear::find($requestSchoolYearId);
            if ($year) {
                return $year;
            }
        }

        // 2. Année de travail de l'utilisateur
        $user = Auth::user();
        if ($user && $user->working_school_year_id) {
            $year = SchoolYear::find($user->working_school_year_id);
            if ($year) {
                return $year;
            }
        }

        // 3. Année courante
        $year = SchoolYear::where('is_current', true)->first();
        if ($year) {
            return $year;
        }

        // 4. Première année active
        return SchoolYear::where('is_active', true)->first();
    }
}
