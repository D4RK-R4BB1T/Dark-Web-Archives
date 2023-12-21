<!-- users/components/component-search -->
<form role="form" action="" method="get">
    <div class="row">
        <div class="col-lg-10 col-sm-10 col-xs-24">
            <div class="form-group has-feedback">
                <input class="form-control" name="username" placeholder="Логин" value="{{ request('username') }}" />
                <span class="glyphicon glyphicon-search form-control-feedback"></span>
            </div>
        </div>
        <div class="col-lg-10  col-sm-10 col-xs-24">
            <div class="form-group has-feedback">
                <select class="form-control" name="role_type_id">
                    <option value="" readonly @if(empty(request()->get('role_type_id')))selected @endif>Тип роли</option>
                    @foreach(\App\Role::getAllRoles() as $roleId)
                        <option value="{{ $roleId }}" @if($roleId == request()->get('role_type_id'))selected @endif>{{ \App\Role::getName($roleId) }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon-menu-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-lg-4 col-sm-4 col-xs-24 text-left">
            <div class="form-group" style="height: 33px; line-height: 32px;">
                <button class="btn btn-orange" type="submit">{{ __('layout.Search') }}</button>
            </div>
        </div>
    </div> <!-- /.row -->
</form>
<!-- / users/components/component-search -->
