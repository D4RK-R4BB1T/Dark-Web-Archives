<?php
$user = isset($user) ? $user : null;
if (!$user) {
    throw new \Exception('User is not set.');
}
?>
<!-- layouts/components/block-user-cabinet -->
<div class="well block user-info">
    <div class="media">
        <a class="pull-left" href="#">
            <img src="http://www.placehold.it/72x72/EEEEEE/666666">
        </a>
        <div class="media-body">
            <h5 class="media-heading text-orange">{{ $user->getPublicName() }}</h5>
            <p class="small">Рейтинг: <span class="pull-right text-orange">{{ $user->getRating() }}</span></p>
            <p class="small">Баланс: <span class="pull-right text-orange">{{ btc2rub($user->getRealBalance(), 0) }} ₽</span></p>
            <p class="small"><a href="#">Настройки</a></p>
        </div>
    </div>
    <p class="small" style="margin-top: 3px">Последнее посещение: {{ $user->getLastLogin()->format('d.m.y') }}</p>
</div>
<!-- / layouts/components/block-categories -->