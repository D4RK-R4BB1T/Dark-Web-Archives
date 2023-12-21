<!-- shop/management/stats/components/component-orders-filter -->
<form role="form" action="" method="get">
    <div class="row">
        <div class="col-xs-24 col-sm-7">
            <div class="form-group has-feedback">
                <select name="good" class="form-control" title="Товар...">
                    <option value="">Товар</option>
                    @foreach($goods as $good)
                        <option value="{{ $good->id }}" {{ request('good') == $good->id ? 'selected' : '' }}>{{ $good->title }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-24 col-sm-4">
            <div class="form-group transparent has-feedback">
                <select name="show" class="form-control" title="Товар...">
                    <option value="available" {{ request('show', 'available') === 'available' ? 'selected' : '' }}>Активные</option>
                    <option value="all" {{ request('show', 'available') === 'all' ? 'selected' : '' }}>Все</option>
                </select>
                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
            </div>
        </div>

        <div class="col-xs-24 col-sm-3 col-sm-offset-10 text-right">
            <div class="form-group">
                <button class="btn btn-orange" type="submit">Поиск</button>
            </div>
        </div>
    </div>
</form>
<!-- / shop/management/stats/components/component-orders-filter -->