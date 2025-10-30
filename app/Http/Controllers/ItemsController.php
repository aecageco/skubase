<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\ProductMeta;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Response;

class ItemsController extends Controller
{


    public function exportCsv($like, $fileText)
    {
//        $like="HB%";
//        $fileText="HappyBeaks";

        $skus  = \App\Models\ProductMeta::pluck('sku'); // from sqlite2
        $items = \App\Models\Items::orderBy('sku','ASC')->whereIn('sku', $skus)->where('SKU','like',$like)->get();

//        $items = \App\Models\Item::limit(100)->get([
//            'sku','name','price','weight'
//        ]);

        $filename = $fileText . '.csv';
        $handle = fopen('php://temp', 'r+');

        // write header
        fputcsv($handle, array_keys($items->first()->toArray()));

        // write rows
        foreach ($items as $row) {
            fputcsv($handle, $row->toArray());
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return Response::make($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$filename}",
        ]);
    }

    public function html(){
        $skus  = \App\Models\ProductMeta::pluck('sku'); // from sqlite2
        $items = \App\Models\Items::whereIn('sku', $skus)->get();
        $items=$items->toArray();
        $downloader = app(\App\Services\HtmlBatchDownloader::class);
        $result = $downloader->download($items);
    }
    public function missing(){
        $type="Missing";

        $items = \App\Models\Items::whereHas('approval', function ($q) {
            $q->where('status', 4);
        })->paginate(25);
        return view('items.index', compact('items','type'));
    }
    public function approved(){
        $type="Approved";

        $items = \App\Models\Items::whereHas('approval', function ($q) {
            $q->where('status', 1);
        })->paginate(25);
        return view('items.index', compact('items','type'));

    }
    public function rejected(){
        $type="Rejected";
        $items = \App\Models\Items::whereHas('approval', function ($q) {
            $q->where('status', 2);
        })->paginate(25);
        return view('items.index', compact('items','type'));

    }


    /**
     * Display a listing of the resource.
     */
    public function index()

    {                $type="All";

        $items = \App\Models\Items::where('status','=',1)->paginate(25);
        return view('items.index', compact('items','type'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(Items $item)
    {
        $item = Items::find($item)->first();

        if ($item->id) {
            $nextPost = Items::where('id', '>', $item->id)
                ->orderBy('id')
                ->first();

            if ($nextPost) {
                $nextRecordId = $nextPost->id;
                // You now have the ID of the next record
            } else {
                $nextRecordId = 0;
            }
        } else {
            $nextRecordId = 0;
        }

        if ($item->id) {
            $prevPost = Items::where('id', '<', $item->id)
                ->orderBy('id','desc')
                ->first();

            if ($prevPost) {
                $prevPostId = $prevPost->id;
                // You now have the ID of the next record
            } else {
                $prevPostId = 0;
            }
        } else {
            $prevPostId = 0;
        }
        return view('items.show', compact('item'),  compact('prevPostId', 'nextRecordId'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Items $items)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Items $items)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Items $items)
    {
        //
    }
}
