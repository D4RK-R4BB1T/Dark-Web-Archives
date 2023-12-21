{{--
This file is part of MM2-dev project.
Description: Employees sections access page
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
                <h4 class="text-center">Управление магазином</h4>
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_finances" {{ $employee->sections_finances ? 'checked' : '' }} @if(!\Auth::user()->can('management-owner'))disabled @endif> Открыть доступ к финансам
                            </label>
                        </div>
                        <p>
                            Сотрудник сможет работать с кошельками и выплачивать зарплаты <br />
                            <strong>Внимание! Будьте предельно осторожны открывая доступ к данному разделу.</strong>
                        </p>
                    </div>
                    
                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_employees" {{ $employee->sections_employees ? 'checked' : '' }}> Открыть доступ к сотрудникам
                            </label>
                        </div>
                        <p>
                            Сотрудник сможет управлять всеми сотрудниками и менять их права, в том числе свои <br />
                            <strong>Внимание! Будьте предельно осторожны открывая доступ к данному разделу.</strong>
                        </p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_discounts" {{ $employee->sections_discounts ? 'checked' : '' }}> Открыть доступ к скидкам
                            </label>
                        </div>
                        <p>
                            Сотрудник сможет управлять промо-кодами и скидочными группами, включая выдачу промо-кодов на любую сумму.  <br />
                            <strong>Внимание! Будьте предельно осторожны открывая доступ к данному разделу.</strong>
                        </p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_messages" {{ $employee->sections_messages ? 'checked' : '' }}> Открыть доступ к сообщениям
                            </label>
                        </div>
                        <p>Сотрудник сможет просматривать и отвечать на сообщения от лица магазина</p>
                    </div>

                    <div class="access-line level-2">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_orders" {{ $employee->sections_orders ? 'checked' : '' }}> Открыть доступ ко всем заказам
                            </label>
                        </div>
                        <p>Сотрудник сможет просматривать все заказы магазина и информацию о покупателях</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_messages_private" {{ $employee->sections_messages_private ? 'checked' : '' }}> Разрешить <span class="dashed hint--top" aria-label="Не показываются в диалогах магазина">личные</span> сообщения
                            </label>
                        </div>
                        <div>
                            <p>Сотрудник сможет получать личные сообщения от пользователей</p>
                            <div class="row">
                                <div class="col-sm-12">
                                    <input style="height: 25px" type="text" class="form-control" name="sections_messages_private_description" value="{{ $employee->sections_messages_private_description }}" placeholder="Должность (показывается пользователю)" />
                                </div>
                                <div class="col-sm-12">
                                    <label style="font-weight: normal">
                                        <input type="checkbox" name="sections_messages_private_autojoin" {{ $employee->sections_messages_private_autojoin ? 'checked' : '' }}> &nbsp;Добавлять владельца магазина в диалог
                                    </label>
                                </div>
                            </div>
                            <p></p>
                        </div>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_paid_services" {{ $employee->sections_paid_services ? 'checked' : '' }}> Открыть доступ к платным услугам
                            </label>
                        </div>
                        <p>Сотрудник сможет добавлять и редактировать платные услуги</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_settings" {{ $employee->sections_settings ? 'checked' : '' }}> Открыть доступ к настройкам
                            </label>
                        </div>
                        <p>Сотрудник сможет изменять настройки магазина</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_stats" {{ $employee->sections_stats ? 'checked' : '' }}> Открыть доступ к статистике
                            </label>
                        </div>
                        <p>Сотрудник сможет просматривать раздел статистики</p>
                    </div>

{{--                    <div class="access-line">--}}
{{--                        <div class="checkbox">--}}
{{--                            <label>--}}
{{--                                <input type="checkbox" name="sections_qiwi" {{ $employee->sections_qiwi ? 'checked' : '' }}> Открыть доступ к QIWI--}}
{{--                            </label>--}}
{{--                        </div>--}}
{{--                        <p>Сотрудник сможет управлять QIWI-кошельками</p>--}}
{{--                    </div>--}}

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_pages" {{ $employee->sections_pages ? 'checked' : '' }}> Открыть доступ к страницам
                            </label>
                        </div>
                        <p>Сотрудник сможет добавлять и редактировать страницы сайта</p>
                    </div>

                    <div class="access-line">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="sections_moderate" {{ $employee->sections_moderate ? 'checked' : '' }}> Открыть доступ к адресам на модерации
                            </label>
                        </div>
                        <p>Сотрудник сможет управлять списком моменталок находящихся на модерации</p>
                    </div>

                    <hr class="small" />

                    <div class="text-center">
                        <a class="text-muted" href="{{ URL::previous() }}">вернуться назад</a>&nbsp;&nbsp;
                        <button class="btn btn-orange" type="submit">Открыть доступ к выбранным пунктам</button>&nbsp;&nbsp;
                        <a class="text-muted" href="{{ url('/shop/management/employees/'.$employee->id) }}">пропустить шаг</a>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-employees-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection