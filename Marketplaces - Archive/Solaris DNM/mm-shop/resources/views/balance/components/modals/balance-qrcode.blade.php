<!-- balance/components/modals/balance-qrcode -->
@component('layouts.components.component-modal', ['id' => 'balance-qrcode'])
    @slot('title', 'QR-код')
    <div class="text-center">
        <img src="{{ qrcode(Auth::user()->primaryWallet()->segwit_wallet) }}" />
    </div>
    @slot('footer_btn', 'Закрыть')
@endcomponent
<!-- / balance/components/modals/balance-qrcode -->