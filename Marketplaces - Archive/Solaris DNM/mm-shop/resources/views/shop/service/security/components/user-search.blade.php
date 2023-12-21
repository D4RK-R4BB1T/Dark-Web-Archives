<form role="form" action="" method="get">
    <div class="row">
        <div class="col-xs-24 col-sm-6">
            <div class="form-group has-feedback">
                <input class="form-control" name="username" placeholder="Логин пользователя..." value="{{ request('username') }}"/>
                <span class="glyphicon glyphicon-search form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-3 col-sm-offset-3 text-right">
            <div class="form-group">
                <button class="btn btn-orange" type="submit">Поиск</button>
            </div>
        </div>
    </div>
</form>
