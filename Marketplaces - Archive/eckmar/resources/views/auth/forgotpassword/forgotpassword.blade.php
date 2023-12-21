@extends('master.main')


@section('title','Forgot Password')

@section('content')

    <div class="row mt-5 justify-content-center" >
        <div class="col-md-4 text-center">
            <h2>Forgot your password?</h2>
            <div class="alert alert-warning">
                Note that you will not be able to read messages encrypted by the key from previous password.
            </div>
            <div class="mt-3">
                <p>Please choose how to recover it</p>

                <form method="GET" action="/forgotpassword/pgp">
                    <div class="form-group text-center">
                        <div class="row">
                            <button type="submit" class="btn btn-outline-primary btn-block">PGP</button>
                        </div>
                    </div>
                </form>

                <form method="GET" action="/forgotpassowrd/mnemonic">
                    <div class="form-group text-center">
                        <div class="row">
                            <button type="submit" class="btn btn-outline-primary btn-block">Mnemonic</button>
                        </div>
                    </div>
                </form>

            </div>
        </div>
    </div>

@stop