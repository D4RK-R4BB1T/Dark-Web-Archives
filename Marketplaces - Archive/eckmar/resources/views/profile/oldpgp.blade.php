@extends('master.profile')

@section('profile-content')

    @include('includes.flash.error')

    <h1 class="mb-3">Your old PGP keys:</h1>
    <hr>
    @if($keys -> isNotEmpty())
        @foreach($keys as $pgp)
            <div class="form-group">
                <textarea class="form-control disabled" rows="10" style="resize: none;" disabled readonly>{{{ $pgp -> key }}}</textarea>
                <p class="text-muted">Used until {{ $pgp -> timeUntil() -> diffForHumans() }}.</p>
            </div>

        @endforeach
    @else
        <div class="alert-warning">You don't have previous PGP keys.</div>
    @endif


@stop