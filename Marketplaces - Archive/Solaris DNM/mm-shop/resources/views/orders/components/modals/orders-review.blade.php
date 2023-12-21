<!-- orders/components/modals/orders-review -->
<form class="form-horizontal" role="form" action="{{ url('/orders/review/'.$order->id) }}" method="post">
    {{ csrf_field() }}
    <input type="hidden" name="redirect_to" value="{{ url('/orders/review/'.$order->id) }}" />
    @component('layouts.components.component-modal', ['id' => 'orders-review'])
        @slot('title', 'Отзыв о товаре')
        <div class="form-group">
            <textarea rows="3" name="text" class="form-control" placeholder="Напишите отзыв..." required></textarea>
        </div>
        <div class="row form-group" style="margin-top: 8px; margin-bottom: 2px">
            <div class="col-sm-8"><label class="control-label no-padding">Нравится ли вам магазин?</label></div>
            <div class="col-xs-6 col-sm-6 text-right text-muted no-padding-l">очень плохой магазин</div>
            <div class="col-xs-12 col-sm-4 no-padding text-center">
                <input type="radio" name="shop_rating" value="1">&nbsp;
                <input type="radio" name="shop_rating" value="2">&nbsp;
                <input type="radio" name="shop_rating" value="3">&nbsp;
                <input type="radio" name="shop_rating" value="4">&nbsp;
                <input type="radio" name="shop_rating" value="5" checked>
            </div>
            <div class="col-xs-6 col-sm-6 text-muted">отличный магазин</div>
        </div>
        <div class="row form-group" style="margin-bottom: 2px">
            <div class="col-sm-8"><label class="control-label no-padding">Как сработал курьер?</label></div>
            <div class="col-xs-6 col-sm-6 text-right text-muted no-padding-l">было сложно найти</div>
            <div class="col-xs-12 col-sm-4 no-padding text-center">
                <input type="radio" name="dropman_rating" value="1">&nbsp;
                <input type="radio" name="dropman_rating" value="2">&nbsp;
                <input type="radio" name="dropman_rating" value="3">&nbsp;
                <input type="radio" name="dropman_rating" value="4">&nbsp;
                <input type="radio" name="dropman_rating" value="5" checked>
            </div>
            <div class="col-xs-6 col-sm-6 text-muted">нашлось быстро</div>
        </div>
        <div class="row form-group" style="margin-bottom: 4px">
            <div class="col-sm-8"><label class="control-label no-padding">Понравился ли вам стафф?</label></div>
            <div class="col-xs-6 col-sm-6 text-right text-muted no-padding-l">совсем не понравился</div>
            <div class="col-xs-12 col-sm-4 no-padding text-center">
                <input type="radio" name="item_rating" value="1">&nbsp;
                <input type="radio" name="item_rating" value="2">&nbsp;
                <input type="radio" name="item_rating" value="3">&nbsp;
                <input type="radio" name="item_rating" value="4">&nbsp;
                <input type="radio" name="item_rating" value="5" checked>
            </div>
            <div class="col-xs-6 col-sm-6 text-muted">потрясающий стафф</div>
        </div>
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Оставить отзыв</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
    @endcomponent
</form>
<!-- / orders/components/modals/orders-review -->