<?php

namespace App\Http\Controllers;

use App\Models\Items;
use App\Models\ProductMeta;
use Illuminate\Http\Request;

class ItemsController extends Controller
{
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

        $skus  = \App\Models\ProductMeta::pluck('sku'); // from sqlite2
        $items = \App\Models\Items::whereIn('sku', $skus)->paginate(25);
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
