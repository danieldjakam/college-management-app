<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use App\Jobs\SendWhatsAppNotification;

class ResetWhatsAppRateLimit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'whatsapp:reset-rate-limit {--show : Afficher uniquement les statistiques sans réinitialiser}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Réinitialiser le compteur de rate limiting WhatsApp ou afficher les statistiques';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cacheKey = 'whatsapp_rate_limit';
        $currentCount = Cache::get($cacheKey, 0);
        $maxMessages = SendWhatsAppNotification::MAX_MESSAGES_PER_PERIOD;
        $periodMinutes = SendWhatsAppNotification::RATE_LIMIT_PERIOD_MINUTES;

        // Afficher les statistiques actuelles
        $this->info('📊 Statistiques du Rate Limiting WhatsApp');
        $this->newLine();
        $this->line("   Configuration :");
        $this->line("   • Limite : {$maxMessages} messages par période");
        $this->line("   • Période : {$periodMinutes} minutes");
        $this->newLine();
        $this->line("   État actuel :");
        $this->line("   • Messages envoyés dans la période : {$currentCount}/{$maxMessages}");
        $this->line("   • Messages restants avant pause : " . max(0, $maxMessages - $currentCount));

        if ($currentCount >= $maxMessages) {
            $this->warn("   ⚠️  LIMITE ATTEINTE - Les prochains messages seront mis en pause");
        } else {
            $this->info("   ✅ Limite non atteinte - Envois actifs");
        }

        $this->newLine();

        // Si l'option --show est présente, ne pas réinitialiser
        if ($this->option('show')) {
            return Command::SUCCESS;
        }

        // Demander confirmation avant de réinitialiser
        if (!$this->confirm('Voulez-vous réinitialiser le compteur ?', false)) {
            $this->info('❌ Réinitialisation annulée');
            return Command::SUCCESS;
        }

        // Réinitialiser le compteur
        Cache::forget($cacheKey);

        $this->newLine();
        $this->info('✅ Compteur de rate limiting réinitialisé avec succès !');
        $this->line('   Les messages peuvent maintenant être envoyés immédiatement.');

        return Command::SUCCESS;
    }
}
