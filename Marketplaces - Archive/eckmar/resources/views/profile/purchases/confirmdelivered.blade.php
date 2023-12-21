@extends('master.confirmation')

@section('confirmation-title', 'Mark the sale as sent - ' . $purchase-> short_id)

@section('confirmation-content')
    This action can't be undone! Confirm that <strong>{{ $purchase -> offer -> product -> name }}</strong> in quantity of <em>{{ $purchase -> quantity }}</em> was delivered to you!
    <br>
    Purchase ID: {{ $purchase -> short_id }}
@endsection

@section('confirmation-back', $backRoute)
@section('confirmation-next', route('profile.purchases.delivered', $purchase))