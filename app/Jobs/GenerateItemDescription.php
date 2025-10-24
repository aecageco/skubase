<?php

namespace App\Jobs;

use App\Models\Items;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Bus\Batchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class GenerateItemDescription implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    public int $itemId;

    /** seconds to wait for the run to complete */
    private int $runTimeout = 90;

    /** directory under the target disk */
    private string $outDir = 'ai_descriptions';

    /** filesystem disk from config/filesystems.php */
    private string $disk = 'local';

    public function __construct(int $itemId)
    {
        $this->itemId = $itemId;
    }

    public function handle(): void
    {
        $assistantId = (string) env('OPENAI_ASSISTANT_ID', '');
        if ($assistantId === '') {
            Log::error('OPENAI_ASSISTANT_ID missing');
            return;
        }

        $item = Items::with('meta')->find($this->itemId);
        if (!$item) {
            Log::warning("Item {$this->itemId} not found");
            return;
        }

        $sku = (string) $item->sku;
        if ($sku === '') {
            Log::warning("Item {$this->itemId} has empty SKU");
            return;
        }

        $url = (string) ($item->url ?? '');
        $url="https://www.aecageco.com/Products/".$url;

        try {
            // 1) Create a thread
            $thread = OpenAI::threads()->create();

            // 2) Add user message
            OpenAI::threads()->messages()->create($thread->id, [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => "Generate a clear, structured product description for SKU {$sku} and Use details from {$url} if reachable. "
                        ,
                    ],
                ],
            ]);

            // 3) Run with Assistant
            $run = OpenAI::threads()->runs()->create($thread->id, [
                'assistant_id' => $assistantId,
            ]);

            // 4) Poll for completion (bounded)
            $start = time();
            do {
                $status = OpenAI::threads()->runs()->retrieve($thread->id, $run->id);
                if ($status->status === 'completed') {
                    break;
                }
                if (in_array($status->status, ['failed', 'cancelled', 'expired'], true)) {
                    Log::error("Assistant run {$run->id} status={$status->status}");
                    return;
                }
                if ((time() - $start) > $this->runTimeout) {
                    Log::error("Assistant run {$run->id} timed out after {$this->runTimeout}s");
                    return;
                }
                sleep(1);
            } while (true);

            // 5) Fetch latest assistant message
            $messages = OpenAI::threads()->messages()->list($thread->id);
            $response = $this->extractAssistantText($messages->data);

            // 6) Save to file: storage/app/ai_descriptions/SKU.md
            $this->storeResponse($sku, $response);

            Log::info("Description generated for SKU {$sku}");
        } catch (\Throwable $e) {
            Log::error("GenerateItemDescription error for SKU {$sku}: {$e->getMessage()}", [
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }

    /** Find the first assistant message and return plain text */
    private function extractAssistantText(array $messages): string
    {
        foreach ($messages as $m) {
            if (($m->role ?? null) === 'assistant') {
                // Consolidate all text parts if present
                $parts = $m->content ?? [];
                $out = [];
                foreach ($parts as $p) {
                    if (($p->type ?? '') === 'text' && isset($p->text->value)) {
                        $out[] = (string) $p->text->value;
                    }
                }
                $text = trim(implode("\n\n", $out));
                if ($text !== '') return $text;
            }
        }
        return '';
    }

    /** Write response as Markdown to the configured disk */
    private function storeResponse(string $sku, string $content): void
    {
        $safe = preg_replace('/[^A-Za-z0-9_\-\.]/', '-', $sku) ?: 'unknown-sku';
        $path = "{$this->outDir}/{$safe}.md";

        Storage::disk($this->disk)->makeDirectory($this->outDir);
        Storage::disk($this->disk)->put($path, $content ?: '(no content)');
    }
}
