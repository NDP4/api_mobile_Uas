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

        if (file_exists($link)) {
            return $this->error('The "public/storage" directory already exists.');
        }

        if (!file_exists($target)) {
            $this->laravel->make('files')->makeDirectory($target, 0755, true);
        }

        try {
            symlink($target, $link);
            $this->info('The [public/storage] directory has been linked.');
        } catch (\Exception $e) {
            $this->error('Failed to create symbolic link: ' . $e->getMessage());
        }
    }
}
