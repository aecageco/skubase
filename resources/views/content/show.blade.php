@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <h1>Item: {{$item->sku}}</h1>
@stop

@section('content')
    <table class="table table-hover">
        <tr>
            <td>Item</td><td><a href="/items/{{$item->id}}">{{$item->sku}}</a></td></tr>
        <tr>
            <td>UPC</td><td>{{$item->upc}}</td></tr>

<tr><td>Display Name</td>
    <td>{{$item->display_name}}</td></tr>
        <tr><td>Short Description</td><td>{{$item->short_description}}</td></tr>
        <tr><td>Long Description</td> <td>{{$item->detailed_description}}</td>
        <tr><td>Web Site Link</td> <td><a href="https://www.aecageco.com/Products/{{$item->url}}" target="_new">Web</a></td>
        </tr>

</table>

@stop

@section('css')
    {{-- Add here extra stylesheets --}}
    {{-- <link rel="stylesheet" href="/css/admin_custom.css"> --}}
@stop

@section('js')
    <script> console.log("Hi, I'm using the Laravel-AdminLTE package!"); </script>
@stop
