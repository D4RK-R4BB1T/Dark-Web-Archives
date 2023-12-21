@extends('master.admin')

@section('admin-content')
    <div class="row">
        <div class="col-md-12">
            @include('includes.flash.success')
            @include('includes.flash.invalid')
            @include('includes.flash.error')
            @include('includes.profile.ticket')
        </div>
    </div>
@stop