<!-- shop/management/components/modals/goods-delete -->
<form action="{{ url('/shop/management/goods/delete/'.$good->id) }}" method="post">
    {{ csrf_field() }}
    <input type="hidden" name="redirect_to" value="/shop/management/goods">
    @component('layouts.components.component-modal', ['id' => 'goods-delete'])
        @slot('title', 'Удаление товара')
        Вы действительно хотите удалить данный товар? <br />
        Важно! Все добавленные <strong>упаковки</strong>, <strong>квесты</strong>, <strong>кастомные места</strong>, <strong>партии (в учете товаров)</strong> и <strong>отзывы</strong> будут удалены. Данная операция необратима.
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Удалить</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
    @endcomponent
</form>
<!-- / shop/management/components/modals/goods-delete -->