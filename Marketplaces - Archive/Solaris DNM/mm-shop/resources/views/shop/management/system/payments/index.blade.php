{{--
This file is part of MM2-dev project.
Description: Shop management system page
--}}
@extends('layouts.master')

@section('title', 'Оплата :: Системные настройки')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.system.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" role="form" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="well block">
                    <h3>Оплата магазина</h3>
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
                        <div class="col-xs-12 col-sm-11 col-md-8 col-lg-6">
                            <span class="text-muted">Необходимо к оплате:</span>
                        </div>
                        <div class="col-xs-12 col-sm-13 col-md-16">
                            <?php
                            if (\App\Packages\Utils\BitcoinUtils::isPaymentsEnabled()) {
                                $plan = $shop->getHumanPlanName();
                                $planPriceStr = $shop->getHumanPlanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB);

                                $employeesCount = $shop->employees_count;
                                $employeesCountStr = plural($employeesCount, ['дополнительный сотрудник', 'дополнительных сотрудника', 'дополнительных сотрудников']);
                                $employeesPrice = $employeesCount * $shop->getAdditionalEmployeePrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD);
                                $employeesPriceStr = human_price($employeesPrice, \App\Packages\Utils\BitcoinUtils::CURRENCY_USD);

                                $tooltipText = sprintf('Тариф: %s (%s)&#10;+ %d %s (+ %s)',
                                    $plan, $planPriceStr,
                                    $employeesCount, $employeesCountStr, $employeesPriceStr);
                            } else {
                                $tooltipText = '-';
                            }
                            ?>
                            {{ $shop->getHumanTotalPlanPrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}
                            <span class="hint--top" aria-label="{{ $tooltipText }}">
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
                    <div class="text-center">
                        <a class="btn btn-orange" href="{{ url("/shop/management/system/payments/shop") }}">Оплатить</a>
                    </div>
                </div>

                <div class="well block">
                    <h3>Дополнительные услуги</h3>
                    <hr class="small" />
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td>Тип услуги</td>
                            <td>Стоимость</td>
                            <td>Статус</td>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Дополнительные сотрудники</td>
                            <td>{{ $shop->getHumanAdditionalEmployeePrice(\App\Packages\Utils\BitcoinUtils::CURRENCY_USD) }} / ежемесячно</td>
                            <td>{{ $shop->employees_count }} {{ plural($shop->employees_count, ['дополнительный сотрудник', 'дополнительных сотрудника', 'дополнительных сотрудников'])  }}</td>
                            <td class="text-right">
                                <a class="dark-link" href="{{ url("/shop/management/system/payments/employees") }}"><i class="glyphicon glyphicon-cog"></i></a>
                            </td>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </form>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-system-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection