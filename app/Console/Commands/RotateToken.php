<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class RotateToken extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simoko:rotate-token {device_uid : The device UID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rotate API token for an existing SIMOKO device';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deviceUid = trim($this->argument('device_uid'));
        $device = Device::where('device_uid', $deviceUid)->first();

        if (! $device) {
            $this->error("Error: Alat dengan UID '{$deviceUid}' tidak ditemukan!");
            return Command::FAILURE;
        }

        $newToken = Str::random(40);
        $tokenHash = hash('sha256', $newToken);

        $device->update(['token_hash' => $tokenHash]);

        $this->info("==================================================");
        $this->info("   SIMOKO TOKEN ROTATED SUCCESSFULLY");
        $this->info("==================================================");
        $this->line("Device UID     : <fg=yellow>{$deviceUid}</>");
        $this->line("NEW_TOKEN      : <fg=cyan>{$newToken}</>");
        $this->info("==================================================");
        $this->warn("Segera perbarui DEVICE_TOKEN pada secrets.h alat ESP32!");
        $this->info("==================================================");

        return Command::SUCCESS;
    }
}

