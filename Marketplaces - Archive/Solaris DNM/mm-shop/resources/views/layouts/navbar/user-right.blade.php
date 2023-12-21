<?php
/** @var \App\Packages\Referral\ReferralState $referralState */
$referralState = app('referral_state');
?>
<!-- layouts/navbar/user-right -->
<ul class="nav navbar-nav navbar-right">
    @if (Auth::user()->active && isset($unreadNotifications) && $unreadNotifications->count() > 0)
        <li style="margin-right: -10px;">
            <a>
                @component('layouts.components.component-modal-toggle', ['id' => 'notifications'])
                    <i class="glyphicon glyphicon-bell @if ($unreadNotifications->count() > 0) text-red @endif" style="top: 3px"></i>
                    <div class="badge red">{{ $unreadNotifications->count() }}</div>
                @endcomponent
            </a>
        </li>
    @endif
    <li class="dropdown">
        <a>
            {{ Auth::user()->getPrivateName() }}@if (Auth::user()->active), <span class="text-orange">баланс {{ btc2rub(Auth::user()->getRealBalance(), 0) }} руб. <span class="glyphicon glyphicon-cog"></span></span>
            @endif
        </a>
        <ul class="dropdown-menu orange" role="menu">
            <li role="presentation"><a role="menuitem" tabindex="-1" href="{{ url("/settings") }}">Настройки</a></li>
            @if ($referralState->isEnabled && !$referralState->isReferralUrl)
                <li role="presentation"><a role="menuitem" href="{{ url("/referral") }}">Рефералы</a></li>
            @endif
            <li role="presentation">
                <a href="#" class="no-padding">
                @component('layouts.components.component-modal-toggle', ['id' => 'logout', 'class' => 'modal-logout-link'])
                    Выйти
                @endcomponent
                </a>
            </li>
            <li role="separator" class="divider"></li>
            <li role="presentation" class="disabled"><a href="#">1 USD = {{ round_price(\App\Packages\Utils\BitcoinUtils::convert(1, \App\Packages\Utils\BitcoinUtils::CURRENCY_USD, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }} руб.</a></li>
            <li role="presentation" class="disabled"><a href="#">1 BTC = {{ round_price(\App\Packages\Utils\BitcoinUtils::convert(1, \App\Packages\Utils\BitcoinUtils::CURRENCY_BTC, \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }} руб.</a></li>
        </ul>
    </li>
</ul>
<!-- / layouts/navbar/user-right -->