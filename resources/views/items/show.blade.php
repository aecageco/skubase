@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    @if (session('status'))
        @php $color = session('status_type') === 'success' ? '#d4edda' : '#f8d7da'; @endphp
        <div style="background:{{ $color }}; padding:10px; margin-bottom:10px;">
            {{ session('status') }}
        </div>
    @endif
    <div style="display:flex; width:100%;">
        <div style="flex:1; width:70%; padding:10px;">   <h1>{{$item->display_name}} </h1><a href="https://www.aecageco.com/Products/{{$item->url}}" target="_new">View on Web</a> | @if ($prevPostId != 0)<a href="/items/{{$prevPostId}}">@endif Previous @if ($prevPostId != 0)</a>@endif | @if ($nextRecordId != 0)<a href="/items/{{$nextRecordId}}">@endif Next @if($nextRecordId != 0)</a>@endif
        </div>
        <div style="flex:1; width:30%; padding:10px;">
            <b>Approval Status</b>:
            @switch($item->approval->status)
                @case(0)
                    <span>Not Reviewed</span>
                    @break

                @case(1)
                    <span style="color:green;">Approved</span>
                    @break

                @case(2)
                    <span style="color:red;">Rejected</span>
                    @break

                @default
                    <span>Unknown</span>
            @endswitch
            @if ($item->approval->status == 2)<br><b>Rejected Reason:</b> {{$item->approval->reason}}@endif
            <br>
            <form
                action="{{ route('item.approve', $item->sku) }}"
                method="POST"
                onsubmit="return confirm('Are you sure?');"
                style="display:inline;"
            >
                @csrf
                <button
                    @if ($item->approval->status == 1)
                        disabled
                    style="padding:8px 16px; background:#cccccc; color:#fff; border:none; cursor:pointer;"
                    @else
                        style="padding:8px 16px; background:#007bff; color:#fff; border:none; cursor:pointer;"

                    @endif
                    type="submit"
                >
                    Approve Item
                </button>
            </form>

            <form id="rejectForm-{{ $item->id }}" action="{{ route('itemapproval.reject', $item->sku) }}" method="POST" style="display:inline;">
                @csrf
                <input type="hidden" name="reason" id="rejectReason-{{ $item->id }}">
                <button type="button"
                        @if ($item->approval->status == 2)
                            disabled
                        style="padding:8px 16px; background:#cccccc; color:#fff; border:none; cursor:pointer;"
                        @else
                            style="padding:8px 16px; background:#dc3545; color:#fff; border:none; cursor:pointer;"

                        @endif
                        onclick="rejectItem{{ $item->id }}()">
                    Reject
                </button>
            </form>

            <script>
                function rejectItem{{ $item->id }}() {
                    const r = prompt('Enter rejection reason:');
                    if (r === null) return; // cancel
                    const reason = r.trim();
                    if (!reason) { alert('Reason required'); return; }
                    document.getElementById('rejectReason-{{ $item->id }}').value = reason;
                    document.getElementById('rejectForm-{{ $item->id }}').submit();
                }
            </script>

        </div>
    </div>

@stop

@section('content')
    <table class="table table-hover table-bordered">
        <tr>
            <td>SKU</td><td colspan="3">{{$item->sku}}</td></tr>
        <tr>
            <td>UPC</td><td colspan="3">{{$item->upc}}</td></tr>

<tr><td>Display Name</td>
    <td colspan="3">{{$item->display_name}}</td></tr>
       <tr> <td>Bullets</td><td colspan="3">@if($item->content != null){{$item->content->bullets}}@endif</td></tr>
       <Tr> <td>Feature Bullets</td><td colspan="3">@if($item->content != null){{$item->content->feature_bullets}}@endif</td></tr>
        <tr><td style="width:15%">New Short Description</td> <td style="width:25%"> @if($item->content != null){!! $item->content->short_plain !!}@endif</td><td style="width:15%">Old Short Description</td><td>{!!$item->short_description !!}</td></tr>
        <tr><td>New Long Description</td> <td> @if($item->content != null){!! $item->content->long_plain !!}</td>@endif<td>Old Long Description</td> <td>{!! $item->detailed_description !!}</td>
        @if($item->image)
        @if($item->image->main)<tr><td>Main Image: </td><td colspan="3"><a href="https://977154.app.netsuite.com/{{$item->image->main}}" target="_new"><img style="width:250px;" src="https://977154.app.netsuite.com/{{$item->image->main}}"><br>https://977154.app.netsuite.com/{{$item->image->main}}</a></td></tr> @endif
        @if($item->image->alt1 != null)<tr><td>Alt Image 1: </td><td colspan="3"><a href="https://977154.app.netsuite.com/{{$item->image->alt1}}" target="_new"><img style="width:250px;" src="https://977154.app.netsuite.com/{{$item->image->alt1}}"><br>https://977154.app.netsuite.com/{{$item->image->alt1}}</a></td></tr>@endif
        @if($item->image->alt2 != null) <tr><td>Alt Image 2: </td><td colspan="3"><a href="https://977154.app.netsuite.com/{{$item->image->alt2}}" target="_new"><img style="width:250px;" src="https://977154.app.netsuite.com/{{$item->image->alt2}}"><br>https://977154.app.netsuite.com/{{$item->image->alt2}}</a></td></tr>@endif
        @if($item->image->alt3 != null) <tr><td>Alt Image 3: </td><td colspan="3"><a href="https://977154.app.netsuite.com/{{$item->image->alt3}}" target="_new"><img style="width:250px;" src="https://977154.app.netsuite.com/{{$item->image->alt3}}"><br>https://977154.app.netsuite.com/{{$item->image->alt3}}</a></td></tr>@endif
        @if($item->image->alt4 != null)<tr><td>Alt Image 4: </td><td colspan="3"><a href="https://977154.app.netsuite.com/{{$item->image->alt4}}" target="_new"><img style="width:250px;" src="https://977154.app.netsuite.com/{{$item->image->alt4}}"><br>https://977154.app.netsuite.com/{{$item->image->alt4}}</a></td></tr>@endif
            @endif
        @if($item->meta)
            @if($item->image->main)<tr><td>Meta Info: </td><td colspan="3"><table>
                        <tr><td>Weight</td><td>{{$item->meta->weight}}</td></tr>
                        <tr><td>Case Pack</td><td>None</td></tr>
                        <tr><td>UOM:</td><td>{{$item->meta->uom}}</td></tr>
                        <tr><td>MOQ:</td><td>{{$item->meta->moq}}</td></tr>
                        <tr><td>Country of Origin:</td><td>{{$item->meta->origin}}</td></tr>

                    </table></td></tr> @endif

        @endif
</table>

@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop
