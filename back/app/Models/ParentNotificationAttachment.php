<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParentNotificationAttachment extends Model
{
    protected $fillable = [
        'parent_notification_id',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'whatsapp_media_id',
        'whatsapp_sent'
    ];

    protected $casts = [
        'whatsapp_sent' => 'boolean',
    ];

    /**
     * Relation avec la notification parent
     */
    public function parentNotification()
    {
        return $this->belongsTo(ParentNotification::class);
    }

    /**
     * Obtenir l'URL complète du fichier
     */
    public function getFileUrlAttribute()
    {
        return url('storage/' . $this->file_path);
    }

    /**
     * Obtenir la taille formatée du fichier
     */
    public function getFormattedSizeAttribute()
    {
        $bytes = $this->file_size;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return round($bytes / 1024, 2) . ' KB';
        }
        return $bytes . ' B';
    }
}
