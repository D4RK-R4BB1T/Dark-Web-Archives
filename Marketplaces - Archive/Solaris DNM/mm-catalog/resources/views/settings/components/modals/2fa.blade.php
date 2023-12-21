<!-- settings/security/components/modals/2fa -->
@component('layouts.components.component-modal', ['id' => '2fa'])
    @slot('title', 'Выберите способ двухфакторной авторизации')
        <div class="row">
            <div class="col-sm-12" style="border-right: 1px solid #ccc;">
                <div class="text-center">
                    <div style="margin-bottom: 5px">
                        <strong>Авторизация через OTP</strong>
                    </div>
                    <p class="text-muted">
                        Для авторизации будет использоваться приложение, генерирующее одноразовые коды на мобильном устройстве. <br /><br />
                        Это более простой, но менее безопасный вариант.
                    </p>
                    <br />
                    <a class="btn btn-orange" href="/settings/security/2fa/otp/enable">Использовать OTP</a>
                </div>
            </div>
            <hr class="hidden visible-xs" />
            <div class="col-sm-12">
                <div class="text-center">
                    <div style="margin-bottom: 5px">
                        <strong>Авторизация через PGP</strong>
                    </div>
                    <p class="text-muted">
                        Для авторизации будет требоваться расшифровка зашифрованного вашим PGP-ключом сообщения. <br /><br />
                        Это менее простой, но более безопасный вариант.
                    </p>
                    <br />
                    <a class="btn btn-orange" href="/settings/security/2fa/pgp/enable">Использовать PGP</a>
                </div>
            </div>
        </div>
        @slot('footer_btn', 'Закрыть')
@endcomponent
<!-- / settings/security/components/modals/2fa -->