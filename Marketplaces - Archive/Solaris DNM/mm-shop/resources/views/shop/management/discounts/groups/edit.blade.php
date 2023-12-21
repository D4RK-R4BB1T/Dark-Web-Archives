@extends('layouts.master')

@section('title', 'Редактирование группы :: Скидки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_DISCOUNTS,
        ['title' => 'Скидочные группы', 'url' => url('/shop/management/discounts/groups')],
        ['title' => 'Редактирование группы']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.discounts.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Редактирование группы</h3>
                <hr class="small" />
                <div class="alert alert-warning">
                    После редактирования группы, действующие пользователи не будут перенесены туда автоматически.<br />
                    Для автоматического распределения используйте "Мастер назначения групп".
                </div>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Название группы (не отображается пользователю)" value="{{ old('title') ?: $group->title }}" required {{ autofocus_on_desktop() }}>

                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->

                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('percent_amount') ? ' has-error' : '' }}">
                                <input id="percent_amount" type="text" class="form-control" name="percent_amount" placeholder="Величина скидки (%), только цифры" value="{{ old('percent_amount') ?: $group->percent_amount }}" required>

                                @if ($errors->has('percent_amount'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('percent_amount') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->

                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group has-feedback{{ $errors->has('mode') ? ' has-error' : '' }}">
                                <select name="mode" class="form-control" title="Режим перехода">
                                    <option value="">Режим перехода</option>
                                    <option value="{{ \App\UserGroup::MODE_AUTO }}" {{ old('mode', $group->mode) == \App\UserGroup::MODE_AUTO ? 'selected' : '' }}>Переход по количеству покупок</option>
                                    <option value="{{ \App\UserGroup::MODE_MANUAL }}" {{ old('mode', $group->mode) == \App\UserGroup::MODE_MANUAL ? 'selected' : '' }}>Ручной режим</option>
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('category'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('category') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->

                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('buy_count') ? ' has-error' : '' }}">
                                <input id="buy_count" type="number" class="form-control" name="buy_count" placeholder="Количество покупок для авто-перехода" value="{{ old('buy_count') ?: $group->buy_count }}">

                                @if ($errors->has('buy_count'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('buy_count') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Используется при автоматическом режиме перехода. Пользователь, набравший заданное количество покупок, будет автоматически перенесен в эту группу.
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->

                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отредактировать группу</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-discounts-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection