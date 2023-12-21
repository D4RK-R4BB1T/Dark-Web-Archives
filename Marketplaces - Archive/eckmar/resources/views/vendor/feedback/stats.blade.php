<div class="row mt-2">
    <div class="col-md-6">
        <h5>Recent feedback ratings </h5>



        <table class="table">
            <thead class="bg-light">
            <th>

            </th>
            <th>
                1 month
            </th>
            <th>
                6 months
            </th>
            <th>
                All time
            </th>
            </thead>
            <tbody>
            <tr>
                <td>
                    <span class="fas fa-plus-circle text-success"></span> Positive
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('positive',1)}}
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('positive',6)}}
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('positive')}}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="fas fa-stop-circle text-secondary"></span>  Neutral
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('neutral',1)}}
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('neutral',6)}}
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('neutral')}}
                </td>
            </tr>
            <tr>
                <td>
                    <span class="fas fa-minus-circle text-danger"></span> Negative
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('negative',1)}}
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('negative',6)}}
                </td>
                <td>
                    {{$vendor->vendor->countFeedbackByType('negative')}}
                </td>
            </tr>
            </tbody>
        </table>
    </div>


    <div class="col-md-6">
        <h5>Detailed vendor ratings</h5>
        <table class="table">
            <thead class="bg-light">
            <th>
                Criteria
            </th>
            <th>
                Average rating
            </th>
            <th>
                Score
            </th>
            </thead>
            <tbody>
            <tr>
                <td>
                    Quality
                </td>
                <td>
                    @include('includes.purchases.stars', ['stars' =>  $vendor -> vendor -> avgRate('quality_rate')])
                </td>
                <td>
                    ({{ $vendor ->vendor-> avgRate('quality_rate') }})
                </td>
            </tr>
            <tr>
                <td>
                    Communication
                </td>
                <td>
                    @include('includes.purchases.stars', ['stars' =>  $vendor -> vendor -> avgRate('communication_rate')])
                </td>
                <td>
                    ({{ $vendor ->vendor-> avgRate('communication_rate') }})
                </td>
            </tr>
            <tr>
                <td>
                    Shipping
                </td>
                <td>
                    @include('includes.purchases.stars', ['stars' =>  $vendor -> vendor -> avgRate('shipping_rate')])
                </td>
                <td>
                    ({{ $vendor ->vendor-> avgRate('shipping_rate') }})
                </td>
            </tr>

            </tbody>
        </table>
    </div>

</div>