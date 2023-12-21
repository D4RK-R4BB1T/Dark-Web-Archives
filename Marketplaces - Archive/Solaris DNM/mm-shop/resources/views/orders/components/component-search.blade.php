<!-- orders/components/component-search -->
<form role="form" action="" method="get">
    <input type="hidden" name="status" value="{{ old('status') }}">
    <div class="row">
        <div class="col-xs-24 col-sm-7">
            <div class="form-group has-feedback">
                <select name="good" class="form-control" title="Выберите товар...">
                    <option value="">Любой товар</option>
                    @foreach($goods as $good)
                        <option value="{{ $good->id }}" {{ request('good') == $good->id ? 'selected' : '' }}>{{ $good->title }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-7">
            <div class="form-group has-feedback">
                <select name="city" class="form-control" title="Выберите город...">
                    <option value="">Любой город</option>
                    @foreach($cities as $city)
                        <option value="{{ $city->id }}" {{ request('city') == $city->id ? 'selected' : '' }}>{{ $city->title }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        @if (isset($users))
            <div class="col-xs-24 col-sm-7">
                <div class="form-group has-feedback">
                    <select name="user" class="form-control" title="Выберите пользователя...">
                        <option value="">Любой пользователь</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user') == $user->id ? 'selected' : '' }}>{{ $user ? $user->getPublicName() : '-' }}</option>
                        @endforeach
                    </select>
                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                </div>
            </div>
        @else
            <div class="col-xs-24 col-sm-7">
                <div class="form-group has-feedback">
                    <input type="text" class="form-control" title="Имя пользователя..." placeholder="Имя пользователя..." value="{{ request()->has('username') ?: request()->get('username') }}" name="username">
                </div>
            </div>
        @endif
        <div class="col-xs-24 col-sm-3 text-right">
            <div class="form-group">
                <button class="btn btn-orange" type="submit">Поиск</button>
            </div>
        </div>
    </div>
</form>
<!-- /orders/components/component-search -->