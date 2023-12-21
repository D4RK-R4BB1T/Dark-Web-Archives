{{--
This file is part of MM2-dev project.
Description: Employees quests access page
--}}
@extends('layouts.master')

@section('title', 'Настройки доступа :: Сотрудники')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.employees.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Настройки доступа для {{ $employee->getPrivateName() }}</h3>
                <hr class="small" />
                <h4 class="text-center">Доступ к квестам</h4>
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_create" {{ $employee->quests_create ? 'checked' : '' }}> Разрешить загрузку квестов
                            </label>
                        </div>
                        <p>Сотрудник сможет загружать квесты для всех имеющихся в магазине товаров</p>
                    </div>

                    <div class="access-line level-2">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_moderate" {{ $employee->quests_moderate ? 'checked' : '' }}> Модерация загруженных квестов
                            </label>
                        </div>
                        <p>Перед тем как попасть на витрину, квесты должны быть проверены администратором</p>
                    </div>

                    <div class="access-line level-2">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_edit" {{ $employee->quests_edit ? 'checked' : '' }}> Редактирование своих квестов
                            </label>
                        </div>
                        <p>Сотрудник сможет редактировать свои загруженные адреса</p>
                    </div>

                    <div class="access-line level-2">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_delete" {{ $employee->quests_delete ? 'checked' : '' }}> Удаление своих квестов
                            </label>
                        </div>
                        <p>Сотрудник сможет удалять свои загруженные адреса</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_own_orders" {{ $employee->sections_own_orders ? 'checked' : '' }}> Разрешить просмотр своих заказов
                            </label>
                        </div>
                        <p>Сотрудник будет иметь доступ к разделу Заказы и видеть только свои заказы</p>
                    </div>

                    <div class="access-line level-2 text-center">
                        <label class="no-padding-l">Разрешить сотруднику загрузку квестов только для отдельных товаров</label>
                    </div>
                    @foreach($goods->chunk(2) as $i => $chunks)
                        <div class="access-line level-2">
                            <div class="row no-padding-l">
                                <div class="access-line-checks col-xs-11 col-xs-offset-1 no-padding-l">
                                    <div class="checkbox">
                                        <label>
                                            <input name="quests_allowed_goods[]" type="checkbox" value="{{ $chunks->first()->id }}" {{ is_array($employee->quests_allowed_goods) && in_array($chunks->first()->id, $employee->quests_allowed_goods) ? 'checked' : '' }}> {{ $chunks->first()->title }} <span class="text-muted">({{ $chunks->first()->cities->map(function($c) { return $c->title; })->implode(', ') }})</span>
                                        </label>
                                    </div>
                                </div>
                                @if ($chunks->count() > 1) {{-- this condition is only for latest chunk --}}
                                <div class="col-xs-11">
                                    <div class="checkbox">
                                        <label>
                                            <input name="quests_allowed_goods[]" type="checkbox" value="{{ $chunks->last()->id }}" {{ is_array($employee->quests_allowed_goods) && in_array($chunks->last()->id, $employee->quests_allowed_goods) ? 'checked' : '' }}> {{ $chunks->last()->title }} <span class="text-muted">({{ $chunks->last()->cities->map(function($c) { return $c->title; })->implode(', ') }})</span>
                                        </label>
                                    </div>
                                </div>
                                @endif
                            </div>
                        </div>
                    @endforeach

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_only_own_city" {{ $employee->quests_only_own_city ? 'checked' : '' }}> Ограничить загрузку квестов городом сотрудника
                            </label>
                        </div>
                        <p>Сотрудник сможет загружать квесты только для своего города</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_preorders" {{ $employee->quests_preorders ? 'checked' : '' }}> Разрешить выдачу предзаказов
                            </label>
                        </div>
                        <p>Сотрудник сможет выдавать предзаказы для товаров, к которым у него есть доступ</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_not_only_own" {{ $employee->quests_not_only_own ? 'checked' : '' }}> Разрешить просмотр чужих квестов
                            </label>
                        </div>
                        <p>Сотрудник сможет видеть квесты, загруженные другими сотрудниками</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="quests_autojoin" {{ $employee->quests_autojoin ? 'checked' : '' }}> Автоматически добавлять сотрудника к его проблемным заказам
                            </label>
                        </div>
                        <p>Сотрудник будет автоматически добавлен в диалог, если его заказ отмечен проблемным</p>
                    </div>

                    <hr class="small" />

                    <div class="text-center">
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>&nbsp;&nbsp;
                        <button class="btn btn-orange" type="submit">Открыть доступ к выбранным пунктам</button>&nbsp;&nbsp;
                        <a class="text-muted" href="{{ url('/shop/management/employees/access/sections/'.$employee->id) }}">пропустить шаг</a>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-employees-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection