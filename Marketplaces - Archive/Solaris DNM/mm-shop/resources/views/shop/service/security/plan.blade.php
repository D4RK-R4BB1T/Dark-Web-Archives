@extends('layouts.master')

@section('title', 'Служба безопасности')

@section('content')
    <div class="col-sm-7 col-md-5 col-lg-5 col-xs-24">
        @include('shop.service.security.components.sidebar')
    </div>

    <div class="col-sm-17 col-md-19 col-lg-19 col-xs-24 pull-right animated fadeIn">
        <form action="" method="post">
            {{ csrf_field() }}
            <div class="well block">
                <h3>Тарифный план</h3>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-12 col-sm-11 col-md-8 col-lg-6">
                        <span class="text-muted">Подключенный тариф:</span>
                    </div>
                    <div class="col-xs-12 col-sm-13 col-md-16">
                        {{ $shop->getHumanPlanName() }}
                        <span class="hint--top" aria-label="{{ $shop->getPlanDescription() }}">
                            <i class="glyphicon glyphicon-question-sign"></i>
                        </span>
                    </div>
                </div>
                <div class="row">
                    <?php
                    $needToPay = $shop->getTotalPlanPrice() > 0;
                    $almostExpired = $needToPay && \Carbon\Carbon::now()->addDays(7)->gte($shop->expires_at);
                    $expired = $needToPay && \Carbon\Carbon::now()->gte($shop->expires_at);
                    ?>
                    <div class="col-xs-12 col-sm-11 col-md-8 col-lg-6">
                        <span class="{{ ($expired || $almostExpired) ? 'text-danger' : 'text-muted' }}">Срок оплаты:</span>
                    </div>
                    <div class="col-xs-12 col-sm-13 col-md-16 {{ ($expired || $almostExpired) ? 'text-danger' : '' }}">
                        {{ $shop->expires_at->format('d.m.Y') }}
                        @if ($expired)
                            <span class="hint--top hint--error" aria-label="Срок оплаты магазина истек!">
                                <i class="glyphicon glyphicon-exclamation-sign"></i>
                            </span>
                        @elseif($almostExpired)
                            <span class="hint--top hint--error" aria-label="Срок оплаты магазина скоро истекает!">
                                <i class="glyphicon glyphicon-exclamation-sign"></i>
                            </span>
                        @endif
                    </div>
                </div>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-24">
                        <div class="form-group {{ $errors->has('expires_at') ? ' has-error' : '' }}">
                            <label for="expires_at">Новый срок оплаты:</label>
                            <input id="expires_at" type="date" class="form-control" name="expires_at" placeholder="Срок оплаты" value="{{ old('expires_at') ?: $shop->expires_at->format('Y-m-d') }}">
                            @if ($errors->has('expires_at'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('expires_at') }}</strong>
                                </span>
                            @endif
                        </div>
                        <div class="form-group {{ $errors->has('plan') ? ' has-error' : '' }}">
                            <label for="plan">Новый тариф:</label>
                            <select id="plan" class="form-control" name="plan">
                                @foreach ($plans as $plan)
                                <option value="{{ $plan['value'] }}" @if ($plan['selected']) selected @endif>{{ $plan['name'] }} - {{ $plan['description'] }}</option>
                                @endforeach
                            </select>
                            @if ($errors->has('plan'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('plan') }}</strong>
                                </span>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-orange">Сохранить</button>
                    </div> <!-- /.col-xs-24 -->
                </div> <!-- /.row -->
            </div>
        </form>
    </div>
@endsection