<h3 class="mb-2">Ticket: {{ $ticket -> title }}</h3>
<hr>
@if(!$ticket -> solved)
<form method="POST" action="{{ route('profile.tickets.message.new', $ticket) }}">
    {{ csrf_field() }}

    <div class="form-group">
        <label for="text"><h4>New ticket message:</h4></label>
        <textarea class="form-control" name="message" id="title" rows="5" placeholder="Enter ticket content"></textarea>
        <small class="form-text text-muted">Post new message!</small>
    </div>

    <div class="form-group text-right">

        @hasAccess('tickets')
        <a href="{{ route('admin.tickets.solve', $ticket) }}" class="btn btn-warning">
            Solve Ticket
        </a>
        @endhasAccess
        <button type="submit" class="btn btn-outline-success">
            Post message
            <i class="far ml-2 fa-comment-alt"></i>
        </button>
    </div>
</form>
@else
    <div class="alert text-center alert-success">
        This ticket is solved!
        @hasAccess('tickets')
        <a href="{{ route('admin.tickets.solve', $ticket) }}" class="btn btn-outline-danger btn-sm">Unsolve</a>
        @endhasAccess
    </div>
@endif
@foreach($replies as $reply)

    <div class="card my-2">
        <div class="card-body">
            <p class="card-text">
                {{ $reply -> text }}
            </p>
        </div>
        <div class="card-footer text-right py-1">
            <small><span class="text-muted">{{ $reply -> time_passed }}</span>
            by <strong>{{ $reply -> user -> username }}</strong></small>
        </div>
    </div>

@endforeach

{{ $replies-> links('includes/paginate') }}
