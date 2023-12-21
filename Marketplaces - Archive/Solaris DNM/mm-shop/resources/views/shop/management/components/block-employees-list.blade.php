<!-- shop/management/components/block-employees-list -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Сотрудники</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            @foreach ($shop->employees()->with(['user'])->get() as $shopEmployee)
                <a href="{{ url('/shop/management/employees/'.$shopEmployee->id) }}" class="list-group-item {{ (isset($employee) && $employee->id == $shopEmployee->id) ? 'active' : ''}}">
                    {{ $shopEmployee->getPrivateName() }}
                </a>
            @endforeach
        </div>
    </div>
</div>
<!-- / shop/management/components/block-employees-list -->