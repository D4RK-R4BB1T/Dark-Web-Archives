@extends('master.profile')

@section('profile-content')
    @include('includes.flash.error')
    @include('includes.flash.success')
    @include('includes.validation')


    <h1 class="my-3">Decrypt messages</h1>
    <hr>
    <div class="row justify-content-center">
       <div class="col-md-12">
           <p>All your messages are encrypted. Please enter your password to unlock your decryption key and make messages viewable.</p>
       </div>
    </div>

    <div class="row justify-content-center">
        <div class="col-md-6">
            <form action="{{route('profile.messages.decrypt.post')}}" method="post">
                {{csrf_field()}}
                <div class="form-group">
                    <input type="password" name="password" class="form-control" >
                </div>
                <div class="form-group text-center">
                    <button class="btn  btn-outline-success" type="submit">Decrypt messages</button>
                </div>
            </form>
        </div>


    </div>



@stop