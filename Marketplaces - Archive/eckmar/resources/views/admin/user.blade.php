@extends('master.admin')

@section('admin-content')
    <div class="row">
        <div class="col">
            <h4>
                Displaying details for user {{$user->username}}
            </h4>
            <hr>
        </div>
    </div>

    @include('includes.flash.success')
    @include('includes.flash.error')
    @include('includes.flash.invalid')
    <div class="card mt-3">
        <form action="{{route('admin.user.edit.info',$user->id)}}" method="post">
            {{csrf_field()}}
            <div class="card-header">
                Basic information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">

                        <div class="form-group">
                            <label for="id">ID:</label>
                            <input type="text" name="id" id="id" class="form-control" value="{{$user->id}}" readonly>
                        </div>

                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" name="username" id="username" class="form-control"
                                   value="{{$user->username}}" >
                        </div>


                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="referral_code">Referral code:</label>
                            <input type="text" name="referral_code" id="referral_code" class="form-control"
                                   value="{{$user->referral_code}}" >
                        </div>

                        <div class="form-group">
                            <label for="last_login">Last login:</label>
                            <input type="text" name="last_login" id="last_login" class="form-control"
                                   value="{{$user->lastSeenForHumans()}}" readonly>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-outline-secondary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>


    <div class="card mt-3">
        <form action="{{route('admin.user.edit.group',$user->id)}}" method="post">

            {{csrf_field()}}
            <div class="card-header">
                User Groups
            </div>

            <div class="card-body">

                <div class="row">

                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" name="administrator" type="checkbox" value="adminChecked"
                                   id="administratorCheck"
                                   @if($user->isAdmin()) checked @endif>
                            <label class="form-check-label" for="administratorCheck">
                                Administrator
                            </label>
                        </div>
                        <small class="form-text text-muted">Can access Admin Panel without the restrictions. Can change
                            User group
                        </small>
                    </div>
                    <div class="col-md-4">
                        Panel Permissions

                        @foreach(\App\User::$permissions as $permission)
                        <div class="form-check">
                            <label class="form-check-label">
                                <input class="form-check-input" @if($user -> hasPermission($permission)) checked @endif name="permissions[]" type="checkbox" value="{{ $permission }}"
                                       id="moderatorCheck">
                                {{ \App\User::$permissionsLong[$permission] }}
                            </label>
                        </div>
                        @endforeach

                        <small class="form-text text-muted">Limited access to Admin Panel. Mainly resolves disputes
                        </small>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check">
                            <input class="form-check-input" name="vendor" type="checkbox" value="vendorChecked"
                                   id="vendorCheck"
                                   @if($user->isVendor()) checked @endif>
                            <label class="form-check-label" for="vendorCheck">
                                Vendor
                            </label>
                        </div>
                        <small class="form-text text-muted">Give or take away user's ability to add new products</small>

                        <div class="form-check">
                            <input class="form-check-input" name="canUseFe" type="checkbox" value="feChecked"
                                   id="vendorCheck"
                                   @if(!$user->isVendor() || !\App\Marketplace\Payment\FinalizeEarlyPayment::isEnabled()) disabled @else  @if($user->vendor->canUseFe())checked  @endif @endif>
                            <label class="form-check-label" for="canUseFe">
                                Finalize Early Access @if(!\App\Marketplace\Payment\FinalizeEarlyPayment::isEnabled()) feature not present @endif
                            </label>
                        </div>

                    </div>


                </div>

            </div>

            <div class="card-footer">
                <button type="submit" class="btn btn-outline-secondary">
                    Save Changes
                </button>
            </div>
        </form>
    </div>



    <div class="card mt-3">
        <div class="card-header">
            Vendor stats
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4 text-center">
                    <p>{{$user->products()->count()}}</p>
                    <h6>Total Products</h6>
                </div>
                <div class="col-md-4 text-center">
                    <p>{{$user->purchases()->count()}}</p>
                    <h6>Total Sales</h6>
                </div>
            </div>
        </div>

        <div class="card-footer">
            <a href="{{route('admin.products',['user'=>$user->id])}}" class="btn btn-outline-secondary">View user's products</a>
        </div>
    </div>

    <div class="card mt-3" id="bans">
        <div class="card-header">
            Bans
        </div>
        <div class="card-body">
            @if($user->bans->isEmpty())
                <div class="alert alert-info">List of bans is empty.</div>
            @else
                @foreach($user->bans as $ban)
                    <div class="row my-1">
                        <div class="col-md-9 text-left text-muted">
                            Banned until <strong>{{ $ban -> until }}</strong> ({{ \Carbon\Carbon::parse($ban->until)->diffForHumans() }})
                        </div>
                        <div class="col-md-3 text-right">
                            <a href="{{ route('admin.ban.remove', $ban) }}" class="btn btn-outline-danger">Remove ban</a>
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        <div class="card-footer">
            <form class="form-inline" method="POST" action="{{ route('admin.user.ban', $user) }}">
                <label for="days">Ban user for number of days from now:</label>
                <input type="number" name="days" id="days" class="form-control mx-2" placeholder="Days">
                <input type="submit" class="btn btn-outline-danger" value="Ban">
                @csrf
            </form>
        </div>
    </div>


    @if($user -> isVendor())
        <div class="card mt-3" id="bans">
            <div class="card-header">
                Vendor purchase
            </div>
            <div class="card-body">
                <table class="table table-striped">
                    <thead>
                    <tr>
                        <th>Coin</th>
                        <th>Address</th>
                        <th>Paid</th>
                        <th>Balance</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($user -> vendorPurchases as $depositAddress)
                        <tr>
                            <td>
                                <span class="badge badge-info">{{ strtoupper($depositAddress -> coin) }}</span>
                            </td>
                            <td>
                                <input type="text" readonly class="form-control" value="{{ $depositAddress -> address }}"/>
                            </td>
                            <td class="text-right">
                                <span class="badge badge-primary">{{ $depositAddress -> amount }}</span>
                            </td>
                            <td class="text-right">
                                @if($depositAddress -> isEnough())
                                    <span class="badge badge-success">Enough funds</span>
                                @endif
                                <span class="badge badge-info">{{ $depositAddress -> balance }}</span>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>
        </div>
    @endif


@stop