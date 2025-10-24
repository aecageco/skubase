<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\HtmlBatchDownloader;

class HtmlDownloadChunkJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public array $items;
    public string $disk;
    public string $dir;

    public function __construct(array $items, string $disk = 'local', string $dir = 'pages')
    {
        $this->items = $items;
        $this->disk  = $disk;
        $this->dir   = $dir;
        $this->onQueue('html-downloads'); // optional named queue
    }

    public function handle(HtmlBatchDownloader $downloader): void
    {
        $downloader->download($this->items, $this->disk, $this->dir);
    }
}
