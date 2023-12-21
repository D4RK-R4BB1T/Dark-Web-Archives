@extends('master.profile')

@section('title', 'Notifications')

@section('profile-content')
    @include('includes.flash.success')
    @include('includes.flash.error')

    <h1 class="mb-3">Notifications</h1>
    <hr>
    <p>List of your notifications. You can delete them any time</p>
    <form action="{{route('profile.notifications.delete')}}" method="post">
        {{csrf_field()}}
        <div class="form-group">
            <button type="submit" class="btn btn-outline-danger"><i class="fa fa-trash"></i> Delete notifications
            </button>
        </div>
    </form>
    <table class="table table-hover">
        <thead>
        <th>Notification</th>
        <th>Time</th>
        <th>Action</th>
        </thead>
        @foreach($notifications as $notification)
            <tr>
                <td>
                    {{$notification->description}}
                </td>
                <td>
                    {{$notification->created_at->diffForHumans()}}
                </td>
                <td>
                    @if($notification->getRoute() !== null )
                        <a href="{{route($notification->getRoute(),$notification->getRouteParams())}}" class="btn btn-outline-secondary"><i class="fa fa-eye"></i> View</a>
                    @else
                        None
                    @endif
                </td>
            </tr>

        @endforeach
    </table>
    <div class="mt-3">
        {{$notifications->links()}}
    </div>

@stop