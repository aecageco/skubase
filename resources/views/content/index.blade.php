@extends('adminlte::page')

@section('title', 'Content')

@section('content_header')

    <h1>Content</h1>
@stop

@section('content')
<table class="table">
    <tr>
        <th>SKU</th>
        <th>Item Name</th>
        <th>Item Name</th>
        <th>Bullets</th>
        <th>Features</th>
        <th>Short Description</th>
        <th>Long Description</th>

    </tr>
    @foreach ($contents as $content)
        <tr>
            <td><a href="/items/{{$content->item->id}}">{{$content->SKU}}</a></td>
            <td>{{$content->item->display_name}}</td>
            <td>{{ $content->item?->upc}}</td>
            <td>{{$content->bullets}}</td>
            <td>{{$content->feature_bullets}}</td>
            <td>{{$content->short_plain}}</td>
            <td>{{$content->long_plain}}</td>


            <td><a href="https://www.aecageco.com/Products/{{$content->item->url}}" target="_new">Web</a></td>
        </tr>
    @endforeach
</table>
{{ $contents->links('pagination::bootstrap-4') }}
@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop
