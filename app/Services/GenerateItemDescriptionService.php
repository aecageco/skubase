<?php
namespace App\Services;

use OpenAI\Laravel\Facades\OpenAI;

class GenerateItemDescriptionService
{
    public function run($item)
    {
        $sku = $item->sku;
        $url = $item->url ?? '';
        $assistantId = env('OPENAI_ASSISTANT_ID');
        $url="https://www.aecageco.com/Products/".$url;
        $thread = OpenAI::assistants()->threads()->create();

        OpenAI::assistants()->threads()->messages()->create($thread->id, [
            'role' => 'user',
            'content' => [
                ['type' => 'text', 'text' => "Generate description for SKU {$sku} from {$url}"]
            ],
        ]);

        $run = OpenAI::assistants()->threads()->runs()->create($thread->id, [
            'assistant_id' => $assistantId,
        ]);

        do {
            $status = OpenAI::assistants()->threads()->runs()->retrieve($thread->id, $run->id);
            sleep(1);
        } while ($status->status !== 'completed');

        $messages = OpenAI::assistants()->threads()->messages()->list($thread->id);
        $response = $messages->data[0]->content[0]->text->value ?? '';

        $item->description_ai = $response;
        $item->save();

        return $response;
    }
}
