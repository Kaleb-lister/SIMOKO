<?php

namespace App\Console\Commands;

use App\Models\Device;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class ResetPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simoko:reset-password {device_uid : The device UID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Reset owner password for an existing SIMOKO device';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deviceUid = trim($this->argument('device_uid'));
        $device = Device::with('user')->where('device_uid', $deviceUid)->first();

        if (! $device || ! $device->user) {
            $this->error("Error: Alat atau user untuk UID '{$deviceUid}' tidak ditemukan!");
            return Command::FAILURE;
        }

        $characters = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $newPassword = substr(str_shuffle(str_repeat($characters, 5)), 0, 10);

        $device->user->update([
            'password' => Hash::make($newPassword),
            'must_change_credentials' => true,
        ]);

        $this->info("==================================================");
        $this->info("   SIMOKO PASSWORD RESET SUCCESSFULLY");
        $this->info("==================================================");
        $this->line("Device UID     : <fg=yellow>{$deviceUid}</>");
        $this->line("Username       : <fg=green>{$device->user->username}</>");
        $this->line("NEW_PASSWORD   : <fg=green>{$newPassword}</>");
        $this->info("==================================================");
        $this->warn("User wajib mengganti username & password saat login berikutnya!");
        $this->info("==================================================");

        return Command::SUCCESS;
    }
}

