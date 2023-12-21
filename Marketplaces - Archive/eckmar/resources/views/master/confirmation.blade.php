@extends('master.main')

@section('title','Home Page')

@section('content')
    @component('includes.modal')
        @slot('title')
            @yield('confirmation-title')
        @endslot

        @yield('confirmation-content')

        @slot('backRoute')
            @yield('confirmation-back')
        @endslot
        @slot('nextRoute')
            @yield('confirmation-next')
        @endslot

    @endcomponent
@stop