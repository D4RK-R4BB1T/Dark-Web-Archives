@extends('master.profile')

@section('title', 'Purchases')

@section('profile-content')
    @include('includes.flash.success')
    @include('includes.flash.error')

    <h1 class="mb-3">Message for purchase - {{ $purchase -> offer -> product -> name }} - @include('includes.currency', ['usdValue' => $purchase -> value_sum])</h1>
    @if($purchase -> shipping)
    <h3 class="mb-3">Shipping: {{ $purchase -> shipping -> name }} - {{ $purchase -> shipping -> duration }}</h3>
    @endif
    <p class="text-muted">{{ $purchase -> timeDiff() }}</p>

    <textarea rows="10" readonly class="form-control">{{ $purchase -> message }}</textarea>

@stop