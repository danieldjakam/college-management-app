<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Illuminate\Support\Facades\Storage;

class TestQrGeneration extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:qr-generation';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test QR code generation without imagick';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Testing QR code generation...');

        try {
            // Test SVG generation (doesn't require imagick)
            $this->info('Testing SVG format...');
            $qrCode = 'TEST_QR_' . time();
            $qrImage = QrCode::format('svg')
                ->size(200)
                ->generate($qrCode);
            
            $filename = 'test_qr_codes/test_qr_' . time() . '.svg';
            Storage::disk('public')->put($filename, $qrImage);
            
            $this->info('✅ SVG QR code generated successfully!');
            $this->info('File saved: ' . $filename);
            $this->info('URL: ' . Storage::url($filename));

        } catch (\Exception $e) {
            $this->error('❌ Error generating QR code:');
            $this->error($e->getMessage());
            return 1;
        }

        return 0;
    }
}