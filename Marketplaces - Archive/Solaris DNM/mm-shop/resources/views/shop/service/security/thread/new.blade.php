{{--
This file is part of MM2-dev project.
Description: New message page
--}}
@extends('layouts.master')

@section('title', 'Сообщения')

@section('content')
    <div class="row">
        <div class="col-sm-8 col-md-9 col-lg-8">
            @if (isset($deleting) && $deleting)
                @include('messages.components.block-deleting')
            @else
                @include('messages.components.block-threads', ['threads' => $threads, 'deleting' => false])
            @endif
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-16 col-md-15 col-lg-16 animated fadeIn">
            <div class="well block">
                <h3>Отправить сообщение</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
{{--                    <div class="form-group {{ $errors->has('receiver') ? 'has-error' : '' }}">--}}
{{--                        <input name="receiver" type="text" class="form-control" placeholder="Логин пользователя" required value="{{ (old('receiver') ?: (isset($employee) ? $employee->getPublicName() : '')) }}" />--}}
{{--                        @if ($errors->has('receiver'))--}}
{{--                            <span class="help-block">--}}
{{--                                <strong>{{ $errors->first('receiver') }}</strong>--}}
{{--                            </span>--}}
{{--                        @endif--}}
{{--                    </div>--}}
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
                            </label>

                            Отправить лично сотруднику магазина

                            <div class="margin-bottom-1"></div>

                            <div class="form-group has-feedback">
                                <select name="receiver_id" class="form-control">
                                    @foreach ($employees as $employee)
                                        <option value="{{ $employee->id }}" {{ (old('receiver_id') == $employee->id) ? 'selected' : '' }}>
                                            {{ $employee->getPrivateName() }}
                                            @if ($employee->sections_messages_private_description)
                                                ({{ $employee->sections_messages_private_description }})
                                            @endif
                                        </option>
                                    @endforeach
                                    <option value="1">{{ \App\Shop::getDefaultShop()->owner()->getPublicName() }} (Владелец магазина)</option>
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                            </div>
                            <span class="help-block">Диалог будет доступен только выбранному сотруднику.</span>

                        </div>
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
                    <hr class="small" />
                    <button type="submit" class="btn btn-orange">Напишите сообщение</button>
                </form>
            </div>
        </div>
    </div> <!-- /.row -->
@endsection