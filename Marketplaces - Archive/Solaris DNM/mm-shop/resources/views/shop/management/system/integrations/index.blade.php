{{--
This file is part of MM2-dev project.
Description: Shop management system integrations page
--}}
@extends('layouts.master')

@section('title', 'Настройки интеграции')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.system.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" role="form" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
{{--                <div class="well block">--}}
{{--                    <h3>Интеграция с Telegram</h3>--}}
{{--                    <hr class="small" />--}}
{{--                    <p class="text-muted">--}}
{{--                        Интеграция с Telegram позволяет продавать товары через мессенджер. <br />--}}
{{--                        Для подключения необходимо связаться с технической поддержкой.--}}
{{--                    </p>--}}
{{--                    <hr class="small" />--}}
{{--                    <div class="form-group">--}}
{{--                        <div class="checkbox">--}}
{{--                            <label>--}}
{{--                                <input type="checkbox" name="integrations_telegram" {{ ($shop->integrations_telegram) ? 'checked' : '' }}> Включить интеграцию с Telegram--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <hr class="small" />--}}
{{--                    <div class="form-group">--}}
{{--                        <textarea name="integrations_telegram_news" class="form-control" rows="3" placeholder="Новостной блок">{{ $shop->integrations_telegram_news }}</textarea>--}}
{{--                        <span class="help-block">Здесь вы можете написать текст, который будет выводится в главном меню бота.</span>--}}
{{--                    </div>--}}
{{--                    <hr class="small" />--}}
{{--                    <div class="text-center">--}}
{{--                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>--}}
{{--                    </div>--}}
{{--                </div>--}}

{{--                <div class="well block">--}}
{{--                    <h3>Интеграция с обменниками</h3>--}}
{{--                    <hr class="small" />--}}
{{--                    <p class="text-muted">--}}
{{--                        Интеграция с обменником позволит пользователям обменивать биткоины на сайте через API сторонних обменников. <br />--}}
{{--                        Для работы данного функционала необходима поддержка со стороны обменника с использованием <a href="{{ url("/help/employee/exchange_api") }}">Solaris Exchange API</a>.--}}
{{--                    </p>--}}
{{--                    <hr class="small" />--}}
{{--                    <div class="row">--}}
{{--                        <div class="col-xs-12 col-sm-11 col-md-8 col-lg-6">--}}
{{--                            <span class="text-muted">Код приглашения:</span>--}}
{{--                        </div>--}}
{{--                        <div class="col-xs-12 col-sm-13 col-md-16">--}}
{{--                            {{ $shop->integrations_qiwi_exchange_invite }} <br />--}}
{{--                            <span class="help-block">Данный код понадобится для регистрации в качестве обменника на странице регистрации: <a target="_blank" href="{{ url("/exchange/register") }}">{{ URL::to('/exchange/register') }}</a></span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <hr class="small" />--}}
{{--                    <div class="form-group">--}}
{{--                        <div class="checkbox">--}}
{{--                            <label>--}}
{{--                                <input type="checkbox" name="integrations_qiwi_exchange" {{ (old('integrations_qiwi_exchange') || $shop->integrations_qiwi_exchange_id) ? 'checked' : '' }}> Включить интеграцию с внешними обменниками--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <div class="form-group has-feedback{{ $errors->has('integrations_qiwi_exchange_id') ? ' has-error' : '' }}">--}}
{{--                        <select name="integrations_qiwi_exchange_id" class="form-control" title="Выберите обменник">--}}
{{--                            <option value="">Выберите обменник</option>--}}
{{--                            @foreach ($qiwiExchanges as $qiwiExchange)--}}
{{--                                <option value="{{ $qiwiExchange->id }}" @if ($shop->integrations_qiwi_exchange_id == $qiwiExchange->id) selected @endif>{{ $qiwiExchange->title }}</option>--}}
{{--                            @endforeach--}}
{{--                        </select>--}}
{{--                        <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>--}}

{{--                        @if ($errors->has('integrations_qiwi_exchange_id'))--}}
{{--                            <span class="help-block">--}}
{{--                                <strong>{{ $errors->first('integrations_qiwi_exchange_id') }}</strong>--}}
{{--                            </span>--}}
{{--                        @endif--}}
{{--                    </div>--}}
{{--                    <div class="form-group">--}}
{{--                        <div class="checkbox">--}}
{{--                            <label>--}}
{{--                                <input type="checkbox" name="integrations_qiwi_exchange_trusted" {{ (old('integrations_qiwi_exchange_trusted') || (($qiwiExchange = $shop->getActiveQiwiExchange()) ? $qiwiExchange->trusted : false) ) ? 'checked' : '' }}> Отметить обменник доверенным--}}
{{--                            </label>--}}
{{--                            <span class="help-block">--}}
{{--                                Если отметить обменник доверенным, то он сможет самостоятельно решать проблемные обмены в свою пользу. При включении данной функции вы рискуете репутацией магазина в случае мошенничества со стороны обменника.--}}
{{--                            </span>--}}
{{--                        </div>--}}
{{--                    </div>--}}
{{--                    <hr class="small" />--}}
{{--                    <div class="text-center">--}}
{{--                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>--}}
{{--                    </div>--}}
{{--                </div>--}}
                <div class="well block">
                    <h3>Интеграция карты кладов</h3>
                    <hr class="small" />
                    <p class="text-muted">
                        Карта кладов - это инструмент, который позволяет следить старшим сотрудникам магазина за равномерным пополнением витрины в определенной локации.<br>
                        Карту кладов видят лишь те, у кого стоит галочка в правах доступа "Видить чужые клады".<br>
                        Координаты имеют разброс в 100-200 метров от реальной точки.
                    </p>
                    <hr class="small" />
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="integrations_quests_map" {{ ($shop->integrations_quests_map) ? 'checked' : '' }}> Включить карту кладов
                            </label>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-system-integrations-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection