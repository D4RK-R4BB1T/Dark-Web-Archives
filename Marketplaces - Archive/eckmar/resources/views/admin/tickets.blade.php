@extends('master.admin')

@section('admin-content')
    <div class="row mb-4">
        <div class="col">
            <h3>
                All Tickets
            </h3>
        </div>
    </div>

    <div class="row mt-1 mb-3">
        <div class="col">
            <form action="{{route('admin.tickets.remove')}}" method="post">
                {{csrf_field()}}
                <button type="submit" class="btn btn-outline-info" name="type" value="solved">Remove solved tickets</button>
                <button type="submit" class="btn btn-outline-info" name="type" value="all">Remove all tickets</button>

                <div class="input-group mb-3 mt-2">
                    <input type="text" class="form-control" placeholder="Older than (Days)" name="days" aria-label="Days" aria-describedby="basic-addon2">
                    <div class="input-group-append">
                        <button class="btn btn-outline-info" type="submit" name="type" value="orlder_than_days">Remove all</button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Title</th>
            <th>Opened by</th>
            <th>Time</th>
        </tr>
        </thead>
        <tbody>
        @foreach($tickets as $ticket)
            <tr>
                <td>
                    <a href="{{ route('admin.tickets.view', $ticket) }}" class="mt-1">{{ $ticket -> title }}</a>
                    @if($ticket -> solved)
                    <span class="badge badge-success">Solved</span>
                    @else
                        @if($ticket -> answered)
                            <span class="badge badge-warning">Answered</span>
                        @endif
                    @endif
                </td>
                <td>
                    <strong>{{ $ticket -> user -> username }}</strong>
                </td>
                <td>
                    <small>{{ $ticket -> time_passed }}</small>
                </td>

            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="text-center">
                {{ $tickets->links('includes.paginate') }}
            </div>
        </div>
    </div>



@stop