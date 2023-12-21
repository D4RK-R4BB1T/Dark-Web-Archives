<!-- shop/management/qiwi/components/component-operations-filter -->
<form role="form" action="" method="get">
    <div class="row">
        <div class="col-xs-24 col-sm-6">
            <div class="form-group has-feedback {{ $errors->has('period_start') ? 'has-error' : '' }}">
                <input class="form-control" name="period_start" placeholder="Дата начала (ДД.ММ.ГГГГ)" value="{{ old('period_start') ?: $periodStart->format('d.m.Y') }}" />
                <span class="glyphicon glyphicon-calendar form-control-feedback"></span>
                @if ($errors->has('period_start'))
                    <span class="help-block">
                        <strong>{{ $errors->first('period_start') }}</strong>
                    </span>
                @endif
            </div>
        </div>
        <div class="col-xs-24 col-sm-6">
            <div class="form-group has-feedback {{ $errors->has('period_end') ? 'has-error' : '' }}">
                <input class="form-control" name="period_end" placeholder="Дата окончания (ДД.ММ.ГГГГ)" value="{{ old('period_end') ?: $periodEnd->format('d.m.Y') }}" />
                <span class="glyphicon glyphicon-calendar form-control-feedback"></span>
                @if ($errors->has('period_end'))
                    <span class="help-block">
                        <strong>{{ $errors->first('period_end') }}</strong>
                    </span>
                @endif
            </div>
        </div>
        <div class="col-xs-24 col-sm-6">
            <div class="form-group has-feedback">
                <select name="wallet" class="form-control" title="Выберите кошелек...">
                    <option value="">Любой кошелек</option>
                    @foreach($qiwiWalletsFilter as $walletId => $wallet)
                        <option value="{{ $walletId }}" {{ request('wallet') == $walletId ? 'selected' : '' }}>+{{ $wallet }}</option>
                    @endforeach
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
<!-- shop/management/qiwi/components/component-operations-filter -->