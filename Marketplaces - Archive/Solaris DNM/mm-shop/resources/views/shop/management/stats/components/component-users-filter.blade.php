<!-- shop/management/stats/components/component-users-filter -->
<form role="form" action="" method="get">
    <div class="row">
        <div class="col-xs-24 col-sm-6">
            <div class="form-group has-feedback">
                <input class="form-control" name="username" placeholder="Логин пользователя..." value="{{ request('username') }}"/>
                <span class="glyphicon glyphicon-search form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-6">
            <div class="form-group has-feedback">
                <select name="group" class="form-control" title="Группа пользователя...">
                    <option value="">Любая группа</option>
                    @foreach (\App\UserGroup::all() as $group)
                        <option value="{{ $group->id }}" {{ request('group') == $group->id ? 'selected' : '' }}>{{ $group->title }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-6">
            <div class="form-group transparent has-feedback">
                <select name="order" class="form-control" title="Выберите тип клада...">
                    <option value="last_login_at">По последнему входу</option>
                    <option value="buy_count" {{ request('order') == 'buy_count' ? 'selected' : '' }}>По количеству покупок</option>
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-3 col-sm-offset-3 text-right">
            <div class="form-group">
                <button class="btn btn-orange" type="submit">Поиск</button>
            </div>
        </div>
    </div>
</form>
<!-- / shop/management/stats/components/component-users-filter -->