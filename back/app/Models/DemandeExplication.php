<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DemandeExplication extends Model
{
    use HasFactory;

    protected $table = 'demandes_explication';

    protected $fillable = [
        'emetteur_id',
        'destinataire_id', 
        'school_year_id',
        'sujet',
        'message',
        'priorite',
        'statut',
        'type',
        'date_envoi',
        'date_lecture', 
        'date_reponse',
        'date_cloture',
        'reponse',
        'pieces_jointes',
        'notes_internes'
    ];

    protected $casts = [
        'date_envoi' => 'datetime',
        'date_lecture' => 'datetime', 
        'date_reponse' => 'datetime',
        'date_cloture' => 'datetime',
        'pieces_jointes' => 'array'
    ];

    // Relations
    
    /**
     * Utilisateur qui émet la demande (comptable)
     */
    public function emetteur()
    {
        return $this->belongsTo(User::class, 'emetteur_id');
    }

    /**
     * Utilisateur destinataire de la demande 
     */
    public function destinataire()
    {
        return $this->belongsTo(User::class, 'destinataire_id');
    }

    /**
     * Année scolaire
     */
    public function schoolYear()
    {
        return $this->belongsTo(SchoolYear::class, 'school_year_id');
    }

    // Scopes

    /**
     * Scope pour filtrer par statut
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('statut', $status);
    }

    /**
     * Scope pour les demandes envoyées par un utilisateur
     */
    public function scopeEmisesPar($query, $userId)
    {
        return $query->where('emetteur_id', $userId);
    }

    /**
     * Scope pour les demandes reçues par un utilisateur
     */
    public function scopeRecuesPar($query, $userId)
    {
        return $query->where('destinataire_id', $userId);
    }

    /**
     * Scope pour les demandes en attente
     */
    public function scopeEnAttente($query)
    {
        return $query->whereIn('statut', ['envoyee', 'lue']);
    }

    /**
     * Scope pour filtrer par priorité
     */
    public function scopeAvecPriorite($query, $priorite)
    {
        return $query->where('priorite', $priorite);
    }

    /**
     * Scope pour filtrer par type
     */
    public function scopeDeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope pour l'année scolaire active
     */
    public function scopeAnneeCourante($query)
    {
        $schoolYear = SchoolYear::where('is_current', true)->first();
        if ($schoolYear) {
            return $query->where('school_year_id', $schoolYear->id);
        }
        return $query;
    }

    // Accessors et Mutators

    /**
     * Obtenir le libellé du statut
     */
    public function getStatutLabelAttribute()
    {
        $labels = [
            'brouillon' => 'Brouillon',
            'envoyee' => 'Envoyée',
            'lue' => 'Lue',
            'repondue' => 'Répondue', 
            'cloturee' => 'Clôturée'
        ];

        return $labels[$this->statut] ?? $this->statut;
    }

    /**
     * Obtenir le libellé de la priorité
     */
    public function getPrioriteLabelAttribute()
    {
        $labels = [
            'basse' => 'Basse',
            'normale' => 'Normale',
            'haute' => 'Haute',
            'urgente' => 'Urgente'
        ];

        return $labels[$this->priorite] ?? $this->priorite;
    }

    /**
     * Obtenir le libellé du type
     */
    public function getTypeLabelAttribute()
    {
        $labels = [
            'financier' => 'Financier',
            'absence' => 'Absence',
            'retard' => 'Retard',
            'disciplinaire' => 'Disciplinaire',
            'autre' => 'Autre'
        ];

        return $labels[$this->type] ?? $this->type;
    }

    /**
     * Vérifier si la demande a été lue
     */
    public function getEstLueAttribute()
    {
        return !is_null($this->date_lecture);
    }

    /**
     * Vérifier si la demande a une réponse
     */
    public function getAReponseAttribute()
    {
        return !is_null($this->reponse) && !empty(trim($this->reponse));
    }

    /**
     * Calculer le délai depuis l'envoi
     */
    public function getDelaiDepuisEnvoiAttribute()
    {
        if (!$this->date_envoi) {
            return null;
        }

        return $this->date_envoi->diffForHumans();
    }

    /**
     * Obtenir la couleur basée sur la priorité
     */
    public function getCouleurPrioriteAttribute()
    {
        $couleurs = [
            'basse' => 'success',
            'normale' => 'info', 
            'haute' => 'warning',
            'urgente' => 'danger'
        ];

        return $couleurs[$this->priorite] ?? 'secondary';
    }

    /**
     * Obtenir la couleur basée sur le statut
     */
    public function getCouleurStatutAttribute()
    {
        $couleurs = [
            'brouillon' => 'secondary',
            'envoyee' => 'primary',
            'lue' => 'info',
            'repondue' => 'success',
            'cloturee' => 'dark'
        ];

        return $couleurs[$this->statut] ?? 'secondary';
    }

    // Méthodes d'action

    /**
     * Marquer la demande comme envoyée
     */
    public function marquerCommeEnvoyee()
    {
        $this->update([
            'statut' => 'envoyee',
            'date_envoi' => now()
        ]);
    }

    /**
     * Marquer la demande comme lue
     */
    public function marquerCommeLue()
    {
        if ($this->statut === 'envoyee') {
            $this->update([
                'statut' => 'lue',
                'date_lecture' => now()
            ]);
        }
    }

    /**
     * Ajouter une réponse à la demande
     */
    public function ajouterReponse($reponse)
    {
        $this->update([
            'reponse' => $reponse,
            'statut' => 'repondue',
            'date_reponse' => now()
        ]);
    }

    /**
     * Clôturer la demande
     */
    public function cloturer()
    {
        $this->update([
            'statut' => 'cloturee',
            'date_cloture' => now()
        ]);
    }
}