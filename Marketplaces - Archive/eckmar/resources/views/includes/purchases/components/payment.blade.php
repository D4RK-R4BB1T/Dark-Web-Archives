<div class="col-md-6">
    <h3 class="mb-4">Payment</h3>

    <table class="table ">
        <tr>
            <td>To pay:</td>
            <td>
                @if($purchase -> isDelivered())
                    <span class="badge badge-success">Paid</span>
                @elseif($purchase -> isCanceled())
                    <span class="badge badge-secondary">Canceled</span>
                @elseif($purchase -> isDisputed() && $purchase -> dispute -> isResolved())
                    <span class="badge badge-success">Resolved</span>
                @else
                    {{ $purchase -> coin_sum }} <span class="badge badge-info">{{ $purchase -> coin_label }}</span>
                @endif
            </td>
        </tr>
        <tr>
            <td>Address received:</td>
            <td>
                @if($purchase -> isDelivered())
                    <span class="badge badge-success">Paid</span>
                @elseif($purchase -> isCanceled())
                    <span class="badge badge-secondary">Canceled</span>
                @elseif($purchase -> isDisputed() && $purchase -> dispute -> isResolved())
                    <span class="badge badge-success">Resolved</span>
                @else
                    @if($purchase -> coin_balance == 'unavailable')
                        <span class="badge badge-danger">{{ $purchase -> coin_balance }}</span>
                    @else
                        {{ $purchase -> coin_balance }} <span class="badge badge-info">{{ $purchase -> coin_label }}</span>
                    @endif
                    @if($purchase -> enoughBalance()) <span class="badge badge-success">enough</span> @endif
                @endif
            </td>
        </tr>
        <tr>
            <td>Address:</td>
            <td><input type="text" readonly class="form-control" value="{{ $purchase -> address }}"></td>
        </tr>
        <tr>
            <td>State</td>
            <td>
                <div class="btn-group">
                    <span class="btn disabled btn-sm @if($purchase -> isPurchased()) btn-primary @else btn-outline-secondary @endif">Purchased</span>
                    @if($purchase->type=='normal')
                    <span class="btn disabled btn-sm @if($purchase -> isSent()) btn-primary @else btn-outline-secondary @endif">Sent</span>
                    @endif
                    <span class="btn disabled btn-sm @if($purchase -> isDelivered()) btn-primary @else btn-outline-secondary @endif">Delivered</span>
                    <span class="btn disabled btn-sm @if($purchase -> isDisputed()) btn-danger @else btn-outline-secondary @endif">Disputed</span>
                    <span class="btn disabled btn-sm @if($purchase -> isCanceled()) btn-danger @else btn-outline-secondary @endif">Canceled</span>
                </div>
            </td>
        </tr>
        <tr>
            <td>Type:</td>
            <td>{{ \App\Purchase::$types[$purchase->type] }}</td>
        </tr>
        <tr>
            <td colspan="2" class="justify-content-center text-center">
                @if($purchase->isPurchased())
                    <a href="{{ route('profile.purchases.canceled.confirm', $purchase) }}"
                       class="btn btn-outline-danger"><i class="fas fa-window-close mr-1"></i> Cancel purchase</a>
                @endif


                @if($purchase->type == 'normal' && $purchase -> isPurchased() && $purchase -> isVendor())
                    <a href="{{ route('profile.sales.sent.confirm', $purchase) }}"
                       class="btn btn-outline-mblue"><i class="fas fa-clipboard-check mr-2"></i> Mark as
                        sent</a>
                @endif

                 @if($purchase->type == 'normal' && $purchase -> isSent() && $purchase -> isBuyer())
                    <a href="{{ route('profile.purchases.delivered.confirm', $purchase) }}"
                       class="btn btn-outline-success"><i class="fas fa-clipboard-check mr-2"></i> Mark as
                        delivered</a>
                @endif


                @if(!$purchase -> isDisputed() && ($purchase -> isBuyer() || $purchase -> isVendor()))
                    <a href="#dispute" class="btn btn-outline-danger"><i class="fas fa-poop mr-2"></i>
                        Dispute</a>
                @endif
                {{-- Show to vendor if it is delivered --}}
                @if($purchase->hex && $purchase->isDelivered() && $purchase->isVendor())
                    <div class="alert alert-warning">
                        To retrieve funds from this purchase please sign this transaction and send it.
                    </div>
                    <textarea cols="30" rows="5" class="form-control" readonly>{{ $purchase->hex }}</textarea>
                @endif
                {{-- Show to the winner if it is resolved --}}
                @if($purchase->hex && $purchase->isDisputed() && $purchase->dispute->isResolved() && $purchase->dispute->isWinner())
                    <div class="alert alert-warning">
                        To retrieve funds from this purchase please sign this transaction and send it.
                    </div>
                    <textarea cols="30" rows="5" class="form-control" readonly>{{ $purchase->hex }}</textarea>
                @endif
            </td>




        </tr>

    </table>

    {{-- Instructions for escrow --}}
    {{-- Purchased buyer--}}
    @if($purchase -> isPurchased() && $purchase -> isBuyer() && !$purchase -> enoughBalance())
        <div class="alert alert-warning text-center">
            To proceed with purchase send the enough <em>Bitcoin</em> to the address: <span
                    class="badge badge-info">{{ $purchase -> address }}</span>
        </div>
    @endif

    {{-- Purchased vendor --}}
    @if($purchase -> isVendor() && $purchase -> isPurchased() && $purchase -> enoughBalance())
        <div class="alert alert-warning text-center">
            The buyer has paid sufficient amount on the <em>Escrow</em> address. It's recommended to send the
            goods now!
        </div>
    @elseif($purchase -> isVendor() && $purchase -> isPurchased())
        <div class="alert alert-warning text-center">
            The buyer has not paid sufficient amount on the <em>Escrow</em> address. Don't send the goods now!
        </div>
    @endif

    {{-- Sent vendor --}}
    @if($purchase -> isBuyer() && $purchase -> isSent())
        <div class="alert alert-warning text-center">
            By marking this purchase as delivered you will release the funds from the address to the vendors
            address.
        </div>
    @endif


</div>