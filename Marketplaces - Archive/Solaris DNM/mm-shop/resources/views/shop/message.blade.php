{{-- 
This file is part of MM2-dev project. 
Description: Shop page creation
--}}
@extends('layouts.master')

@section('title', 'Отправить сообщение')

@section('content')
    @include('layouts.components.sections-menu')
    @if (Auth::user()->employee)
        @include('shop.management.components.sections-menu')
    @endif
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' => [
            // BREADCRUMB_CATALOG,
            // BREADCRUMB_SHOPS,
            ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
            ['title' => 'Отправить сообщение']
        ]
    ])
    @include('shop.sections-pages-menu', [
        'page' => ''
    ])
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-13 col-lg-13 animated fadeIn">
            <form role="form" action="" method="post">
                {{ csrf_field() }}
                <div class="well block">
                    <h3>Отправить сообщение</h3>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Тема сообщения" value="{{ old('title') }}" required {{ autofocus_on_desktop() }}>
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
                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                                <textarea rows="5" class="form-control" name="body" placeholder="Введите текст..." required>{{ old('body') }}</textarea>
                                @if ($errors->has('body'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('body') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="form-group {{ $errors->has('receiver') ? 'has-error' : '' }}">
                        <div class="radio">
                            <label>
                                <input type="radio" name="receiver" value="shop" {{ (!old('receiver') || old('receiver') === 'shop') ? 'checked' : '' }}>
                                Клиентский сервис магазина
                                <span class="help-block">Для обращения по любым вопросам работы магазина и покупкам.</span>
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="receiver" value="user" {{ (old('receiver') === 'user') ? 'checked' : '' }}>
                                Отправить лично сотруднику магазина
                                <div class="form-group has-feedback">
                                    <select name="receiver_id" class="form-control">
                                        @foreach ($receivers as $receiver)
                                            <option value="{{ $receiver->id }}" {{ (old('receiver_id') == $receiver->id) ? 'selected' : '' }}>
                                                {{ $receiver->user->getPublicName() }}
                                                @if ($receiver->sections_messages_private_description)
                                                    ({{ $receiver->sections_messages_private_description }})
                                                @endif
                                            </option>
                                        @endforeach
                                        <option value="1">{{ \App\Shop::getDefaultShop()->owner()->getPublicName() }} (Владелец магазина)</option>
                                    </select>
                                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                </div>
                                <span class="help-block">Диалог будет доступен только выбранному сотруднику.</span>
                            </label>
                        </div>
                        @if (($qiwiExchange = $shop->getActiveQiwiExchange()) && $qiwiExchange->active)
                            <div class="radio">
                                <label>
                                    <input type="radio" name="receiver" value="exchange" {{ (old('receiver') === 'exchange') ? 'checked' : '' }}>
                                    Отправить обменнику
                                    <div class="form-group has-feedback">
                                        <select name="receiver_id" class="form-control">
                                            <option value="{{ $qiwiExchange->id }}">{{ $qiwiExchange->title }}</option>
                                        </select>
                                        <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                    </div>
                                </label>
                            </div>
                        @endif
                        @if ($errors->has('receiver'))
                            <span class="help-block">
                                <strong>{{ $errors->first('receiver') }}</strong>
                            </span>
                        @endif
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отправить сообщение</button>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-9 -->

        <div class="col-sm-5 col-md-5 col-lg-6 animated fadeIn">
            @include('shop.components.block-message-reminder')
        </div>
    </div> <!-- /.row -->
@endsection