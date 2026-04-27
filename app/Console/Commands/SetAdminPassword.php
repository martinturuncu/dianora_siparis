<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetAdminPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:password {password : The new plain text password}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set or update the admin password hash in the .env file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $password = $this->argument('password');
        $hash = \Illuminate\Support\Facades\Hash::make($password);
        
        $path = base_path('.env');
        if (file_exists($path)) {
            $env = file_get_contents($path);
            
            // Check if key exists
            if (preg_match('/^ADMIN_PASSWORD_HASH=.*$/m', $env)) {
                // Replace existing using preg_replace but escaping the hash
                $env = preg_replace('/^ADMIN_PASSWORD_HASH=.*$/m', 'ADMIN_PASSWORD_HASH="' . str_replace('$', '\$', $hash) . '"', $env);
            } else {
                // Append
                $env .= "\nADMIN_PASSWORD_HASH=\"{$hash}\"\n";
            }
            
            file_put_contents($path, $env);
            $this->info('Admin password updated successfully.');
            $this->warn('If you are on production, please run: php artisan config:clear');
        } else {
            $this->error('.env file not found.');
        }
    }
}
