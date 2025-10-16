@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>@isset($type) {{$type}} @endisset Items</h1>
@stop

@section('content')
@if (request()->path()!='items/missing')

<table class="table">
    <tr>
        <th>SKU</th>
        <th>UPC</th>

        <th>Item Name</th>
        <th>Short Desc</th>
        <th>Long Desc</th>
        <th>Web</th>
    </tr>
    @foreach ($items as $item)
        <tr>
            <td><a href="/items/{{$item->id}}">{{$item->sku}}</a></td>
            <td>{{$item->upc}}</td>

            <td>{{$item->display_name}}</td>
            <td>{{$item->short_description}}</td>
            <td>{{$item->detailed_description}}</td>
            <td><a href="https://www.aecageco.com/Products/{{$item->url}}" target="_new">Web</a></td>
        </tr>
    @endforeach
</table>
Showing {{ $items->firstItem() }} to {{ $items->lastItem() }} of {{ $items->total() }} Items.
{{ $items->links('pagination::bootstrap-4') }}
@else
<h3>Coming Soon</h3>
@endif
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop
