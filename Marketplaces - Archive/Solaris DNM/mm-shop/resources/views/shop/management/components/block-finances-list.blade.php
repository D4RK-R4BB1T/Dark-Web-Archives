<!-- shop/management/components/block-finances-list -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Сотрудники</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            @foreach ($shop->employees()->with(['user'])->get() as $shopEmployee)
                <a href="{{ url('/shop/management/finances/employee/'.$shopEmployee->id) }}" class="list-group-item {{ (isset($employee) && $employee->id == $shopEmployee->id) ? 'active' : ''}}">
                    {{ $shopEmployee->getPrivateName() }}
                </a>
            @endforeach
            <a href="{{ url("/shop/management/finances/employee/all") }}" class="list-group-item {{ (isset($section) && $section === 'all') ? 'active' : '' }}">Общая статистика</a>
        </div>
    </div>
</div>
<!-- / shop/management/components/block-finances-list -->