<?php

namespace App\Http\Controllers;

use App\Models\ItemApproval;
use App\Models\Items;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ItemApprovalController extends Controller
{
    public function approve( $sku)
    {
        $user=Auth::user();
        $item=Items::where('sku','=',$sku)->first();
        $approval=$item->approval;
        $approval->status=1;
        $approval->user_id=Auth::user()->id;
        $approval->save();


        $nextId = Items::leftJoin('item_approvals as ia', 'ia.sku', '=', 'items.sku')
            ->where(function ($q) {
                $q->whereNull('ia.status')->orWhere('ia.status', 0);
            })
            ->where('items.id', '>', $item->id)
            ->orderBy('items.id')
            ->value('items.id');

        if ($nextId) {
            return redirect()->route('items.show', $nextId)
                ->with('status', 'Item '.$item->sku.' approved.')->with('status_type', 'success');
        }
        return redirect()->route('items.index')
            ->with('status', 'All items processed.');




    }
    public function reject($sku, \Illuminate\Http\Request $request)
    {
        $user=Auth::user();
        $item=Items::where('sku','=',$sku)->first();
        $approval=$item->approval;
        $approval->status=2;
        $approval->user_id=Auth::user()->id;
        $approval->reason=$request->reason;
        $approval->save();
        $nextId = \App\Models\Items::where('id', '>', $item->id)->min('id');
        return redirect('/items/'.$nextId)->with('status', 'Item '.$item->sku.' Rejected');

    }

}
