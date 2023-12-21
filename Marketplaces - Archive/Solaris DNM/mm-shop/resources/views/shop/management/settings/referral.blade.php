@extends('layouts.master')

@section('title', 'Настройки реферальной системы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.settings.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" role="form" method="post">
                {{ csrf_field() }}
                <div class="well block">
                    <h3>Реферальная система</h3>
                    <hr class="small" />
                    <p class="text-muted">
                        Реферальная система позволяет пользователям создавать пользователям уникальные ссылки на магазин и задавать комиссию за продажу.
                        <br /><br />
                        Комиссия для ссылки задается пользователем, приглашенные пользователи будут видеть увеличенные цены на сайте. При продаже, комиссия будет поступать на счёт пригласившего.
                        <br /><br />
                        <strong>Важно!</strong> Отключение реферальной системы отключает работу сгенерированных ссылок, приглашенные пользователи не смогут зайти на сайт.
                    </p>
                    <hr class="small" />
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="referral_enabled" {{ $shop->referral_enabled ? 'checked' : '' }}> Включить реферальную систему
                            </label>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-settings-appearance-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection