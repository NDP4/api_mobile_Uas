<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class StorageLinkCommand extends Command
{
    /**
     * The console command signature.
     *
     * @var string
     */
    protected $signature = 'storage:link';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a symbolic link from "public/storage" to "storage/app/public"';

    /**
     * Execute the console command.
     *
     * @return void
     */
    public function handle()
    {
        $target = storage_path('app/public');
        $link = public_path('storage');

        // Buat direktori storage jika belum ada
        if (!file_exists(storage_path('app'))) {
            mkdir(storage_path('app'), 0755, true);
        }

        // Buat direktori public jika belum ada
        if (!file_exists($target)) {
            mkdir($target, 0755, true);
        }

        // Hapus symlink lama jika ada
        if (is_link($link)) {
            unlink($link);
        }

        // Hapus direktori storage di public jika ada
        if (is_dir($link)) {
            rmdir($link);
        }

        try {
            // Buat symlink baru
            if (symlink($target, $link)) {
                // Set permission yang benar
                chmod($target, 0755);
                $this->info('The [public/storage] directory has been linked.');
            } else {
                $this->error('Failed to create symbolic link.');
            }
        } catch (\Exception $e) {
            $this->error('Failed to create symbolic link: ' . $e->getMessage());
        }
    }
}
