{{--
This file is part of MM2-dev project.
Description: Qiwi wallet add
--}}
@extends('layouts.master')

@section('title', 'Настройки API')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.qiwi.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Настройки интеграции через API</h3>
                <hr class="small" />
                <p class="text-muted">
                    На этой странице вы можете настроить интеграцию с магазином через API. <br />
                    Для работы через API необходимо настроить обработчик по протоколу <a target="_blank" href="{{ url("/help/employee/qiwi_api") }}"><strong>Solaris QIWI Protocol</strong></a>.
                </p>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="qiwi_api" {{ (old('qiwi_api') || $shop->isQiwiApiEnabled()) ? 'checked' : '' }}> Включить QIWI API
                            </label>
                            <span class="help-block">Изменение данной опции удалит все активные в системе QIWI-кошельки.</span>
                        </div>
                    </div>
                    <hr />

                    <div class="form-group{{ $errors->has('qiwi_api_url') ? ' has-error' : '' }}">
                        <input id="qiwi_api_url" type="text" class="form-control" name="qiwi_api_url" placeholder="Адрес обработчика (%HANDLER%)" value="{{ old('qiwi_api_url') ?: $shop->integrations_qiwi_api_url }}" >
                        @if ($errors->has('qiwi_api_url'))
                            <span class="help-block">
                                <strong>{{ $errors->first('qiwi_api_url') }}</strong>
                            </span>
                        @else
                            <span class="help-block">Введите полный адрес обработчика, вместе с http://. Поддерживается работа с зоной .onion.</span>
                        @endif
                    </div>

                    <div class="form-group{{ $errors->has('qiwi_api_key') ? ' has-error' : '' }}">
                        <input id="qiwi_api_key" type="text" class="form-control" name="qiwi_api_key" placeholder="Секретный ключ магазина (shop_key)" value="{{ old('qiwi_api_key') ?: $shop->integrations_qiwi_api_key }}">

                        @if ($errors->has('qiwi_api_key'))
                            <span class="help-block">
                                <strong>{{ $errors->first('qiwi_api_key') }}</strong>
                            </span>
                        @endif
                    </div>
                    <hr />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                    </div>
                </form>
            </div>

            <div class="well block">
                <h3>Отладочная информация</h3>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-12 col-sm-14 col-md-11 col-lg-9">
                        <span class="text-muted">Последняя синхронизация:</span>
                    </div>
                    <div class="col-xs-12 col-sm-10 col-md-13">
                        {{ $shop->integrations_qiwi_api_last_sync_at ? $shop->integrations_qiwi_api_last_sync_at->format('d.m.Y H:i') : '-' }}
                    </div>
                </div>
                <div class="row">
                    <div class="col-xs-12 col-sm-10 col-md-11 col-lg-9">
                        <span class="text-muted">Последний ответ сервера:</span>
                    </div>
                    <div class="col-xs-24">
                        <pre style="min-height: 100px">{{ $shop->integrations_qiwi_api_last_response }}</pre>
                    </div>
                </div>
            </div>

        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-qiwi-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection