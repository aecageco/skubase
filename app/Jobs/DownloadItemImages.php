<?php

namespace App\Jobs;

use App\Models\Items;
use App\Services\SkuImageDownloader;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Batchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DownloadItemImages implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $tries = 3;
    public int $backoff = 60; // seconds

    public function __construct(public int $itemId) {}

    public function handle(SkuImageDownloader $svc): void
    {
        $item = Items::with('image')->find($this->itemId);
        if (!$item) return;
        $svc->downloadFromItem($item);
    }
}
