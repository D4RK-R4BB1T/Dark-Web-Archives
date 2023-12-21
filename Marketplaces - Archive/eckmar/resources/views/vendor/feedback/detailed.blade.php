<div class="row mt-3">
    <table class="table">
        <thead>
            <th>
                Feedback
            </th>
            <th>
                From
            </th>
            <th>
                When
            </th>
        </thead>
        <tbody>
            @foreach($feedback as $fb)
                <tr>
                    <td>
                        <span>@include('includes.vendor.feedback_icon',$fb){{$fb->comment}}</span>@if($fb->isLowValue()) <span class="badge badge-warning">Low value</span> @endif<br>
                        <span class="text-muted">{{$fb->product_name}}</span>
                    </td>
                    <td>
                        <span>Buyer: {{$fb->getHiddenBuyerName()}}</span><br>
                        <span class="text-muted">US ${{$fb->product_value}}</span>
                    </td>
                    <td>
                        {{$fb->getLeftTime()}}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{$feedback->links()}}
</div>