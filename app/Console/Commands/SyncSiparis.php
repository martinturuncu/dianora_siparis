<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\SiparisSyncService;

class SyncSiparis extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'siparis:sync {--direction=both : push, pull or both}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Siparisleri uzak sunucu ile senkronize eder.';

    protected $syncService;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct(SiparisSyncService $syncService)
    {
        parent::__construct();
        $this->syncService = $syncService;
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $direction = $this->option('direction');

        $this->info("Senkronizasyon işlemi başlatılıyor... Yön: $direction");

        if ($direction === 'push' || $direction === 'both') {
            $this->info("Veriler gönderiliyor (Push)...");
            $result = $this->syncService->pushToRemote();
            $this->info($result);
        }

        if ($direction === 'pull' || $direction === 'both') { // Doğru string karşılaştırma operatörü
            $this->info("Veriler çekiliyor (Pull)...");
            $result = $this->syncService->pullFromRemote();
            $this->info($result);
        }

        $this->info("İşlem tamamlandı.");
        return 0;
    }
}
