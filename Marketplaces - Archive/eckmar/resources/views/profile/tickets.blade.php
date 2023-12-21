@extends('master.profile')

@section('content')

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-3">Support</h1>
            <hr>
            @include('includes.flash.error')
            @include('includes.flash.invalid')
        </div>

        <div class="col-md-3">
            <h3 class="mb-2">Tickets</h3>

            <a href="{{ route('profile.tickets') }}" class="btn btn-block @if($ticket) btn-outline-primary @else btn-primary @endif my-2">
                <i class="fas fa-plus-circle mr-2"></i>
                New ticket
            </a>

            @if(auth() -> user() -> tickets() -> exists())
                <div class="list-group flex-md-column flex-row nav-pills justify-content-sm-center">
                @foreach(auth() -> user() -> tickets as $currTicket)
                    <a href="{{ route('profile.tickets', $currTicket) }}" class="list-group-item list-group-item-action @if($currTicket == $ticket) active @endif">
                        {{ $currTicket -> title }}
                        @if($currTicket -> solved)
                            <span class="badge badge-success">Solved</span>
                        @else
                            @if($currTicket -> answered)
                                <span class="badge badge-warning">Answered</span>
                            @endif
                        @endif

                    </a>
                @endforeach
                </div>
            @else
                <div class="alert alert-warning text-center">
                    Your ticket list is empty!
                </div>
            @endif

        </div>
        <div class="col-md-9">
            @if($ticket)
                @include('includes.profile.ticket')
            @else
                @include('includes.profile.newticket')
            @endif
        </div>
    </div>

@stop