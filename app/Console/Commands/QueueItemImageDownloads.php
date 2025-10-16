<?php

namespace App\Console\Commands;

use App\Jobs\DownloadItemImages;
use App\Models\Items;
use Illuminate\Bus\Batch;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Throwable;

class QueueItemImageDownloads extends Command
{
    protected $signature = 'images:queue {--chunk=500} {--queue=images}';
    protected $description = 'Queue image downloads for all items needing images';

    public function handle(): int
    {
        $chunk = (int)$this->option('chunk');
        $queue = (string)$this->option('queue');

        $total = 0;
        $batches = [];

        Items::query()
            ->with('image') // eager load relationship
            ->whereNotNull('image_id') // adjust filter
            ->orderBy('id')
            ->chunkById($chunk, function ($items) use (&$total, &$batches, $queue) {
                $jobs = [];
                foreach ($items as $it) {
                    if ($it->relationLoaded('image') && $it->image !== null) {
                        $jobs[] = (new DownloadItemImages($it->id))->onQueue($queue);
                    }                }

                $batch = Bus::batch($jobs)
                    ->onQueue($queue)
                    ->name('DownloadItemImages '.now()->toDateTimeString())
                    ->allowFailures() // do not halt entire batch
                    ->dispatch();

                $batches[] = $batch->id;
                $total += count($jobs);
            });

        $this->info("Queued {$total} jobs across ".count($batches)." batches.");
        return self::SUCCESS;
    }
}
