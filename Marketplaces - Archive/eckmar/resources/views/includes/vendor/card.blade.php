<div class="card mt-5 profile-card border border-secondary" >
    <div class="card-body">

        <div class="row">
            <div class="col-sm-3">
                <h4><a href="{{ route('vendor.show', $vendor) }}" style="color: black">{{ $vendor -> username }}</a></h4>
                <p> <span class="btn @if($vendor->vendor->experience >= 0) btn-primary @else btn-danger @endif active" style="cursor:default">Level {{$vendor->vendor->getLevel()}}</span>
                    <span class="@if($vendor->vendor->experience < 0) text-danger @endif">({{$vendor->vendor->getShortXP()}} XP)</span></p>
                @if($vendor->vendor->isTrusted())
                    <p class="badge badge-success">Trusted vendor <span class="fa fa-check-circle"></span></p>
                @endif
                @if($vendor->vendor->isDwc())
                    <p class="badge badge-danger">Deal with caution <span class="fa fa-exclamation-circle"></span></p>
                @endif
            </div>
            <div class="col-sm-5 text-center">
                <p>
                    {{$vendor->vendor->about}}
                </p>
            </div>
            <div class="col-sm-4 text-right">
                <p>
                    <a href="{{ route('profile.messages').'?otherParty='.$vendor->username}}" class="btn btn-outline-secondary"><span class="fas fa-envelope"></span> Send message</a></p>
            </div>
        </div>
        <div class="row">
            <div class="col">
                <hr>
            </div>
        </div>
        <div class="row">
            <div class="col-sm-12">
                @include('includes.vendor.feedback')
            </div>
        </div>

    </div>
</div>