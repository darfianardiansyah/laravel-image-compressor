<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class ClearConvertedImagesCommand extends Command
{
    protected $signature = 'conversions:clear {--hours=24 : Usia file dalam jam sebelum dihapus}';

    protected $description = 'Hapus file hasil konversi gambar yang sudah melewati batas waktu.';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $threshold = now()->subHours((int) $this->option('hours'))->getTimestamp();
        $deleted = 0;

        foreach ($disk->files('conversions') as $file) {
            if ($disk->lastModified($file) <= $threshold) {
                $disk->delete($file);
                $deleted++;
            }
        }

        $this->info("{$deleted} file hasil konversi dihapus.");

        return self::SUCCESS;
    }
}
