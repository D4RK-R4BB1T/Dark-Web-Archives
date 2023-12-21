@extends('master.main')

@section('title','Vendor - ' . $vendor -> username )

@section('content')
    {{-- Breadcrumbs --}}
    <nav class="main-breadcrumb" aria-label="breadcrumb">
        <ol class="breadcrumb">

            <li class="breadcrumb-item" aria-current="page">{{ config('app.name') }}</li>
            <li class="breadcrumb-item" aria-current="page">Vendor</li>
            <li class="breadcrumb-item active" aria-current="page">{{ $vendor -> username }}</li>
        </ol>
    </nav>



    <div class="row">
        <div class="col-md-12 profile-bg {{$vendor->vendor->getProfileBg()}} rounded pt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    @include('includes.vendor.card')
                </div>
            </div>
        </div>
    </div>

    @include('includes.vendor.stats')
    @yield('vendor-content')
@stop