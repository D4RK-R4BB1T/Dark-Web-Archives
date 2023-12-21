@extends('master.profile')

@section('profile-content')
    @include('includes.flash.success')

    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-3">PGP keys</h1>
        </div>
        <div class="col-md-6">
            <h3 class="mb-3">Your pgp key</h3>
            <hr>

            @if(auth() -> user() -> hasPGP())
                <p>Your PGP key is:</p>
                <textarea class="disabled form-control" style="resize: none" rows="10" disabled>{{{ auth() -> user() -> pgp_key }}}</textarea>
            @else
                <div class="alert alert-warning text-center my-3">
                    You don't have PGP key set! Please set the PGP key in the form below.
                </div>
            @endif
            <p><a href="{{ route('profile.pgp.old') }}">Old PGP keys</a></p>

        </div>
        <div class="col-md-6">
            <h3 class="mb-3">Set new PGP key</h3>
            <hr>

            <form method="POST" action="{{ route('profile.pgp.post') }}">
                {{ csrf_field() }}
                <div class="form-group">
                    <label for="newpgp">New PGP key:</label>
                    <textarea name="newpgp" id="newpgp" style="resize: none" rows="10" class="form-control @error('newpgp', $errors) is-invalid @enderror"></textarea>
                    @error('newpgp', $errors)
                    <div class="invalid-feedback">
                        {{ $errors -> first('newpgp') }}
                    </div>
                    @enderror
                    <p class="text-muted">Paste your public PGP key here and later you'll need to confirm.</p>
                </div>
                <div class="form-group text-center">
                    <button class="btn btn-outline-success" type="submit">Add PGP</button>
                </div>

            </form>
        </div>
    </div>




@stop