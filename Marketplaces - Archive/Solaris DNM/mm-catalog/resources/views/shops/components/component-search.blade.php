<!-- shops/components/component-search -->
<form role="form" action="" method="get">
    <div class="row">
        <div class="col-xs-20 col-sm-16">
            <div class="form-group has-feedback">
                <input class="form-control" name="query" placeholder="Название магазина" value="{{ request('query') }}" />
                <span class="glyphicon glyphicon-search form-control-feedback"></span>
            </div>
        </div>
        <div class="col-xs-3 col-sm-3 text-left">
            <div class="form-group" style="height: 33px; line-height: 32px;">
                <button class="btn btn-orange" type="submit">{{ __('layout.Search') }}</button>
            </div>
        </div>
    </div> <!-- /.row -->
</form>
<!-- / shops/components/component-search -->
