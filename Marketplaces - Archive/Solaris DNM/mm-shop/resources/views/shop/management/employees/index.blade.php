{{--
This file is part of MM2-dev project.
Description: Employees activity list
--}}
@extends('layouts.master')

@section('title', 'Лента действий :: Сотрудники')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.employees.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('shop.management.employees.components.component-search')
            <div class="well block">
                <h3 class="one-line">Лента действий работников магазина</h3>
                <hr class="small" />
                @if ($employeesLog->count() > 0)
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td>Работник</td>
                            <td>Название</td>
                            <td>Действие работника</td>
                            <td>Дата</td>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($employeesLog as $item)
                            <tr>
                                <td>@if($employee = traverse($item, 'employee')){{ $employee->getPrivateName() }}@else - @endif</td>
                                <td>
                                    @if (in_array($item->action, [
                                        \App\EmployeesLog::ACTION_GOODS_ADD, \App\EmployeesLog::ACTION_GOODS_EDIT, \App\EmployeesLog::ACTION_GOODS_DELETE,
                                        \App\EmployeesLog::ACTION_PACKAGES_ADD, \App\EmployeesLog::ACTION_PACKAGES_EDIT, \App\EmployeesLog::ACTION_PACKAGES_DELETE,
                                        \App\EmployeesLog::ACTION_QUESTS_ADD, \App\EmployeesLog::ACTION_QUESTS_EDIT, \App\EmployeesLog::ACTION_QUESTS_DELETE,
                                        \App\EmployeesLog::ACTION_ORDERS_PREORDER, \App\EmployeesLog::ACTION_QUESTS_MODERATE_ACCEPT, \App\EmployeesLog::ACTION_QUESTS_MODERATE_DECLINE
                                    ]))
                                        {{ $item->data['good_title'] }}
                                    @elseif(in_array($item->action, [
                                        \App\EmployeesLog::ACTION_SETTINGS_PAGE_ADD, \App\EmployeesLog::ACTION_SETTINGS_PAGE_EDIT, \App\EmployeesLog::ACTION_SETTINGS_PAGE_DELETE
                                    ]))
                                        {{ $item->data['page_title'] }}
                                    @elseif($item->action === \App\EmployeesLog::ACTION_FINANCE_PAYOUT)
                                        Выплата средств сотруднику
                                    @endif
                                </td>
                                <td>{{ $item->getHumanAction() }}</td>
                                <td>{{ $item->created_at->format('d.m.Y в H:i') }}</td>
                                <td>
                                    @if (in_array($item->action, [\App\EmployeesLog::ACTION_GOODS_ADD, \App\EmployeesLog::ACTION_GOODS_EDIT, \App\EmployeesLog::ACTION_GOODS_DELETE, \App\EmployeesLog::ACTION_QUESTS_MODERATE_ACCEPT, \App\EmployeesLog::ACTION_QUESTS_MODERATE_DECLINE]))
                                        @if ($item->good)
                                            <a class="dark-link" href="{{ url('/shop/'.$shop->slug.'/goods/'.$item->good_id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                        @else
                                            <span class="hint--left" aria-label='Товар "{{ $item->data['good_title'] }}" удален'><i class="glyphicon glyphicon-eye-close"></i></span>
                                        @endif
                                    @elseif(in_array($item->action, [\App\EmployeesLog::ACTION_PACKAGES_ADD, \App\EmployeesLog::ACTION_PACKAGES_EDIT, \App\EmployeesLog::ACTION_PACKAGES_DELETE]))
                                        @if ($item->good)
                                            @if ($item->package)
                                                <a class="dark-link" href="{{ url('/shop/management/goods/packages/'.$item->good_id.'?highlight='.$item->package_id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                            @else
                                                <span class="hint--left" aria-label='Упаковка не найдена'><i class="glyphicon glyphicon-eye-close"></i></span>
                                            @endif
                                        @else
                                            <span class="hint--left" aria-label='Товар "{{ $item->data['good_title'] }}" удален'><i class="glyphicon glyphicon-eye-close"></i></span>
                                        @endif
                                    @elseif(in_array($item->action, [\App\EmployeesLog::ACTION_QUESTS_ADD, \App\EmployeesLog::ACTION_QUESTS_EDIT, \App\EmployeesLog::ACTION_QUESTS_DELETE]))
                                        @if ($item->good && $item->package && $item->position && $item->position->available)
                                            <a class="dark-link" href="{{ url('/shop/management/goods/quests/view/'.$item->good_id.'/'.$item->position_id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                        @elseif ($item->good && $item->package)
                                            <a class="dark-link" href="{{ url('/shop/management/goods/quests/'.$item->good_id.'/'.$item->package_id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                        @elseif (!$item->package)
                                            <span class="hint--left" aria-label='Упаковка с этим квестом удалена'><i class="glyphicon glyphicon-eye-close"></i></span>
                                        @else
                                            <span class="hint--left" aria-label='Товар "{{ $item->data['good_title'] }}" удален'><i class="glyphicon glyphicon-eye-close"></i></span>
                                        @endif
                                    @elseif(in_array($item->action, [\App\EmployeesLog::ACTION_ORDERS_PREORDER]))
                                        @if ($item->order)
                                            <a class="dark-link" href="{{ url('/shop/management/orders/'.$item->order_id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                        @else
                                            <span class="hint--left" aria-label='Заказ не найден'><i class="glyphicon glyphicon-eye-close"></i></span>
                                        @endif
                                    @elseif($item->action == \App\EmployeesLog::ACTION_FINANCE_PAYOUT)
                                        <a class="dark-link" href="{{ url('/shop/management/finances/employee/'.$item->data['employee_id'].'?show=payouts') }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                    @elseif(in_array($item->action, [\App\EmployeesLog::ACTION_SETTINGS_PAGE_ADD, \App\EmployeesLog::ACTION_SETTINGS_PAGE_EDIT, \App\EmployeesLog::ACTION_SETTINGS_PAGE_DELETE]))
                                        @if ($item->page)
                                            <a class="dark-link" href="{{ url('/shop/'.$shop->slug.'/pages/'.$item->page_id) }}"><i class="glyphicon glyphicon-eye-open"></i></a>
                                        @else
                                            <span class="hint--left" aria-label='Страница не найдена'><i class="glyphicon glyphicon-eye-close"></i></span>
                                        @endif
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    @if ($employeesLog->total() > $employeesLog->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $employeesLog->appends(request()->input())->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Нет информации за данный период.</div>
                @endif
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->
    </div> <!-- /.row -->
@endsection