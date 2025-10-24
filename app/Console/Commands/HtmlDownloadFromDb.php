<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\HtmlBatchDownloader;
use App\Models\ProductMeta;
use App\Models\Items;
use App\Jobs\HtmlDownloadChunkJob;

class HtmlDownloadFromDb extends Command
{
    protected $signature = 'html:download-db
        {--limit=0 : Max items to process}
        {--chunk=500 : DB chunk size}
        {--dir=pages : Storage dir}
        {--disk=local : Storage disk}
        {--queue : Dispatch jobs instead of inline}';

    protected $description = 'Download HTML for Items matched to ProductMeta SKUs';

    public function handle(HtmlBatchDownloader $downloader): int
    {
        $limit = (int)$this->option('limit');
        $chunk = (int)$this->option('chunk');
        $dir   = (string)$this->option('dir');
        $disk  = (string)$this->option('disk');
        $queue = (bool)$this->option('queue');

        $skusQuery = ProductMeta::query()->select('sku');
        $skuTotal  = (clone $skusQuery)->count();
        $this->info("Found {$skuTotal} SKUs.");

        $processed = 0;

        $skusQuery->orderBy('sku')->chunk($chunk, function ($skuRows) use (
            $limit, $dir, $disk, $queue, $downloader, &$processed
        ) {
            if ($limit && $processed >= $limit) return false;

            $skus = $skuRows->pluck('sku');

            $q = Items::query()->whereIn('sku', $skus)->orderBy('id');
            if ($limit) {
                $remaining = max(0, $limit - $processed);
                $q->limit($remaining);
            }

            $items = $q->get(['sku','url'])->map(fn ($i) => [
                'sku' => (string)$i->sku,
                'url' => (string)$i->url,
            ])->values()->all();

            if (empty($items)) return true;

            if ($queue) {
                HtmlDownloadChunkJob::dispatch($items, $disk, $dir);
                $this->line("Queued chunk of ".count($items));
            } else {
                $res = $downloader->download($items, $disk, $dir);
                $this->line("Processed ".count($items)."; log: storage/{$res['log']}");
            }

            $processed += count($items);
            return !($limit && $processed >= $limit);
        });

        $this->info("Done. Processed {$processed} items.");
        return self::SUCCESS;
    }
}
