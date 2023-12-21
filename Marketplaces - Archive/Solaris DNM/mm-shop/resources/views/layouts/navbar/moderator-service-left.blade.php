<ul class="nav navbar-nav navbar-dropdown">
    <li><a href="{{ url('/shop/service/orders') }}">Заказы</a></li>

    @php
        $count = Auth::user()->newThreadsCount();
    @endphp

    <li class="{{ $page === 'messages' ? 'active' : ''}}"><a href="{{ url("/messages") }}">Сообщения @if($count > 0)<span class="badge red">{{ $count }}</span>@endif</a></li>
</ul>