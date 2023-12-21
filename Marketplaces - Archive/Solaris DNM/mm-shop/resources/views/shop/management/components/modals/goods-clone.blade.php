<!-- shop/management/components/modals/goods-clone -->
<form action="{{ url('/shop/management/goods/clone/'.$good->id) }}" method="post">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'goods-clone'])
        @slot('title', 'Клонирование товара')
        Вы точно хотите создать полную копию товара? <br />
        Важно! <strong>Квесты</strong> и <strong>права доступа</strong> со старого товара не будут скопированы в новый.
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Клонировать</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
    @endcomponent
</form>
<!-- / shop/management/components/modals/goods-clone -->