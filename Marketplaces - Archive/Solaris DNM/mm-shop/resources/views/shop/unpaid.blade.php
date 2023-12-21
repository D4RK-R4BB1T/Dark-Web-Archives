{{-- 
This file is part of MM2-dev project. 
Description: Unpaid shop page
--}}
@extends('layouts.master')

@section('title', 'Системная информация')

@section('content')
    @include('layouts.components.sections-menu')
    @if (Auth::check() && Auth::user()->employee)
        @include('shop.management.components.sections-menu')
    @endif
    <div class="well block">
        <h3>Системная информация</h3>
        <hr class="small" />
        <p>Работа магазина временно приостановлена.</p>
    </div>
@endsection