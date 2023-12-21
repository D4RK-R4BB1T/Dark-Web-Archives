{{--
This file is part of MM2-dev project.
Description: Finances employee page
--}}
@extends('layouts.master')

@section('title', 'Выплата сотруднику :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Выплата сотруднику: {{ $employee->getPrivateName() }}</h3>
                <hr class="small" />
                <div class="text-center">
                    <h4>
                        Баланс сотрудника: {{ $employee->getHumanBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                    </h4>
                </div>
                <hr class="small" />

                <form action="" method="post" class="form-horizontal">
                    {{ csrf_field() }}
                    <div class="form-group{{ $errors->has('amount') ? ' has-error' : '' }}">
                        <div class="col-md-16 col-md-offset-4">
                            <input id="amount" name="amount" type="text" class="form-control" value="{{ old('amount') }}" placeholder="Введите нужную сумму в рублях" {{ autofocus_on_desktop() }} />
                            @if ($errors->has('amount'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('amount') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="form-group has-feedback {{ $errors->has('wallet') ? ' has-error' : '' }}">
                        <div class="col-md-16 col-md-offset-4">
                            <select name="wallet" class="form-control" title="Кошелек">
                                <option value="">Кошелек</option>
                                @foreach ($wallets as $wallet)
                                    <option value="{{ $wallet->id }}" {{ old('wallet') == $wallet->id ? 'selected' : '' }}>{{ $wallet->title }} ({{ $wallet->getHumanRealBalance(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }})</option>
                                @endforeach
                            </select>
                            <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                            @if ($errors->has('wallet'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('wallet') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">&nbsp;
                        <button type="submit" class="btn btn-orange">Выплатить деньги</button>
                        &nbsp;
                        <a class="text-muted" href="{{ url('/shop/management/finances/employee/'.$employee->id) }}">вернуться назад</a>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-finances-employees-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection