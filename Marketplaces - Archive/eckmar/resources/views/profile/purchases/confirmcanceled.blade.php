@extends('master.confirmation')

@section('confirmation-title', 'Mark the sale/purchase as canceled - ' . $sale-> short_id)

@section('confirmation-content')
    This action can't be undone! Confirm mark as canceled, of sale/purchase <strong>{{ $sale -> offer -> product -> name }}</strong> in quantity of <em>{{ $sale -> quantity }}</em>
    <br>
    Purchase ID: {{ $sale -> short_id }}

@endsection

@section('confirmation-back', $backRoute)
@section('confirmation-next', route('profile.purchases.canceled', $sale))