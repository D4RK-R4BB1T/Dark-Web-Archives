@extends('master.main')

@section('title', 'Profile settings')

@section('content')

    <div class="row">
        <div class="col-md-3">
            @include("includes.profile.menu")
        </div>
<div class="col-md-9">
    @yield("profile-content")
</div>

</div>


@stop