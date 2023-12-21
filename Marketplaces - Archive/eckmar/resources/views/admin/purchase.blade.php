@extends('master.admin')

@section('title')
    Purchase Resolving - #{{ $purchase -> short_id }}
@stop

@section('admin-content')

    <div class="row">
        <div class="col-md-12">
            @include('includes.flash.success')
            @include('includes.flash.error')
            @include('includes.validation')
            <h3 class="mb-3">Purchase Resolving - #{{ $purchase -> short_id }} - @include('includes.currency', ['usdValue' => $purchase -> value_sum])</h3>
            <p class="text-muted">Created {{ $purchase -> timeDiff() }} - {{ $purchase -> created_at }}</p>
        </div>

    </div>
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
