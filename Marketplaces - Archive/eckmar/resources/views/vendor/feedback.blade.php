@extends('master.main')

@section('title','Vendor - ' . $vendor -> username )

@section('content')
    {{-- Breadcrumbs --}}
    <nav class="main-breadcrumb" aria-label="breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item" aria-current="page">{{ config('app.name') }}</li>
            <li class="breadcrumb-item" aria-current="page">Vendor</li>
            <li class="breadcrumb-item" aria-current="page"><a href="{{route('vendor.show',['user'=>$vendor])}}">{{ $vendor -> username }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Feedback</li>
        </ol>
    </nav>


    <div class="row">
        <div class="col">
            <h4> Feedback profile - {{$vendor->username}}</h4>
            @if($vendor->vendor->isTrusted())
                <p class="badge badge-success">Trusted vendor <span class="fa fa-check-circle"></span></p>
            @endif
            @if($vendor->vendor->isDwc())
                <p class="badge badge-danger">Deal with caution <span class="fa fa-exclamation-circle"></span></p>
            @endif
            <hr>
        </div>
    </div>

    @include('vendor.feedback.stats')
    @include('vendor.feedback.detailed')

@stop