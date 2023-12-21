<div class="col-md-6">
    <h3 class="mb-2">Offer</h3>
    <table class="table">
        <tr>
            <td>Purchased amount:</td>
            <td>
                <span class="badge badge-primary">{{ $purchase -> quantity }} {{ str_plural($purchase -> offer -> product -> mesure, $purchase -> quantity) }}</span>
            </td>
        </tr>
        <tr>
            <td>Price:</td>
            <td><strong>@include('includes.currency', ['usdValue' => $purchase -> offer -> price])</strong>
                per {{ $purchase -> offer -> product -> mesure }}</td>
        </tr>
        <tr>
            <td>Total:</td>
            <td><strong>@include('includes.currency', ['usdValue' => $purchase -> value_sum])</strong></td>
        </tr>
    </table>
</div>