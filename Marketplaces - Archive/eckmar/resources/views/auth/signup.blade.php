@extends('master.main')


@section('title','Sign Up')

@section('content')

    <div class="row mt-5">
        <div class="col-md-6 offset-md-3">
            <h2>Sign Up</h2>


            <div class="mt-3">
                <form action="{{route('auth.signup.post')}}" method="post">
                    {{csrf_field()}}

                    <div class="form-group ">
                        <input type="text" class="form-control @if($errors->has('username')) is-invalid @endif" placeholder="Username" name="username" id="username">
                        @if($errors->has('username'))
                            <p class="text-danger">{{$errors->first('username')}}</p>
                        @endif
                    </div>
                    <div class="form-row">
                        <div class="col">
                            <input type="password" class="form-control @if($errors->has('password')) is-invalid @endif" placeholder="Password" name="password"
                                   id="password">
                        </div>
                        <div class="col">
                            <input type="password" class="form-control @if($errors->has('password')) is-invalid @endif" placeholder="Confirm Password"
                                   name="password_confirmation" id="password_confirm">
                        </div>

                    </div>
                    @if($errors->has('password'))
                        <p class="text-danger">{{$errors->first('password')}}</p>
                    @endif
                    <div class="form-group mt-1">
                        <span class="text-muted">
                            Your private key for decrypting messages will be protected with your password. Please make
                            sure that you choose a strong one
                        </span>
                    </div>
                    <div class="form-group">
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <div class="input-group-text">Referral Code</div>
                            </div>
                            <input type="text" name="refid" value="{{$refid}}" class="form-control"
                                   @if($refid !== '') readonly @endif>
                        </div>

                    </div>
                    @include('includes.captcha')
                    @if($errors->has('captcha'))
                        <p class="text-danger">{{$errors->first('captcha')}}</p>
                    @endif
                    <div class="form-group text-center">
                        <div class="row">
                            <div class="col-xs-12 col-md-4 offset-md-4">
                                <button type="submit" class="btn btn-outline-primary btn-block">Sign Up</button>
                            </div>
                        </div>
                    </div>
                    <div class="form-group text-center">
                        <a href="{{route('auth.signin')}}" class="text-muted">Already have an account ?</a>
                    </div>
                </form>
            </div>

        </div>
    </div>


@stop