@extends('layouts.master')

@section('title', 'Промо-коды :: Скидки')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.discounts.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Промо-коды</h3>
                <hr class="small" />
                @if (count($codes) > 0)
                    <div class="table-responsive">
                    <table class="table table-header" style="margin-bottom: 0">
                        <thead>
                        <tr>
                            <td>#</td>
                            <td>Код</td>
                            <td>Величина скидки</td>
                            <td>Режим</td>
                            <td>Действует до</td>
                            <td>Активен</td>
                            <td>Сотрудник</td>
                            <td>Создан</td>
                            <td></td>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($codes as $code)
                            <tr>
                                <td>{{ $code->id }}</td>
                                <td><code>{{ $code->code }}</code></td>
                                <td>{{ $code->getHumanDiscount() }}</td>
                                <td>
                                    @if ($code->mode == \App\Promocode::MODE_SINGLE_USE)
                                        Одноразовый
                                    @elseif ($code->mode == \App\Promocode::MODE_UNTIL_DATE)
                                        Многоразовый
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($code->expires_at)
                                        {{ $code->expires_at->format('d.m.Y') }}</td>
                                    @else
                                        &infin;
                                    @endif
                                <td>
                                    @if ($code->isActive())
                                        <i class="glyphicon glyphicon-ok text-success"></i>
                                    @else
                                        <i class="glyphicon glyphicon-remove text-danger"></i>
                                    @endif
                                </td>
                                <td>@if($employee = traverse($code, 'employee')){{ $employee->getPrivateName() }}@else - @endif</td>
                                <td>{{ $code->created_at->format('d.m.Y в H:i') }}</td>
                                <td class="text-right" style="font-size: 15px">
                                    <a class="dark-link hint--top" aria-label="Редактировать" href="{{ url('/shop/management/discounts/promo/edit/' . $code->id) }}"><i class="glyphicon glyphicon-edit"></i></a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                    @if ($codes->total() > $codes->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $codes->appends(request()->input())->links() }}
                        </div>
                    @endif
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">Промо-кодов не найдено</div>
                @endif

                <hr class="small" />
                <div class="text-center">
                    <a class="btn btn-orange" href="{{ url("/shop/management/discounts/promo/add") }}">Создать промо-код</a>
                </div>

            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->
    </div> <!-- /.row -->
@endsection
