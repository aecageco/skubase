<?php

namespace App\Http\Controllers;
use App\Models\Items; // your Item model
use App\Services\SkuImageDownloader;
use Illuminate\Http\Request;

class ImageDownloadController extends Controller
{


    public function saveItemImages(int $itemId, SkuImageDownloader $svc)
    {
        $item = Items::with('image')->findOrFail($itemId);
        $paths = $svc->downloadFromItem($item);
        return response()->json(['saved' => $paths]);
    }
}
