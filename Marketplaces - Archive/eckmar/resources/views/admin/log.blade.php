@extends('master.admin')

@section('admin-content')
    <div class="row">
        <div class="col">
            <h4>
                Activity log of all administrators
            </h4>
            <hr>
            <p class="small text-muted">
                New logs are loaded  @if($cacheMinutes == 0) instantly @else every {{$cacheMinutes}} {{str_plural('minute',$cacheMinutes)}} @endif. You can change this option in configuration.
            </p>
        </div>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>User</th>
            <th>Type</th>
            <th>Description</th>
            <th>Performed on</th>
            <th>Time</th>
        </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
                <tr>
                    <td><a href="{{route('admin.users.view',$log->user->id)}}">{{$log->user->username}}</a></td>
                    <td>{{$log->type}}</td>
                    <td>{{$log->description}}</td>
                    <td><a href="{{$log->performedOn()['link']}}">{{$log->performedOn()['text']}}</a></td>
                    <td>{{$log->created_at}}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="text-center">
                {{$logs->links()}}
            </div>
        </div>
    </div>


@stop