@extends('layouts.master')

@section('title', 'Регистрация обменника')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-24 col-md-16 col-md-offset-4 auth-container {{ $errors->count() > 0 ? ' animated shake' : '' }}">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ config('mm2.header_title') }}: Регистрация обменника</div>
                <div class="panel-body">
                    <form class="form-horizontal" role="form" method="POST" action="{{ url('/exchange/register') }}">
                        {{ csrf_field() }}

                        <div class="form-group{{ $errors->has('invite') ? ' has-error' : '' }}">
                            <div class="col-xs-24">
                                <input id="invite" type="text" class="form-control" name="invite" placeholder="Код приглашения" value="{{ old('invite') }}" required {{ autofocus_on_desktop() }}>

                                @if ($errors->has('invite'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('invite') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Код приглашения можно получить у администратора магазина.
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="title" type="text" class="form-control" name="title"
                                       placeholder="Название обменника" value="{{ old('title') }}" required>

                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Название обменника будет видно пользователям и администратору магазина. Оно не может быть изменено в дальнейшем.
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('description') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <textarea class="form-control" name="description" rows="3" placeholder="Описание обменника" required>{{ old('description') }}</textarea>

                                @if ($errors->has('description'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('description') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Краткая информация, показываемая пользователю при осуществлении обмена. Тут могут быть дополнительные контакты и прочая информация.
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('api_url') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="api_url" type="text" class="form-control" name="api_url"
                                       placeholder="Адрес обработчика (%HANDLER%)" value="{{ old('api_url') }}" required>

                                @if ($errors->has('api_url'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('api_url') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Введите полный адрес обработчика <strong>Solaris Exchange API</strong>, вместе с http://. Поддерживается работа с зоной .onion. <br />
                                        <strong>Документация доступна по ссылке: <a href="{{ url("/help/employee/exchange_api") }}" target="_blank">{{ URL::to('/help/employee/exchange_api') }}</a></strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group{{ $errors->has('api_key') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="api_key" type="text" class="form-control" name="api_key" placeholder="Секретный ключ магазина (shop_key)" value="{{ old('api_key') }}" required>
                                @if ($errors->has('api_key'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('api_key') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="form-group">
                            <div class="col-md-24">
                                <button type="submit" class="btn btn-lg btn-orange col-xs-24">
                                    Регистрация
                                </button>
                            </div>
                        </div>
                    </form>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection
