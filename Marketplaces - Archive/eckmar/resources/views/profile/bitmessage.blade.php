@extends('master.profile')

@section('profile-content')


    <h1 class="my-3">Bitmessage</h1>
    <hr>
    <p class="my-3">Service to forward all your notifications from marketplace to your Bitmessage address</p>
    <p>Service staus: @if($enabled) <span class="badge badge-success">Enabled</span> @else <span class="badge badge-danger">Disabled</span> @endif</p>


    @if($user->bitmessage_address !== null)
    <div class="alert @if($enabled) alert-info @else alert-warning @endif">You have configured your Bitmessage address,@if($enabled) notifications will be forwareded @else however service is not currently enabled @endif </div>
    @else
        <div class="alert alert-warning">In order to forward notifications please configure your Bitmessage address</div>
    @endif


    @if($user->bitmessage_address == null)
        <h4>Add Bitmessage address</h4>
    @else
        <h4>Change Bitmessage address</h4>
        <p class="text-muted">Current address: {{$user->bitmessage_address}}</p>
    @endif
    <hr>
    @include('includes.flash.error')
    @include('includes.flash.success')
    @include('includes.flash.invalid')
    <form action="{{route('profile.bitmessage.sendcode')}}" method="post">
        {{csrf_field()}}
        <div class="form-group">
            <label for="address">Bitmessage address:</label>
            <input type="text" name="address" id="" class="form-control" id="address" value="@if(session()->has('bitmessage_confirmation')) {{session()->get('bitmessage_confirmation')['address']}} @endif">
        </div>
        <div class="form-group">
            @if(session()->has('bitmessage_confirmation'))
                <button type="submit" class="btn btn-outline-secondary">Resend confirmation message</button>
                <p class="text-muted">You can request new confirmation message every {{config('bitmessage.confirmation_msg_frequency')}} {{str_plural('second',config('bitmessage.confirmation_msg_frequency'))}}</p>
            @else
                <button type="submit" class="btn btn-outline-primary">Send confirmation message</button>
            @endif
        </div>
    </form>

    @if(session()->has('bitmessage_confirmation'))
        <div class="row">
            <div class="col-md-6">
                <form action="{{route('profile.bitmessage.confirmcode')}}" method="post">
                    {{csrf_field()}}
                    <div class="form-group">
                        <label for="code">Confirmation code</label>
                        <input type="text" name="code" id="" class="form-control">
                    </div>
                    <div class="form-group">
                        <button type="submit" class="btn btn-outline-primary">Confirm address</button>
                    </div>
                </form>
            </div>
        </div>
    @endif


@stop