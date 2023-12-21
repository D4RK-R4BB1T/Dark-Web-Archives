<!-- shop/management/employees/components/component-search -->
<form role="form" action="" method="get">
    <div class="row">
        <div class="col-xs-24 col-sm-7">
            <div class="form-group has-feedback">
                <select name="employee" class="form-control" title="Выберите сотрудника...">
                    <option value="">Любой пользователь</option>
                    @foreach($shop->employees()->with(['user'])->get() as $shopEmployee)
                        <option value="{{ $shopEmployee->id }}" {{ request('employee') == $shopEmployee->id ? 'selected' : '' }}>{{ $shopEmployee->getPrivateName() }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-3 col-sm-offset-14 text-right">
            <div class="form-group">
                <button class="btn btn-orange" type="submit">Поиск</button>
            </div>
        </div>
    </div>
</form>
<!-- / shop/management/employees/components/component-search -->