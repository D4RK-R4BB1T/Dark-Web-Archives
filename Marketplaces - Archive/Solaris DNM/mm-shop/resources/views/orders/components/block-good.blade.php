<?php
/** @var \App\Order $order */
$good = $order->_stub_good();

$includeReferrerFee = isset($include_referrer_fee) ? (bool) $include_referrer_fee : false;
$includeReferrerHint = isset($include_referrer_hint) ? (bool) $include_referrer_hint : false;
$alwaysIncludeGroupDiscount = isset($include_group_discount) ? (bool) $include_group_discount : false;

/** @var \App\GoodsPackage $package */
$package = $order->_stub_package($includeReferrerFee);
/** @var \App\UserGroup $group */
$group = $order->_stub_group();

$includeGroupDiscount = !is_null($group->percent_amount);
if (!$alwaysIncludeGroupDiscount) {
    $includeGroupDiscount = $includeGroupDiscount && $group->percent_amount > 0;
}
?>
<!-- orders/components/block-good -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Товар</div>
    <div class="panel-body">
        @if ($order->good && $order->good->has_quests)
            <a class="dark-link" href="{{ url('/shop/'.$order->shop->slug.'/goods/'.$order->good->id) }}">
        @endif
        <div class="hidden-xs"><img src="{{ url($good->image_url) }}" class="img-responsive" /></div>
        <h4 style="margin-top: 0">{{ $good->title }}</h4>
        @if ($order->good && $order->good->has_quests)
            </a>
        @endif
        <p><span class="text-muted">Город:</span> <span class="pull-right">{{ $package->city->title }}</span></p>
        <p><span class="text-muted">Количество:</span> <span class="pull-right">{{ $package->getHumanWeight() }}</span></p>
        <p><span class="text-muted">Стоимость:</span>
            @if (!$includeReferrerFee || !$includeReferrerHint || empty($order->referrer_fee))
                <span class="pull-right">{{ $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</span>
            @else
                <span class="pull-right text-right">
                    {{ $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }} <br />
                    <small class="text-muted">с учётом наценки в {{ $order->referrer_fee }}%</small>
                </span>
                <br /><br />
            @endif
        </p>
        @if ($order->promocode)
            <p><span class="text-muted">Код:</span> <span class="pull-right"><code style="margin: auto 0">{{ $order->promocode->code }}</code></span></p>
            <p><span class="text-muted">Скидка по коду:</span> <span class="pull-right">{{ $order->promocode->getHumanDiscount() }}</span></p>
        @endif
        @if ($includeGroupDiscount)
            <p><span class="text-muted">Скидочная группа:</span> <span class="pull-right"> {{ $group->getHumanDiscount() }}</span></p>
        @endif

        @if ($order->user_price_btc)
            <p><span class="text-muted">Оплачено:</span> <span class="pull-right">{{ human_price($order->user_price_btc, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC) }}</span></p>
        @endif
        {{--<p><span class="text-muted">Гарант:</span> <span class="pull-right">{{ $order->guarantee ? 'Да' : 'Нет' }}</span></p>--}}
        <p><span class="text-muted">Время:</span> <span class="pull-right">{{ $order->created_at->format('d.m.Y в H:i') }}</span></p>
        <p><span class="text-muted">Тип:</span> <span class="pull-right">{{ $package->preorder ? 'Предзаказ' : 'Моментальная покупка' }}</span></p>
    </div>
</div>
<!-- / orders/components/block-good -->