<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class SkuImageDownloader
{
    public function __construct(
        private string $disk = 'public',
        private string $baseDir = 'images',
        private int $timeout = 30,
        private int $retries = 3,
        private int $retryDelayMs = 1000,
        private string $nsBaseUrl = 'https://977154.app.netsuite.com'
    ) {}

    /**
     * Downloads images for $item->sku from $item->image (main, alt1..altN).
     * Writes:
     *   - {sku}.images.json
     *   - {sku}.doc.csv
     * Filenames: SKU_01.ext, SKU_02.ext, ...
     * If >1 saved in this run and first index == 1, rename SKU_01.ext -> SKU_01.default.ext.
     */
    public function downloadFromItem(object $item, bool $writeDocs = true): array
    {
        @set_time_limit(0);

        $sku = trim((string)($item->sku ?? ''));
        if ($sku === '') {
            throw new \InvalidArgumentException('Item->sku missing');
        }

        $dir = $this->dirForSku($sku);
        $this->ensureDir($dir);

        $savedPaths = [];
        $imagesMeta = [];

        if (isset($item->image)) {
            $urls = $this->collectUrls($item->image);

            $nextIndex = $this->nextAvailableIndex($dir, $sku);
            $firstSavedThisRun = null;

            foreach ($urls as $slot => $url) {
                if (!$url) continue;

                $url = $this->prefixNsUrl($url);

                [$body, $mime] = $this->fetch($url);
                if (!$body) continue;

                $ext = $this->extFromMimeOrUrl($mime, $url);

                // Save as SKU_XX.ext
                $fname = $this->nameForIndex($sku, $nextIndex, $ext);
                $path  = "{$dir}/{$fname}";
                Storage::disk($this->disk)->put($path, $body);

                if ($firstSavedThisRun === null) {
                    $firstSavedThisRun = ['path' => $path, 'i' => $nextIndex, 'ext' => $ext];
                }

                $imagesMeta[] = [
                    'slot'       => $slot,
                    'file'       => $path,
                    'mime'       => $mime,
                    'bytes'      => strlen($body),
                    'source_url' => $url,
                    'saved_at'   => now()->toIso8601String(),
                ];
                $savedPaths[] = $path;

                $nextIndex++;
            }

            // Rename first file to SKU_01.default.ext if multiple saved this run and first index was 1
            if (count($savedPaths) > 1 && $firstSavedThisRun !== null && $firstSavedThisRun['i'] === 1) {
                $oldPath = $firstSavedThisRun['path']; // images/SKU/SKU_01.ext
                $newPath = $dir . '/' . $this->nameForIndexDefault($sku, 1, $firstSavedThisRun['ext']); // images/SKU/SKU_01.default.ext

                if (Storage::disk($this->disk)->exists($oldPath) && !Storage::disk($this->disk)->exists($newPath)) {
                    Storage::disk($this->disk)->move($oldPath, $newPath);

                    $idx = array_search($oldPath, $savedPaths, true);
                    if ($idx !== false) $savedPaths[$idx] = $newPath;

                    foreach ($imagesMeta as &$m) {
                        if ($m['file'] === $oldPath) {
                            $m['file'] = $newPath;
                            break;
                        }
                    }
                    unset($m);
                }
            }
        }

        // images manifest
        Storage::disk($this->disk)->put(
            "{$dir}/{$sku}.images.json",
            json_encode([
                'sku'          => $sku,
                'count'        => count($imagesMeta),
                'images'       => $imagesMeta,
                'generated_at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT)
        );

        // CSV doc
        if ($writeDocs) {
            $this->writeItemCsv($item, $dir);
        }

        return $savedPaths;
    }

    /* ------------------ docs ------------------ */

    private function writeItemCsv(object $item, string $dir): void
    {
        $sku   = (string)($item->sku ?? '');
        $name  = (string)($item->display_name ?? '');
        $long  = (string)($item->detailed_description ?? '');
        $url   = (string)($item->url ?? '');
        $upc   = (string)($item->upc ?? '');
        $short = (string)($item->short_description ?? '');

        $prefixedUrl = $this->prefixNsUrl($url);

        // meta fields (null-safe)
        $meta = $item->meta ?? (object)[];

        $msrp               = (string)($meta->msrp ?? '');
        $weight             = (string)($meta->weight ?? '');
        $origin             = (string)($meta->origin ?? '');
        $uom                = (string)($meta->uom ?? '');
        $item_dimensions    = (string)($meta->item_dimensions ?? '');
        $carton_dimensions  = (string)($meta->carton_dimensions ?? '');
        $carton_1_weight    = (string)($meta->carton_1_weight ?? '');
        $carton_2_dimensions= (string)($meta->carton_2_dimensions ?? '');
        $carton_2_weight    = (string)($meta->carton_2_weight ?? '');
        $carton_3_dimensions= (string)($meta->carton_3_dimensions ?? '');
        $carton_3_weight    = (string)($meta->carton_3_weight ?? '');

        $rows = [
            [
                'sku', 'display_name', 'detailed_description', 'url', 'upc', 'short_description',
                'msrp', 'weight', 'origin', 'uom', 'item_dimensions',
                'carton_dimensions', 'carton_1_weight',
                'carton_2_dimensions', 'carton_2_weight',
                'carton_3_dimensions', 'carton_3_weight'
            ],
            [
                $sku, $name, $long, $prefixedUrl, $upc, $short,
                $msrp, $weight, $origin, $uom, $item_dimensions,
                $carton_dimensions, $carton_1_weight,
                $carton_2_dimensions, $carton_2_weight,
                $carton_3_dimensions, $carton_3_weight
            ],
        ];

        $csv = $this->toCsv($rows);
        Storage::disk($this->disk)->put("{$dir}/{$sku}.doc.csv", $csv);
    }
    private function toCsv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        foreach ($rows as $r) fputcsv($fh, $r);
        rewind($fh);
        $csv = stream_get_contents($fh);
        fclose($fh);
        return $csv ?: '';
    }

    /* ------------------ internals ------------------ */

    private function collectUrls(object $imageModel): array
    {
        $slots = ['main'];
        for ($i = 1; $i <= 20; $i++) $slots[] = "alt{$i}";

        $out = [];
        foreach ($slots as $s) {
            if (isset($imageModel->{$s}) && is_string($imageModel->{$s})) {
                $url = trim($imageModel->{$s});
                if ($url !== '') $out[$s] = $url;
            }
        }
        return $out;
    }

    private function fetch(string $url): array
    {
        $resp = Http::withHeaders([
            'User-Agent' => 'LaravelImageFetcher/1.0',
            'Accept'     => 'image/*',
            'Referer'    => 'https://977154.app.netsuite.com/',
        ])
            ->retry($this->retries, $this->retryDelayMs)
            ->timeout($this->timeout)
            ->withOptions(['allow_redirects' => true])
            ->get($url);

        if (!$resp->successful()) {
            \Log::warning('Image download HTTP failure', ['url' => $url, 'status' => $resp->status()]);
            return ['', ''];
        }

        $body = $resp->body() ?? '';
        if ($body === '') {
            \Log::warning('Image download empty body', ['url' => $url]);
            return ['', ''];
        }

        $mime = $resp->header('Content-Type');
        $mime = $mime ? trim(strtok($mime, ';')) : null;

        if (!$mime || strpos($mime, 'image/') !== 0) {
            \Log::warning('Non-image content-type', ['url' => $url, 'content_type' => $mime]);
            return ['', ''];
        }

        return [$body, $mime];
    }

    private function extFromMimeOrUrl(string $mime, string $url): string
    {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png'  => 'png',
            'image/webp' => 'webp',
            'image/gif'  => 'gif',
            'image/bmp'  => 'bmp',
            'image/tiff' => 'tif',
            'image/avif' => 'avif',
            'image/heic' => 'heic',
        ];
        if (isset($map[$mime])) return $map[$mime];

        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext  = strtolower(pathinfo($path, PATHINFO_EXTENSION) ?: '');
        return $ext === 'jpeg' ? 'jpg' : ($ext ?: 'jpg');
    }

    private function prefixNsUrl(string $url): string
    {
        $u = trim($url);
        if ($u === '') return $u;
        if (preg_match('#^https?://#i', $u)) return $u;
        return rtrim($this->nsBaseUrl, '/') . '/' . ltrim($u, '/');
    }

    private function dirForSku(string $sku): string
    {
        return trim($this->baseDir, '/') . '/' . trim($sku, '/');
    }

    private function ensureDir(string $dir): void
    {
        if (!Storage::disk($this->disk)->exists($dir)) {
            Storage::disk($this->disk)->makeDirectory($dir);
        }
    }

    private function nextAvailableIndex(string $dir, string $sku): int
    {
        $files = Storage::disk($this->disk)->files($dir);
        $max = 0;
        foreach ($files as $f) {
            $base = basename($f);
            // Matches: SKU_01.ext or SKU_01.default.ext
            if (preg_match('/^'.preg_quote($sku,'/').'_([0-9]{2})(?:\.default)?\.[A-Za-z0-9]+$/', $base, $m)) {
                $n = (int)$m[1];
                if ($n > $max) $max = $n;
            }
        }
        return $max + 1;
    }

    private function nameForIndex(string $sku, int $i, string $ext): string
    {
        $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        return "{$sku}_{$nn}.{$ext}";
    }

    private function nameForIndexDefault(string $sku, int $i, string $ext): string
    {
        $nn = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        return "{$sku}_{$nn}.default.{$ext}";
    }
}
