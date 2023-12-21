{{--
This file is part of MM2-dev project.
Description: New message page
--}}
@extends('layouts.master')

@section('title', 'Сообщения')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-8 col-md-9 col-lg-8">
            @if (isset($deleting) && $deleting)
                @include('messages.components.block-deleting')
            @else
                @include('shop.management.messages.components.block-threads', ['threads' => $threads, 'deleting' => false])
            @endif
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-16 col-md-15 col-lg-16 animated fadeIn">
            <div class="well block">
                <h3>Отправить сообщение</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="form-group {{ $errors->has('receiver') ? 'has-error' : '' }}">
                        <input name="receiver" type="text" class="form-control" placeholder="Логин пользователя" required value="{{ (old('receiver') ?: ($receiver ? $receiver->getPublicName() : '')) }}" />
                        @if ($errors->has('receiver'))
                            <span class="help-block">
                                <strong>{{ $errors->first('receiver') }}</strong>
                            </span>
                        @endif
                    </div>

                    <div class="form-group {{ $errors->has('title') ? 'has-error' : '' }}">
                        <input name="title" type="text" class="form-control" placeholder="Тема сообщения" required value="{{ old('title') ?: request('title') }}" />
                        @if ($errors->has('title'))
                            <span class="help-block">
                                <strong>{{ $errors->first('title') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group {{ $errors->has('message') ? 'has-error' : '' }}">
                        <textarea name="message" class="form-control" rows="3" placeholder="Напишите сообщение..." required>{{ old('message') }}</textarea>
                        @if ($errors->has('message'))
                            <span class="help-block">
                                <strong>{{ $errors->first('message') }}</strong>
                            </span>
                        @endif
                    </div>
                    <div class="form-group {{ $errors->has('sender') ? 'has-error' : '' }}">
                        <div class="radio">
                            <label>
                                <input type="radio" name="sender" value="shop" {{ (!old('sender') || old('sender') === 'shop') ? 'checked' : '' }}>
                                Отправить от имени магазина
                                <span class="help-block">Диалог будет создан от имени магазина и станет доступен всем сотрудникам с доступом к сообщениям.</span>
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="sender" value="user" {{ (old('sender') === 'user') ? 'checked' : '' }}>
                                Отправить от имени пользователя
                                <span class="help-block">Диалог будет создан от вашего имени и будет доступен только вам.</span>
                            </label>
                        </div>
                        @if ($errors->has('sender'))
                            <span class="help-block">
                                <strong>{{ $errors->first('sender') }}</strong>
                            </span>
                        @endif
                    </div>
                    <hr class="small" />
                    <button type="submit" class="btn btn-orange">Напишите сообщение</button>
                </form>
            </div>
        </div>
    </div> <!-- /.row -->
@endsection