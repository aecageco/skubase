<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use App\Models\Items;
use App\Jobs\GenerateItemDescription;

class QueueAssistantJob extends Command
{
    protected $signature = 'assistant:queue-all {--limit=0} {--only-missing}';
    protected $description = 'Queue AI description jobs for many items';

    public function handle(): int
    {
        $limit = (int)$this->option('limit');
        $onlyMissing = (bool)$this->option('only-missing');
        $skus=\App\Models\ProductMeta::on('sqlite2')->pluck('sku');
        $q = Items::whereIn('sku',$skus)->with('meta');
        // Example filters. Adjust as needed.
        if ($onlyMissing) {
            $q->whereNull('description_ai'); // or skip if file already exists below
        }
        if ($limit > 0) {
            $q->limit($limit);
        }

        $count = 0;

        $q->orderBy('id')->chunkById(500, function ($items) use (&$count) {
            $batch = [];
            foreach ($items as $item) {
                $sku = (string)$item->sku;
                if ($sku === '') continue;

                // Skip if file already written
                $path = "ai_descriptions/{$sku}.md";
                if (Storage::disk('local')->exists($path)) continue;

                $batch[] = new GenerateItemDescription($item->id);
                $count++;
            }

            if ($batch) {
                Bus::batch($batch)->name('GenerateItemDescriptions')->dispatch();
            }
        });

        $this->info("Queued {$count} jobs.");
        $this->info('Run: php artisan queue:work');
        return self::SUCCESS;
    }
}
