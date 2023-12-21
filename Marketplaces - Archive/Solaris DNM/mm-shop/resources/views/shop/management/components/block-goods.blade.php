<!-- shop/management/components/block-goods -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Работа с товарами</div>
    <div class="panel-body no-padding">
        @if (!Auth::user()->can('management-goods-create') && !Auth::user()->can('management-sections-paid-services'))
            <p style="padding: 10px 10px 0 15px">Нет доступных действий.</p>
        @else
            <div class="list-group hover-menu">
                @can('management-goods-create')
                    <a href="{{ url("/shop/management/goods/add") }}" class="list-group-item">Добавить товар</a>
                @endcan

                @can('management-sections-paid-services')
                    <a href="{{ url("/shop/management/goods/services") }}" class="list-group-item">Настроить платные услуги</a>
                @endcan

                @can('management-sections-moderate')
                    <a href="{{ url("/shop/management/goods/moderation") }}" class="list-group-item">Квесты на модерации</a>
                @endcan
                {{--<a href="#" class="list-group-item">Заложенные товары</a>--}}
            </div>
        @endif
    </div>
</div>

@if ($shop->integrations_quests_map && Auth::user()->can('management-quests-map'))
    <div class="panel panel-default panel-sidebar block no-padding">
        <a href="{{ route('quests_map') }}" class="list-group-item">Карта кладов</a>
    </div>
@endif

<!-- / shop/management/components/block-goods -->