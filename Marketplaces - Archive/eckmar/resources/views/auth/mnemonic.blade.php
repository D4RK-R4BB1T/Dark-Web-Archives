@extends('master.main')


@section('title','Mnemonic')

@section('content')

    <div class="row mt-5">
        <div class="col-md-6 offset-md-3">
            <h2>Mnemonic</h2>


            <div class="mt-3">
                <div class="form-group">
                    <p>
                        This is your mnemonik key. It consists out of {{config('marketplace.mnemonic_length')}} words.
                        Please write
                        them down. This is the only time they will be shown to you, and without them you cannot recover
                        your account
                        in case you lose password.
                    </p>
                </div>
                <div class="form-group">
                    <textarea name="" id="" cols="30" rows="10" readonly class="form-control">{{$mnemonic}}</textarea>
                </div>
                <div class="form-group text-center">
                    <a href="{{route('auth.signin')}}" class="btn btn-warning">Proceed to Sign In</a>
                </div>
            </div>

        </div>
    </div>


@stop