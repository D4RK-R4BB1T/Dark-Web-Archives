<!-- shop/management/components/sections-menu -->
<ul class="sections-menu nav nav-pills">
    <li class="{{ $page === 'goods' ? 'active' : ''}}"><a href="{{ url("/shop/management/goods") }}">Товары</a></li>
    <?php $count = Auth::user()->can('management-sections-orders' )? $shop->preordersCount() : 0; ?>
    @if (Auth::user()->can('management-sections-own-orders'))
        <li class="{{ $page === 'orders' ? 'active' : ''}}"><a href="{{ url("/shop/management/orders") }}">Заказы @if ($count > 0)<span class="badge red">{{ $count }}</span>@endif</a></li>
    @endif
    @if (Auth::user()->can('management-sections-messages'))
        <?php $count = $shop->newThreadsCount(); ?>
        <li class="{{ $page === 'messages' ? 'active' : ''}}"><a href="{{ url("/shop/management/messages") }}">Сообщения @if ($count > 0)<span class="badge red">{{ $count }}</span>@endif</a></li>
    @endif
    @if (Auth::user()->can('management-sections-discounts'))
        <li class="{{ $page === 'discounts' ? 'active' : '' }}"><a href="{{ url("/shop/management/discounts") }}">Скидки</a></li>
    @endif
    @if (Auth::user()->can('management-sections-employees'))
        <li class="{{ $page === 'employees' ? 'active' : '' }}"><a href="{{ url("/shop/management/employees") }}">Сотрудники</a></li>
    @endif
    @if (Auth::user()->can('management-sections-finances'))
        <li class="{{ $page === 'finances' ? 'active' : '' }}"><a href="{{ url("/shop/management/finances") }}">Финансы</a></li>
    @endif
    @if (Auth::user()->can('management-sections-settings'))
        <li class="{{ $page === 'settings' ? 'active' : '' }}"><a href="{{ url("/shop/management/settings") }}">Настройки</a></li>
    @endif
    @if (Auth::user()->can('management-sections-stats'))
        <li class="{{ $page === 'stats' ? 'active' : '' }}"><a href="{{ url("/shop/management/stats") }}">Статистика</a></li>
    @endif
{{--    @if (Auth::user()->can('management-sections-qiwi'))--}}
{{--        <li class="{{ $page === 'qiwi' ? 'active' : '' }}"><a href="{{ url("/shop/management/qiwi") }}">QIWI</a></li>--}}
{{--    @endif--}}
    @if (Auth::user()->can('management-sections-system'))
        <?php
        $needToPay = $shop->getTotalPlanPrice() > 0;
        $almostExpired = $needToPay && \Carbon\Carbon::now()->addDays(7)->gte($shop->expires_at);
        $expired = $needToPay && \Carbon\Carbon::now()->gte($shop->expires_at);
        ?>
        <li class="{{ $page === 'system' ? 'active' : '' }}">
            <a href="{{ url("/shop/management/system") }}">Система
                @if ($expired)
                    <span class="badge red hint--left hint--error" aria-label="Срок оплаты магазина истек!">!!!</span>
                @elseif($almostExpired)
                    <span class="badge red hint--left hint--error" aria-label="Срок оплаты магазина скоро истекает!">!</span>
                @endif
            </a></li>
    @endif
</ul>
<br />
<!-- / shop/management/components/sections-menu -->