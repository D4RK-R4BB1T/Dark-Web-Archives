@extends('master.main')

@section('title', 'Admin Panel')

@section('content')

    <div class="row">
        <div class="col-md-3">
            @include("includes.admin.menu")
        </div>
        <div class="col-md-9">

            @yield("admin-content")
        </div>

    </div>


@stop
