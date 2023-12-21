<?php
/** @var \App\Good $good */
$good = $order->_stub_good();

/** @var \App\GoodsPackage $package */
$package = $order->_stub_package();
?>
<!-- orders/components/block-good -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">{{ __('goods.Goods') }}</div>
    <div class="panel-body">
        @if ($order->good && $order->good->has_quests)
            <a target="_blank" class="dark-link" href="{{ catalog_jump_url($order->shop->id, '/goods/' . $order->good->app_good_id) }}">
        @endif
        @if ($order->localImageCached())
            <div class="hidden-xs"><img src="{{ $order->localImageURL() }}" class="img-responsive" /></div>
        @endif
        <h4 style="margin-top: 0">{{ $good->title }}</h4>
        @if ($order->good && $order->good->has_quests)
            </a>
        @endif
        <p><span class="text-muted">Город:</span> <span class="pull-right">{{ traverse($good, 'city->title') ?: '-' }}</span></p>
        <p><span class="text-muted">Количество:</span> <span class="pull-right">{{ $package->getHumanWeight() }}</span></p>
        <p><span class="text-muted">Стоимость:</span> <span class="pull-right">{{ $package->getHumanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}</span></p>
        {{--<p><span class="text-muted">Гарант:</span> <span class="pull-right">{{ $order->guarantee ? 'Да' : 'Нет' }}</span></p>--}}
        <p><span class="text-muted">Время:</span> <span class="pull-right">{{ $order->created_at->format('d.m.Y в H:i') }}</span></p>
        <p><span class="text-muted">Тип:</span> <span class="pull-right">{{ $package->preorder ? __('goods.Preorder') : __('goods.Instant purchase') }}</span></p>
    </div>
</div>
<!-- / orders/components/block-good -->