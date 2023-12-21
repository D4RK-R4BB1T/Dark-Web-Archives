@extends('master.admin')

@section('admin-content')
    <div class="row">
        <div class="col">
            <h4>
                List of Users
            </h4>
            <hr>
        </div>
    </div>


    <div class="row mt-2">

        <div class="col">
            <form action="{{route('admin.users.query')}}" method="post" class="">
                {{csrf_field()}}
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Username:</label><br>
                            <input type="text" name="username" class="form-control" placeholder="Username of the user">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Order by:</label><br>
                            <select name="order_by" id="" class="form-control" title="Order by">
                                <option value="newest"
                                        @if(app('request')->input('order_by') =='newest') selected @endif>Newest
                                </option>
                                <option value="oldest"
                                        @if(app('request')->input('order_by') =='oldest') selected @endif>Oldest
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Display only: </label><br>
                            <select name="display_group" id="" class="form-control" title="Display group">
                                <option value="everyone"
                                        @if(request('display_group') =='everyone') selected @endif>
                                    Everyone
                                </option>
                                <option value="administrators"
                                        @if(request('display_group') =='administrators') selected @endif>
                                    Administrators
                                </option>
                                <option value="vendors"
                                        @if(request('display_group') =='vendors') selected @endif>Vendors
                                </option>
                                <option value="moderators"
                                        @if(request('display_group') =='moderators') selected @endif>Moderators
                                </option>
                            </select>
                        </div>
                    </div>

                    <div class="col-md-3">
                        <div class="form-group" style="margin-top:2em">
                            <button type="submit" class="btn btn-primary">Apply filter</button>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
    <div class="row">
        <div class="col">

        </div>
    </div>
    <table class="table">
        <thead>
        <tr>
            <th>Username</th>
            <th>Group</th>
            <th>Last Login</th>
            <th>Registration Date</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        @foreach($users as $user)
            <tr>
                <td>
                    {{$user->username}}
                </td>
                <td>
                    @if($user->getUserGroup()['badge'])
                        <span class="badge badge-{{$user->getUserGroup()['color']}}">{{$user->getUserGroup()['name']}}</span>
                    @else
                        {{$user->getUserGroup()['name']}}
                    @endif
                </td>
                <td>
                    {{$user->lastSeenForHumans()}}
                </td>
                <td>
                    {{$user->created_at}}
                </td>
                <td>
                    <a href="{{route('admin.users.view',['user'=>$user->id])}}" class="btn btn-secondary"> <i class="fas fa-search-plus"></i> View</a>
                </td>

            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="text-center">
                {{$users->links()}}
            </div>
        </div>
    </div>


@stop