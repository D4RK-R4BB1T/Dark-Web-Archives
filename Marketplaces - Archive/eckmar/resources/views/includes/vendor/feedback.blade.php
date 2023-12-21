<div class="row">
    <div class="col">
        <h5 class="mb-3">Feedback ratings</h5>
    </div>
    <div class="col text-md-right">
        <a href="{{route('vendor.show.feedback',['user'=>$vendor])}}">See all feedback</a>
    </div>
</div>

<div class="row">
    <div class="col-md-3 col-sm-6">
        <span>Quality:</span><br>
        <span>Communication:</span><br>
        <span>Shipping:</span>
    </div>
    <div class="col-md-4 col-sm-6">
        <span>
            @include('includes.purchases.stars', ['stars' => $vendor -> vendor -> roundAvgRate('quality_rate')]) ({{ $vendor -> vendor -> avgRate('quality_rate') }})
        </span>
        <span> <br>
            @include('includes.purchases.stars', ['stars' => $vendor -> vendor -> roundAvgRate('communication_rate')]) ({{ $vendor -> vendor -> avgRate('communication_rate') }})
        </span> <br>
        <span>
            @include('includes.purchases.stars', ['stars' => $vendor -> vendor -> roundAvgRate('shipping_rate')]) ({{ $vendor -> vendor -> avgRate('shipping_rate') }})
        </span>
    </div>
    <div class="col-md-5 mt-sm-3">

        <div class="row text-md-center">
            <div class="col-4">
                <span class="fas fa-plus-circle text-success"></span> {{$vendor->vendor->countFeedbackByType('positive')}}
                Positive
            </div>
            <div class="col-4">
                <span class="fas fa-stop-circle text-secondary"></span> {{$vendor->vendor->countFeedbackByType('neutral')}}
                Neutral
            </div>
            <div class="col-4">
                <span class="fas fa-minus-circle text-danger"></span> {{$vendor->vendor->countFeedbackByType('negative')}}
                Negative
            </div>
        </div>
    </div>


</div>

