@extends('master.profile')

@section('profile-content')
    @include('includes.flash.error')
    @include('includes.flash.success')

    <h1 class="my-3">Settings</h1>

    <h3 class="mt-4">Change password</h3>
    <hr>
    <form action="{{ route('profile.password.change') }}" method="POST" class="justify-content-between">
        {{ csrf_field() }}
        <div class="form-row my-2">
            <label for="old_password" class="col-form-label col-md-2">Old password:</label>
            <div class="col-md-10">
                <input type="password" class="form-control" id="old_password" name="old_password" placeholder="Type the old password">
            </div>
        </div>
        <div class="form-row my-2">
            <label for="new_password" class="col-form-label col-md-2">New password:</label>
            <div class="col-md-5">
                <input type="password" class="form-control @error('new_password', $errors) is-invalid @enderror" id="new_password" name="new_password" placeholder="Type new password">
            </div>
            <div class="col-md-5">
                <input type="password" class="form-control @error('new_password', $errors) is-invalid @enderror" id="new_password_confirmation" name="new_password_confirmation" placeholder="Confirm new password">
            </div>
        </div>
        <div class="form-row text-right justify-content-between">
            <div class="col-md-9 text-left">
                @error('new_password', $errors)
                    <p class="invalid-feedback d-block">{{ $errors -> first('new_password') }}</p>
                @enderror
            </div>
            <div class="col-md-3">
                <button class="btn btn-outline-success" type="submit">Change password</button>
            </div>
        </div>
    </form>
    @if(\App\Marketplace\Utility\CurrencyConverter::isEnabled())
        @include('multicurrency::changeform')
    @endif
    <h3 class="mt-4">Two Factor Authentication</h3>
    <hr>
    <div class="row">
        <div class="col-md-6">
            <label>2-Factor Authentication:</label>
        </div>
        <div class="col-md-6 text-right">
            <div class="btn-group" role="group" aria-label="Basic example">
                <a href="{{ route('profile.2fa.change', true) }}" class="btn @if(auth() -> user() -> login_2fa == true) btn-success @else btn-outline-success @endif">On</a>
                <a href="{{ route('profile.2fa.change', 0) }}" class="btn @if(auth() -> user() -> login_2fa == false) btn-danger @else btn-outline-danger @endif">Off</a>
            </div>
        </div>
    </div>

    <h3 class="mt-4">Referral link</h3>
    <hr>
    <div class="row">
        <div class="col-md-12">
            <input type="url" readonly class="form-control disabled" value="{{ route('auth.signup', auth() -> user() -> referral_code) }}">
            <p class="text-muted">Paste this address to other users who wants to sign up on the market!</p>
        </div>
    </div>

    <h3 class="mt-4">Payment Addresses</h3>
    <hr>

    <div class="row">
        <div class="col-md-12">
            <form action="{{ route('profile.vendor.address') }}" method="POST">
                {{ csrf_field() }}
                <div class="form-row">
                    <div class="col-md-6">
                        <input type="text" class="form-control form-control-lg d-flex" name="address" id="address" placeholder="Place your new address(pubkey) here">
                    </div>
                    <div class="col-md-2">
                        <select name="coin" id="coin" class="form-control form-control-lg d-flex">
                            <option>Coin</option>
                            @foreach(config('coins.coin_list') as $supportedCoin => $instance)
                                <option value="{{ $supportedCoin }}">{{ strtoupper(\App\Address::label($supportedCoin)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-block btn-success btn-lg">Change</button>
                    </div>
                </div>
            </form>
            <p class="text-muted">On this address you will receive payments from purchases! Funds will be sent to your most recent added address of coin!</p>


            @if(auth() -> user() -> addresses -> isNotEmpty())
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>Address</th>
                        <th>Coin</th>
                        <th class="text-right">Added</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach(auth() -> user() -> addresses as $address)
                        <tr>
                            <td>
                                <input type="text" readonly class="form-control" value="{{ $address -> address }}">
                            </td>
                            <td><span class="badge badge-info">{{ strtoupper($address -> coin) }}</span></td>
                            <td class="text-muted text-right">
                                {{ $address -> added_ago }}
                            </td>
                            <td class="text-right"><a href="{{ route('profile.vendor.address.remove', $address) }}" class="btn btn-danger"><i class="fa fa-trash mr-1"></i>Remove</a></td>
                        </tr>
                    @endforeach
                </tbody>

            </table>
            @else
                <div class="alert text-center alert-warning">You addresses list is empty!</div>
            @endif
        </div>
    </div>

@stop