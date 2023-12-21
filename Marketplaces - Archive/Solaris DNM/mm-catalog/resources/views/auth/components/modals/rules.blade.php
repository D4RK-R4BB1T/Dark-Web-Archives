<!-- auth/components/modals/rules -->
@component('layouts.components.component-modal', ['id' => 'terms', 'modal_lg' => isset($modal_lg) ? $modal_lg : false])
    @slot('title', 'Правила каталога')
    <div class="row">
        <div class="col-xs-20 col-xs-offset-2">
            <div class="form-group" style="margin-bottom: 0">
                @include('rules')
            </div>
        </div>
    </div>
    @slot('footer_btn', 'Закрыть')
@endcomponent
<!-- auth/components/modals/rules -->
