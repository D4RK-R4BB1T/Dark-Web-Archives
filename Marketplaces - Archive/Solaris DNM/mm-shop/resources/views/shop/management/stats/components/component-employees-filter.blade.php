<!-- shop/management/stats/components/component-employees-filter -->
<form role="form" action="" method="get">
    <div class="row">
        <div class="col-xs-24 col-sm-5">
            <div class="form-group has-feedback {{ $errors->has('period_start') ? 'has-error' : '' }}">
                <input class="form-control" name="period_start" placeholder="Дата начала (ДД.ММ.ГГГГ)" value="{{ request()->get('period_start') ?: $periodStart->format('d.m.Y') }}" />
                <span class="glyphicon glyphicon-calendar form-control-feedback"></span>
                @if ($errors->has('period_start'))
                    <span class="help-block">
                        <strong>{{ $errors->first('period_start') }}</strong>
                    </span>
                @endif
            </div>
        </div>
        <div class="col-xs-24 col-sm-5">
            <div class="form-group has-feedback {{ $errors->has('period_end') ? 'has-error' : '' }}">
                <input class="form-control" name="period_end" placeholder="Дата окончания (ДД.ММ.ГГГГ)" value="{{ request()->get('period_end') ?: $periodEnd->format('d.m.Y') }}" />
                <span class="glyphicon glyphicon-calendar form-control-feedback"></span>
                @if ($errors->has('period_end'))
                    <span class="help-block">
                        <strong>{{ $errors->first('period_end') }}</strong>
                    </span>
                @endif
            </div>
        </div>

        <div class="col-xs-24 col-sm-5">
            <div class="form-group has-feedback {{ $errors->has('employee') ? 'has-error' : '' }}">
                <select class="form-control" name="employee">
                    <option value="">Сотрудник</option>
                    @foreach($employees as $employee)
                        <option value="{{ $employee->id }}" @if($employee->id == request()->get('employee'))selected="selected" @endif>{{ $employee->user->username }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon-chevron-down form-control-feedback"></span>
                @if ($errors->has('employee'))
                    <span class="help-block">
                        <strong>{{ $errors->first('employee') }}</strong>
                    </span>
                @endif
            </div>
        </div>

        {{--<div class="col-xs-24 col-sm-3">
            <div class="form-group has-feedback {{ $errors->has('type') ? 'has-error' : '' }}">
                <select class="form-control" name="type">
                    <option value="feed">Лента</option>
                    <option value="report">Отчет</option>
                </select>
                <span class="glyphicon glyphicon-chevron-down form-control-feedback"></span>
                @if ($errors->has('type'))
                    <span class="help-block">
                        <strong>{{ $errors->first('type') }}</strong>
                    </span>
                @endif
            </div>
        </div>--}}

        <div class="col-xs-24 col-sm-3 col-sm-offset-6 text-right">
            <div class="form-group">
                <button class="btn btn-orange" type="submit">Статистика</button>
            </div>
        </div>
    </div>
</form>
<!-- / shop/management/stats/components/component-employees-filter -->