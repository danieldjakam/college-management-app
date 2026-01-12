<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $this->ensureAutoIncrement('payments');
        $this->ensureAutoIncrement('payment_details');
    }

    private function ensureAutoIncrement(string $table): void
    {
        $row = DB::selectOne("SHOW COLUMNS FROM {$table} WHERE Field = 'id'");
        if (!$row) {
            return;
        }

        $extra = strtolower((string) ($row->Extra ?? ''));
        if (str_contains($extra, 'auto_increment')) {
            return;
        }

        DB::statement("ALTER TABLE {$table} MODIFY id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
    }

    public function down(): void
    {
        // No-op
    }
};
