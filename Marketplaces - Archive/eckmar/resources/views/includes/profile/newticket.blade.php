<h3 class="mb-2">Open new Support Ticket</h3>

<form action="{{ route('profile.tickets.new') }}" method="POST">
    {{ csrf_field()  }}
    <div class="form-group">
        <label for="title">Ticket title</label>
        <input type="text" name="title" class="form-control" id="title" aria-describedby="title" placeholder="Enter ticket title">
    </div>
    <div class="form-group">
        <label for="text">Ticket message:</label>
        <textarea class="form-control" name="message" id="title" rows="5" placeholder="Enter ticket content"></textarea>
        <small class="form-text text-muted">Describe your problem with the market!</small>
    </div>

    <div class="form-group text-right">
        <button type="submit" class="btn btn-outline-primary">
            Open ticket
            <i class="far ml-2 fa-plus-square"></i>
        </button>
    </div>
</form>

