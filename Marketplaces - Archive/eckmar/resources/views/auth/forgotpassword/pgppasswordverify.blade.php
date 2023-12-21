@extends('master.main')


@section('title','Forgot Password')

@section('content')
    <div class="row mt-5 justify-content-center">
        <div class="col-md-6 text-center">
            @include('includes.flash.error')
            <h2>Reset password</h2>

            <div class="mt-3">
                <div class="form-group">
                    <p>Decrypt this message in order to get validation string:</p>
                    <textarea name="decrypt_message" class="form-control disabled" rows="8" style="resize: none;" disabled readonly>{{ session() -> get(\App\Marketplace\PGP::NEW_PGP_ENCRYPTED_MESSAGE) }}</textarea>
                </div>

                <form method="POST" action="{{ route('auth.resetpgp') }}">
                    {{ csrf_field() }}

                    <div class="form-group" style="display:flex">

                        <input type="text" class="form-control col-md-6" name="validation_string" id="validation_string" placeholder="Validation string"/>
                        @if($errors->has('validation_string'))
                            <p class="text-danger">{{$errors->first('validation_string')}}</p>
                        @endif
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <input type="password" class="form-control @if($errors->has('password')) is-invalid @endif" placeholder="New password" name="password"
                                   id="password">
                        </div>
                        <div class="col">
                            <input type="password" class="form-control @if($errors->has('password')) is-invalid @endif" placeholder="Confirm new password"
                                   name="password_confirmation" id="password_confirm">
                        </div>
                    </div>
                    @if($errors->has('password'))
                        <p class="text-danger">{{$errors->first('password')}}</p>
                    @endif


                    <div class="form-group text-center">
                        <div class="row">
                            <div class="col-xs-12 col-md-4 offset-md-4">
                                <button type="submit" class="btn btn-outline-primary btn-block" style="margin-top: 15px;">Reset password</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

        </div>
    </div>
@stop