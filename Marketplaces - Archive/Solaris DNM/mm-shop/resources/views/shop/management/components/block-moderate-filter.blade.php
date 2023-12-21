<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Фильтры</div>
    <div class="panel-body">
        <form action="" method="get">
            <div class="col-xs-24">
                <div class="form-group has-feedback">
                    <input class="form-control" name="username" placeholder="Имя пользователя" value="{{ request('username') }}"/>
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
            </div>

            <div class="col-xs-24">
                <div class="form-group has-feedback">
                    <select name="city_id" id="city_id" class="form-control">
                        <option value="">Город</option>
                        @foreach($cities as $c)
                            <option value="{{ $c->id }}" @if($c->id == request()->get('city_id'))selected="selected" @endif>{{ $c->title }}</option>
                        @endforeach
                    </select>
                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                </div>
            </div>

            @if(request()->get('city_id') && $regions->count() > 0)
                <div class="col-xs-24">
                    <div class="form-group has-feedback">
                        <select name="region_id" id="region_id" class="form-control">
                            <option value="">Район</option>
                            @foreach($regions as $r)
                                <option value="{{ $r->id }}" @if($r->id == request()->get('region_id'))selected="selected" @endif>{{ $r->title }}</option>
                            @endforeach
                        </select>
                        <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                    </div>
                </div>
            @endif

            <div class="text-center">
                <button class="btn btn-orange" type="submit">Фильтр</button>
            </div>
        </form>
    </div>
</div>
