{{--
This file is part of MM2-dev project.
Description: Employees add page
--}}
@extends('layouts.master')

@section('title', $employee->getPrivateName() . ' :: Сотрудники')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.employees.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" method="post" role="">
                {{ csrf_field() }}
                <div class="well block">
                <h3 class="one-line">
                    Сотрудник магазина: {{ $employee->getPrivateName() }}
                    @if ($employee->role !== \App\Employee::ROLE_OWNER && Auth::user()->can('management-sections-messages'))
                         <a href="{{ url('/shop/management/messages/new?user='.$employee->user->id) }}" class="hint--top" aria-label="Отправить сообщение"><i style="font-size: 13px" class="glyphicon glyphicon-envelope"></i></a>
                    @endif
                </h3>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-6 col-sm-6 col-md-5 col-lg-6">
                        <span class="text-muted">Настройки доступа:</span>
                    </div>
                    <div class="col-xs-18 col-sm-18 col-md-11">
                        Финансы: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_finances) ? 'Да' : 'Нет' }}</span> <br />
                        Сотрудники: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_employees) ? 'Да' : 'Нет' }}</span> <br />
                        Заказы: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_orders) ? 'Все' : 'Свои' }}</span> <br />
                        Сообщения: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_messages) ? 'Да' : 'Нет' }}</span> <br />
                        Платные услуги: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_paid_services) ? 'Да' : 'Нет' }}</span> <br />
                        Настройки: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_settings) ? 'Да' : 'Нет' }}</span> <br />
                        Страницы: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_pages) ? 'Да' : 'Нет' }}</span> <br />
                        Статистика: <span class="pull-right text-danger">{{ ($employee->role == \App\Employee::ROLE_OWNER || $employee->sections_stats) ? 'Да' : 'Нет' }}</span> <br />

                        Работа с товарами:
                        <?php
                            $c = $employee->goods_create;
                            $e = $employee->goods_edit;
                            $a = !$employee->goods_only_own_city; // all: not only in own city
                        ?>
                        @if ($employee->role == \App\Employee::ROLE_OWNER || ($c && $e && $a))
                            <span class="pull-right text-danger hint--top" aria-label="Работа с товарами полностью разрешена">Да</span>
                        @elseif ($c || $e || !$a)
                            <span class="pull-right text-danger hint--top" aria-label="Создание: {{ $c ? 'да' : 'нет' }}. Редактирование: {{ $e ? 'да' : 'нет' }}. Только в своем городе: {{ $a ? 'нет' : 'да' }}.">Частично</span>
                        @else
                            <span class="pull-right text-danger hint--top" aria-label="Работа с товарами запрещена">Нет</span>
                        @endif
                        <br />
                        Загрузка квестов:
                        <?php
                            $c = $employee->quests_create;
                            $a = !$employee->quests_only_own_city; // all: not only in own city
                            $cnt = 0;
                            $s = is_array($employee->quests_allowed_goods) && ($cnt = count($employee->quests_allowed_goods)) > 0;
                        ?>
                        @if ($employee->role == \App\Employee::ROLE_OWNER || ($c && $a))
                            <span class="pull-right text-danger hint--top" aria-label="Загрузка квестов полностью разрешена">Да</span>
                        @elseif (!$a || $s)
                            <span class="pull-right text-danger hint--top" aria-label="{{ $c ? 'Все товары' : 'Отмеченные товары: ' . $cnt }}. Только в своем городе: {{ $a ? 'нет' : 'да' }}.">Частично</span>
                        @else
                            <span class="pull-right text-danger hint--top" aria-label="Работа с квестами запрещена">Нет</span>
                        @endif
                    </div>
                    <div class="col-xs-24 col-sm-24 col-md-8 col-lg-7 text-center">
                        @if ($employee->role == \App\Employee::ROLE_OWNER)
                            <button class="btn btn-orange" disabled><span class="hint--top" aria-label="Сотрудник - владелец магазина.">Настройки доступа</span></button>
                        @else
                            <a class="btn btn-orange" href="{{ url('/shop/management/employees/access/goods/'.$employee->id) }}">Настройки доступа</a>
                        @endif
                    </div>
                </div>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-6 col-lg-6">
                        <span class="text-muted">Город:</span>
                    </div>
                    <div class="col-xs-10 col-lg-11">
                        @if ($editing)
                            <div class="form-group has-feedback {{ $errors->has('city') ? 'has-error' : '' }}">
                                <select name="city" class="form-control" title="Выберите город">
                                    <option value="">Выберите город</option>
                                    @foreach (\App\City::allReal() as $city)
                                        <option value="{{ $city->id }}" {{ (old('city') ?: $employee->city_id) == $city->id ? 'selected' : '' }}>{{ $city->title }}</option>
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                                @if ($errors->has('city'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('category') }}</strong>
                                    </span>
                                @endif
                            </div>
                        @else
                            {{ traverse($employee, 'city->title') ?: '-' }}
                        @endif
                    </div>
                    <div class="col-xs-8 col-lg-7 text-center">
                        @if ($editing)
                            <button type="submit" class="btn btn-orange">Изменить город</button>
                        @else
                            <a class="text-muted" href="{{ url('/shop/management/employees/edit/'.$employee->id) }}">изменить город</a>
                        @endif
                    </div>
                </div>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-6 col-lg-6">
                        <span class="text-muted">Стаж:</span>
                    </div>
                    <div class="col-xs-18">
                        Работает с {{ $employee->created_at->format('d.m.Y') }}
                    </div>
                </div>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-6 col-lg-6">
                        <span class="text-muted">Статистика:</span>
                    </div>
                    <div class="col-xs-18 col-lg-18">
                        Загружено <span class="text-danger">{{ $count = $employee->positions()->count() }}</span> {{ plural($count, ['квест', 'квеста', 'квестов']) }} (<span class="text-danger">{{ $employee->orders()->sum('status_was_problem') }}</span> проблемных)<br />
                        Средняя оценка работы: <span class="text-danger">{{ $employee->getRating() }}</span>
                    </div>
                </div>
                <hr class="small" />
                <div class="row">
                    <div class="col-xs-6 col-lg-6">
                        <span class="text-muted">Заметка:</span>
                    </div>
                    <div class="col-xs-10 col-lg-11">
                        @if ($editing)
                            <div class="form-group {{ $errors->has('note') ? 'has-error' : '' }}">
                                <textarea name="note" class="form-control" placeholder="Введите текст заметки">{{ old('note') ?: $employee->note }}</textarea>
                                @if ($errors->has('note'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('note') }}</strong>
                                    </span>
                                @endif
                            </div>
                        @else
                            {!! nl2br(e($employee->note)) ?: '-' !!}
                        @endif
                    </div>
                    <div class="col-xs-8 col-lg-7 text-center">
                        @if ($editing)
                            <button type="submit" class="btn btn-orange">Изменить заметку</button>
                        @else
                            <a class="text-muted" href="{{ url('/shop/management/employees/edit/'.$employee->id) }}">изменить заметку</a>
                        @endif
                    </div>
                </div>
                @if ($employee->role !== \App\Employee::ROLE_OWNER)
                    <hr class="small" />
                    <div class="text-center">
                        Вы можете <a href="{{ url('/shop/management/employees/delete/'.$employee->id) }}">уволить сотрудника</a>.
                    </div>
                @endif
            </div> <!-- /.row -->
            </form>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-employees-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection