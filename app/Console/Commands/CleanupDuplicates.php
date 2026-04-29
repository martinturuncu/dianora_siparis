<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupDuplicates extends Command
{
    protected $signature = 'db:cleanup-duplicates
                            {--dry-run : Sadece raporu goster, hicbir sey silme}
                            {--force : Onay sormadan calistir}
                            {--no-backup : Backup tablosu olusturma}';

    protected $description = 'SiparisUrunleri tablosundaki duplicate satirlari temizler';

    public function handle()
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $noBackup = $this->option('no-backup');

        $this->info('=== SiparisUrunleri Duplicate Temizleme ===');
        $this->newLine();

        // ===== Rapor =====
        $totalRows = DB::connection('mysql')->table('SiparisUrunleri')->count();

        $bosCount = DB::connection('mysql')->table('SiparisUrunleri')
            ->where(function ($q) {
                $q->whereNull('StokKodu')
                    ->orWhere('StokKodu', '')
                    ->orWhereNull('Miktar')
                    ->orWhere('Miktar', 0);
            })
            ->count();

        $dupSilinecek = (int) DB::connection('mysql')->select("
            SELECT COALESCE(SUM(Adet - 1), 0) AS A FROM (
                SELECT COUNT(*) AS Adet FROM SiparisUrunleri
                WHERE Miktar > 0 AND StokKodu IS NOT NULL AND StokKodu != ''
                GROUP BY SiparisID, StokKodu, Miktar, BirimFiyat, Tutar
                HAVING COUNT(*) > 1
            ) sub
        ")[0]->A;

        $etkilenen = (int) DB::connection('mysql')->select("
            SELECT COUNT(DISTINCT SiparisID) AS A FROM (
                SELECT SiparisID FROM SiparisUrunleri
                WHERE Miktar > 0 AND StokKodu IS NOT NULL AND StokKodu != ''
                GROUP BY SiparisID, StokKodu, Miktar, BirimFiyat, Tutar
                HAVING COUNT(*) > 1
            ) sub
        ")[0]->A;

        $beklenen = $totalRows - $bosCount - $dupSilinecek;

        $this->table(
            ['Metrik', 'Deger'],
            [
                ['Mevcut toplam satir', number_format($totalRows)],
                ['Bos/sifir satir (silinecek)', number_format($bosCount)],
                ['Duplicate satir (silinecek)', number_format($dupSilinecek)],
                ['Etkilenen siparis sayisi', number_format($etkilenen)],
                ['Temizlik sonrasi beklenen', number_format($beklenen)],
            ]
        );

        if ($dupSilinecek === 0 && $bosCount === 0) {
            $this->info('Temizlenecek bir sey yok. Tablo zaten temiz.');
            return 0;
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('--dry-run aktif. Hicbir sey silinmedi.');
            return 0;
        }

        if (!$force && !$this->confirm("Devam etmek istiyor musun?", false)) {
            $this->warn('Iptal edildi.');
            return 1;
        }

        // ===== Backup =====
        if (!$noBackup) {
            $backupTable = 'backup_siparisurunleri_' . date('Ymd_His');
            $this->newLine();
            $this->info("Backup olusturuluyor: $backupTable");
            DB::connection('mysql')->statement("CREATE TABLE `$backupTable` AS SELECT * FROM SiparisUrunleri");
            $backupCount = DB::connection('mysql')->table($backupTable)->count();
            $this->info("Backup OK: $backupCount satir kopyalandi.");
        }

        // ===== Temizlik =====
        DB::connection('mysql')->beginTransaction();
        try {
            $bosSilindi = DB::connection('mysql')->delete("
                DELETE FROM SiparisUrunleri
                WHERE StokKodu IS NULL OR StokKodu = '' OR Miktar IS NULL OR Miktar = 0
            ");

            $dupSilindi = DB::connection('mysql')->delete("
                DELETE su1 FROM SiparisUrunleri su1
                INNER JOIN SiparisUrunleri su2
                    ON su1.SiparisID  <=> su2.SiparisID
                   AND su1.StokKodu   <=> su2.StokKodu
                   AND su1.Miktar     <=> su2.Miktar
                   AND su1.BirimFiyat <=> su2.BirimFiyat
                   AND su1.Tutar      <=> su2.Tutar
                   AND su1.Id         > su2.Id
            ");

            DB::connection('mysql')->commit();

            $this->newLine();
            $this->info("Bos satir silindi:      $bosSilindi");
            $this->info("Duplicate silindi:      $dupSilindi");

            // ===== Dogrulama =====
            $yeniToplam = DB::connection('mysql')->table('SiparisUrunleri')->count();
            $kalan = (int) DB::connection('mysql')->select("
                SELECT COUNT(*) AS A FROM (
                    SELECT 1 FROM SiparisUrunleri
                    GROUP BY SiparisID, StokKodu, Miktar, BirimFiyat, Tutar
                    HAVING COUNT(*) > 1
                ) sub
            ")[0]->A;

            $this->newLine();
            $this->table(
                ['Sonuc', 'Deger'],
                [
                    ['Yeni toplam satir', number_format($yeniToplam)],
                    ['Toplam silinen', number_format($totalRows - $yeniToplam)],
                    ['Hala kalan duplicate', $kalan === 0 ? 'YOK ✓' : "VAR: $kalan"],
                ]
            );

            if (!$noBackup) {
                $this->newLine();
                $this->comment("Backup tablosu: $backupTable");
                $this->comment("1-2 hafta test ettikten sonra: DROP TABLE $backupTable;");
            }

        } catch (\Exception $e) {
            DB::connection('mysql')->rollBack();
            $this->error('HATA! ROLLBACK yapildi: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
