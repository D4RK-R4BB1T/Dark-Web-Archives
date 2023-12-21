@extends('master.main')


@section('title','Verify login')

@section('content')

    <div class="row mt-5 justify-content-center">
        <div class="col-md-6 text-center">
            @include('includes.flash.error')

            <h2>Sign In Verify</h2>
            <div class="form-group">
                <label>Decrypt this message:</label>
                <textarea name="decrypt_message" class="form-control" rows="10" style="resize: none;" readonly>{{{ session() -> get('login_encrypted_message') }}}</textarea>
                <p class="text-muted">Decrypt this message and get validation string.</p>
            </div>
            <form method="POST" action="{{ route('auth.verify.post') }}" class="form-inline">
                {{ csrf_field() }}
                <label for="validation_string">Validation string:</label>
                <input type="text" class="form-control mx-2" required name="validation_string" id="validation_string"/>
                <button class="btn btn-outline-success">Login</button>

            </form>



        </div>
    </div>


@stop