@extends('layouts.master')

@section('title', 'Управление группой :: Скидки')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_DISCOUNTS,
        ['title' => 'Скидочные группы', 'url' => url('/shop/management/discounts/groups')],
        ['title' => 'Управление группой']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.discounts.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Пользователи группы</h3>
                <hr class="small" />
                @if (count($users) > 0)
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Пользователь</td>
                                <td>Кол-во покупок</td>
                                <td>Дата регистрации</td>
                                <td>Последний вход</td>
                                <td></td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($users as $user)
                                <tr>
                                    <td>{{ $user->getPublicName() }}</td>
                                    <td>
                                        @can('management-sections-orders')
                                            <a class="hint--top" aria-label="Посмотреть покупки" href="{{ url('/shop/management/orders?user='.$user->id) }}">
                                                {{ $user->buy_count }} {{ plural($user->buy_count, ['покупка', 'покупки', 'покупок']) }}
                                            </a>
                                        @else
                                            {{ $user->buy_count }} {{ plural($user->buy_count, ['покупка', 'покупки', 'покупок']) }}
                                        @endcan
                                    </td>
                                    <td>{{ $user->created_at->format('d.m.Y в H:i') }}</td>
                                    <td>{{ ($date = $user->getLastLogin()) ? $date->format('d.m.Y в H:i') : '-' }}</td>
                                    <td class="text-right" style="font-size: 15px">
                                        <a class="text-danger hint--top hint--error" aria-label="Удалить из группы" href="{{ url('/shop/management/discounts/groups/manual/delete/'.$group->id.'/'.$user->id.'?_token='.csrf_token()) }}"><i class="glyphicon glyphicon-remove"></i></a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($users->total() > $users->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $users->appends(request()->input())->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Пользователей не найдено</div>
                @endif
            </div> <!-- /.well -->

            <div class="well block">
                <h3 class="one-line">Добавить пользователя в группу</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-18 col-xs-offset-3">
                            <div class="form-group{{ $errors->has('username') ? ' has-error' : '' }}">
                                <input id="username" type="text" class="form-control" name="username" placeholder="Введите имя пользователя" value="{{ old('username') }}" required>

                                @if ($errors->has('username'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('username') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->

                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Добавить в группу</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->
    </div> <!-- /.row -->
@endsection