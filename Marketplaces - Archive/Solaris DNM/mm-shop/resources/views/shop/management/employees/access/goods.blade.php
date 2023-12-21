{{--
This file is part of MM2-dev project.
Description: Employees goods access page
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
                <h4 class="text-center">Доступ к товарам</h4>
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="goods_all" {{ ($employee->goods_create && $employee->goods_delete && $employee->goods_edit) ? 'checked' : '' }}> Открыть доступ к настройкам товара
                            </label>
                        </div>
                        <p>Сотруднику будет предоставлен полный доступ ко всем настройкам товаров в магазине</p>
                    </div>

                    <div class="access-line level-2">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="goods_create_delete" {{ ($employee->goods_create && $employee->goods_delete) ? 'checked' : '' }}> Создание и удаление товаров
                            </label>
                        </div>
                        <p>Сотрудник сможет только создавать товары</p>
                    </div>

                    <div class="access-line level-2">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="goods_edit" {{ $employee->goods_edit ? 'checked' : '' }}> Редактирование товаров
                            </label>
                        </div>
                        <p>Сотрудник сможет только редактировать товары</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="goods_only_own_city" {{ $employee->goods_only_own_city ? 'checked' : '' }}> Ограничить настройку товаров городом сотрудника
                            </label>
                        </div>
                        <p>Сотрудник сможет работать только с товарами из своего города</p>
                    </div>

                    <hr class="small" />

                    <div class="text-center">
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>&nbsp;&nbsp;
                        <button class="btn btn-orange" type="submit">Открыть доступ к выбранным пунктам</button>&nbsp;&nbsp;
                        <a class="text-muted" href="{{ url('/shop/management/employees/access/quests/'.$employee->id) }}">пропустить шаг</a>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-employees-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection