<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use DOMDocument;
use DOMXPath;

class HtmlBatchDownloader
{
    /**
     * @param array $items Each item = ['sku' => '12345', 'url' => 'https://...']
     */
    public function download(array $items, string $disk = 'local', string $dir = 'pages'): array
    {
        Storage::disk($disk)->makeDirectory($dir);
        $log = [["sku","url","filename","status","http_code","bytes","error","saved_at"]];

        foreach ($items as $item) {
            $sku = trim($item['sku'] ?? '');
            $url = trim($item['url'] ?? '');
            $url="https://www.aecageco.com/Products/".$url;

            if ($sku === '' || $url === '') {
                $log[] = [$sku, $url, '', 'invalid', '', '', 'missing data', now()->toISOString()];
                continue;
            }

            $filename = "{$sku}.html";
            $path = "{$dir}/{$filename}";
            $status = 'ok'; $code = null; $bytes = 0; $error = null;

            try {
                $resp = Http::withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (compatible; LaravelBatch/1.0)',
                    'Accept' => 'text/html,application/xhtml+xml'
                ])
                    ->timeout(25)
                    ->retry(3, 1000)
                    ->get($url);

                $code = $resp->status();

                if (!$resp->successful()) {
                    $status = 'http_error';
                    $error = "HTTP {$code}";
                } else {
                    $html = $this->extractMainDiv($resp->body());
                    if (!$html) {
                        $status = 'not_found';
                        $error = 'main_div not found';
                    } else {
                        $bytes = strlen($html);
                        Storage::disk($disk)->put($path, $html);
                    }
                }
            } catch (\Throwable $e) {
                $status = 'exception';
                $error = $e->getMessage();
            }

            $log[] = [$sku, $url, $filename, $status, $code, $bytes, $error, now()->toISOString()];
        }

        $csvPath = "{$dir}/download_log_" . now()->format('Ymd_His') . ".csv";
        $csv = $this->toCsv($log);
        Storage::disk($disk)->put($csvPath, $csv);

        return ['saved_dir' => $dir, 'log' => $csvPath];
    }

    private function extractMainDiv(string $html): ?string
    {
        libxml_use_internal_errors(true);

        $doc = new DOMDocument();
        $doc->loadHTML($html);
        $xpath = new DOMXPath($doc);

        // Look for the specific class and style
        $divs = $xpath->query('//div[@class="main_div" and @style="width:565px;margin-top:15px;"]');
        if ($divs->length === 0) {
            return null;
        }

        $div = $divs->item(0);
        $inner = '';
        foreach ($div->childNodes as $child) {
            $inner .= $doc->saveHTML($child);
        }

        return trim($inner);
    }

    private function toCsv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        foreach ($rows as $row) { fputcsv($fh, $row); }
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv;
    }
}
