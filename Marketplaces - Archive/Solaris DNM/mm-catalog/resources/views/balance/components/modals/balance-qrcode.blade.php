<!-- balance/components/modals/balance-qrcode -->
@component('layouts.components.component-modal', ['id' => 'balance-qrcode'])
    @slot('title', 'QR-код')
    <div class="text-center">
        @if($address)
            <img src="{{ qrcode($address) }}" />
        @endif
    </div>
    @slot('footer_btn', __('buttons.close'))
@endcomponent
<!-- / balance/components/modals/balance-qrcode -->