@extends('master.profile')

@section('title')
    @yield('purchase-title')
@stop

@section('profile-content')

    <div class="row">
        <div class="col-md-12">
            @include('includes.flash.success')
            @include('includes.flash.error')
            @include('includes.validation')
            <h3 class="mb-3">@yield('purchase-title') - @include('includes.currency', ['usdValue' => $purchase -> value_sum ])</h3>
            <p class="text-muted">Created {{ $purchase -> timeDiff() }} - {{ $purchase -> created_at }}</p>
        </div>

    </div>

    @if($purchase->status_notification !== null)
        <div class="row">
            <div class="col">
                <div class="alert alert-danger">
                    {{$purchase->status_notification}}
                </div>
            </div>
        </div>
    @endif
    <div class="row">
        @include('includes.purchases.components.offer')
        @include('includes.purchases.components.delivery')
    </div>
    <div class="row">
        @include('includes.purchases.components.message')
        @include('includes.purchases.components.payment')
    </div>
    <div class="row">
        @include('includes.purchases.components.feedback')
    </div>
    <div class="row">
        @include('includes.purchases.components.dispute')
    </div>


@stop