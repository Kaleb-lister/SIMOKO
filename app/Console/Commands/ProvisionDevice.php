<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProvisionDevice extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'simoko:provision {device_uid : Unique identifier for the device (e.g. SIMOKO-0001)} {--username= : Custom username for device owner} {--password= : Custom initial password} {--token= : Custom 40-character device token} {--no-change-credentials : Skip mandatory credential change on first login}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Provision a new SIMOKO device with owner credentials and API token';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deviceUid = trim($this->argument('device_uid'));

        if (Device::where('device_uid', $deviceUid)->exists()) {
            $this->error("Error: Alat dengan UID '{$deviceUid}' sudah terdaftar!");
            return Command::FAILURE;
        }

        $username = Str::lower($this->option('username') ?: $deviceUid);

        if (User::where('username', $username)->exists()) {
            $this->error("Error: User dengan username '{$username}' sudah ada!");
            return Command::FAILURE;
        }

        // Generate 10-char unambiguous password if not provided
        $characters = '23456789abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ';
        $password = $this->option('password') ?: substr(str_shuffle(str_repeat($characters, 5)), 0, 10);

        // Generate or use custom 40-character token for device
        $deviceToken = $this->option('token') ?: Str::random(40);
        $tokenHash = hash('sha256', $deviceToken);
        $mustChange = ! $this->option('no-change-credentials');

        DB::transaction(function () use ($username, $password, $deviceUid, $tokenHash, $mustChange) {
            $user = User::create([
                'username' => $username,
                'password' => Hash::make($password),
                'must_change_credentials' => $mustChange,
            ]);

            Device::create([
                'user_id' => $user->id,
                'device_uid' => $deviceUid,
                'token_hash' => $tokenHash,
                'last_reported_balance' => 0,
            ]);
        });

        $this->info("==================================================");
        $this->info("   SIMOKO DEVICE PROVISIONED SUCCESSFULLY");
        $this->info("==================================================");
        $this->line("Device UID     : <fg=yellow>{$deviceUid}</>");
        $this->line("Web Username   : <fg=green>{$username}</>");
        $this->line("Web Password   : <fg=green>{$password}</>");
        $this->line("DEVICE_TOKEN   : <fg=cyan>{$deviceToken}</>");
        $this->info("==================================================");
        $this->warn("PERINGATAN: Simpan DEVICE_TOKEN dan Password di atas!");
        $this->warn("DEVICE_TOKEN tidak dapat ditampilkan lagi setelah ini.");
        $this->info("==================================================");

        return Command::SUCCESS;
    }
}

