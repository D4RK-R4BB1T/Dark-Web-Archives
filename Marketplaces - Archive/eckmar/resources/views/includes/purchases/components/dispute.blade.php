{{-- Disputes --}}
<div class="col-md-12 mt-5 py-2" id="dispute">
    @if($purchase -> isDisputed())
        <h3 class="mb-1">Dispute</h3>
        <hr>
        @if(!$purchase -> dispute -> isResolved() && auth() -> user() -> isAdmin())
            <h5 class="mb-1">Resolve dispute</h5>
            <form action="{{ route('profile.purchases.disputes.resolve', $purchase) }}" class="form-inline"
                  method="POST">
                {{ csrf_field() }}
                <label for="winner" class="mr-2">Dispute winner:</label>
                <select name="winner" id="winner" class="form-control mr-2">
                    <option value="{{ $purchase -> buyer -> id }}">{{ $purchase -> buyer -> username }} -
                        buyer
                    </option>
                    <option value="{{ $purchase -> vendor -> id }}">{{ $purchase -> vendor -> user -> username }}
                        - vendor
                    </option>
                </select>
                <button type="submit" class="btn btn-outline-primary">Resolve dispute</button>
            </form>
        @elseif($purchase -> dispute -> isResolved())
            <h5 class="mb-1">Dispute resolved</h5>
            <p class="alert alert-success">Winner:
                <strong>{{ $purchase -> dispute -> winner -> username }}</strong></p>
        @endif


        @foreach($purchase -> dispute -> messages as $message)
            <div class="card my-2">
                <div class="card-body">
                    {{ $message -> message }}
                </div>
                <div class="card-footer text-muted">
                    {{ $message -> time_ago }} by <a
                            href="{{ route('vendor.show', $message -> author) }}">{{ $message -> author -> username }} {{ $purchase -> userRole($message -> author) }}</a>
                </div>
            </div>
        @endforeach

        @if(!$purchase -> dispute -> isResolved())
            <form action="{{ route('profile.purchases.disputes.message', $purchase -> dispute) }}"
                  method="POST">
                <div class="card my-2">
                    <div class="card-header">
                        <h5><label for="newmessage">New message:</label></h5>
                    </div>
                    <div class="card-body">
                                <textarea name="message" id="newmessage" class="form-control" id="message"
                                          rows="5"></textarea>
                    </div>
                    <div class="card-footer">
                        <button type="submit" class="btn btn-block btn-primary">Send message</button>
                    </div>
                </div>
                {{ csrf_field() }}
            </form>
        @endif

    @else
        <h3 class="mb-1">Initiate Dispute</h3>
        <hr>
        <p class="text-muted">If the described item does not match received item you can initiate dispute against seller. Once dispute is started, it can be resolved in favor of both buyer and vendor</p>
        <form method="POST" action="{{ route('profile.purchases.dispute', $purchase) }}">
            {{ csrf_field() }}
            <label for="message">Dispute message:</label>
            <textarea name="message" id="message" class="form-control" rows="5"
                      placeholder="Type the message for the dispute"></textarea>
            <button type="submit" class="btn btn-block mt-2  btn-danger">Submit dispute</button>
        </form>
    @endif
</div>