<div class="col-md-6">
    <h3 class="mb-2">Delivery</h3>
    @if($purchase -> shipping)
        <table class="table">
            <tr>
                <td>Shipping name:</td>
                <td>{{ $purchase -> shipping -> name }}</td>
            </tr>
            <tr>
                <td>Delivery time:</td>
                <td>{{ $purchase -> shipping -> duration }}</td>
            </tr>
            <tr>
                <td>Shipping price:</td>
                <td><strong>@include('includes.currency', ['usdValue' => $purchase -> shipping -> price])</strong></td>
            </tr>
        </table>
    @else
        {{-- If the buyer deposited enough sum --}}
        @if($purchase -> isBuyer() && $purchase -> enoughBalance())
            <p>Automatic delivery:</p>
            <textarea class="form-control disabled" readonly rows="10">{{ $purchase -> delivered_product }}</textarea>
        @elseif($purchase -> isBuyer())
            <div class="alert alert-warning">
                You must pay to address and the system will deliver you content here.
            </div>
        @endif
    @endif
</div>