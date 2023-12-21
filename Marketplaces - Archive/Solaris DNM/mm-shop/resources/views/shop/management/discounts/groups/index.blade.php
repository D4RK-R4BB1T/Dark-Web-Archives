@extends('layouts.master')

@section('title', 'Скидочные группы :: Скидки')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.discounts.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Скидочные группы</h3>
                <hr class="small" />
                @if (count($groups) > 0)
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>#</td>
                                <td>Название</td>
                                <td>Величина скидки</td>
                                <td>Режим</td>
                                <td>Пользователи</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($groups as $group)
                                <tr>
                                    <td>{{ $group->id }}</td>
                                    <td>{{ $group->title }}</td>
                                    <td>{{ $group->getHumanDiscount() }}</td>
                                    <td>
                                        @if ($group->mode == \App\UserGroup::MODE_AUTO)
                                            Авто ({{ $group->buy_count }} {{ plural($group->buy_count, ['покупка', 'покупки', 'покупок']) }})
                                        @elseif ($group->mode == \App\UserGroup::MODE_MANUAL)
                                            Ручной <a class="hint--top" aria-label="Управление группой" href="{{ url('/shop/management/discounts/groups/manual/'.$group->id) }}"><i class="glyphicon glyphicon-cog"></i></a>                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ url('/shop/management/stats/users?group=' . $group->id) }}">
                                            {{ $group->users_count }} {{ plural($group->users_count, ['пользователь', 'пользователя', 'пользователей']) }}
                                        </a>
                                    </td>
                                    <td class="text-right" style="font-size: 15px">
                                        <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/discounts/groups/edit/'.$group->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                        &nbsp;
                                        <a class="text-danger hint--top hint--error" aria-label="Удалить" href="{{ url('/shop/management/discounts/groups/delete/'.$group->id) }}"><i class="glyphicon glyphicon-remove"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($groups->total() > $groups->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $groups->appends(request()->input())->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Скидочных групп не найдено</div>
                @endif

                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url("/shop/management/discounts/groups/add") }}">Добавить группу</a>
                </div>
            </div> <!-- /.well -->

            <div class="well block">
                <h3 class="one-line">Мастер назначения групп</h3>
                <hr class="small" />
                Мастер назначения групп позволяет автоматически распределить пользователей по скидочным группам.
                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url("/shop/management/discounts/groups/master") }}">Перейти</a>
                </div>
            </div>
        </div> <!-- /.col-sm-12 -->
    </div> <!-- /.row -->
@endsection