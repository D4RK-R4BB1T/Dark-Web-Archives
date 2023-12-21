@extends('layouts.master')

@section('title', 'Мастер назначения групп :: Скидки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_DISCOUNTS,
        ['title' => 'Скидочные группы', 'url' => url('/shop/management/discounts/groups')],
        ['title' => 'Мастер назначения групп']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.discounts.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Мастер назначения групп</h3>
                <hr class="small" />
                <div class="alert alert-info">
                    Данный мастер поможет вам автоматически распределить пользователей по группам в зависимости от их числа покупок. <br />
                    Мастер работает используя следующие правила: <br />
                    <strong>1.</strong> Если пользователь находится в группе с <strong>ручным</strong> управлением - распределение не осуществляется.<br />
                    <strong>2.</strong> Если пользователь не принадлежит никакой группе - он будет распределен в новую согласно кол-ву покупок.<br />
                    <strong>3.</strong> Если пользователь находится в группе с <strong>автоматическим</strong> управлением, но кол-во покупок позволяет перенести его в группу с большим количеством покупок - он будет распределен в группу согласно кол-ву покупок.<br />
                </div>
                <hr class="small" />
                @if (count($users) > 0)
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Пользователь</td>
                                <td>Кол-во покупок</td>
                                <td>Текущая группа</td>
                                <td>Новая группа</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($users as $user)
                                <tr>
                                    <td>{{ $user->getPublicName() }}</td>
                                    <td>{{ $user->buy_count }}</td>
                                    <td>
                                        {{ traverse($user, 'group->title') ?: '(группа не задана)' }} ({{ $user->group ? $user->group->getHumanDiscount() : '0 %' }})
                                    </td>
                                    <td>
                                        <strong>{{ $user->suggestDiscountGroup()->title }}</strong> ({{ $user->suggestDiscountGroup()->getHumanDiscount() }})
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <form action="" method="post">
                            {{ csrf_field() }}
                            <button type="submit" class="btn btn-orange" href="{{ url("/shop/management/discounts/groups/add") }}">Распределить пользователей</button>
                        </form>
                    </div>
                @else
                    <div class="alert alert-warning" style="margin-bottom: 0">Пользователи, которые могли бы быть распределены автоматически, не найдены.</div>
                @endif
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->
    </div> <!-- /.row -->
@endsection