{{--
This file is part of MM2-dev project.
Description: Shop management appearance page
--}}
@extends('layouts.master')

@section('title', 'Настройки внешнего вида')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.settings.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <form action="" role="form" method="post" enctype="multipart/form-data">
                {{ csrf_field() }}
                <div class="well block">
                    <h3>Аватар</h3>
                    <hr class="small" />
                    @if ($errors->has('image'))
                        <div class="alert alert-warning">
                            {{ $errors->first('image') }}
                        </div>
                    @endif
                    <div class="row" style="display: table">
                        <div class="col-xs-5 col-sm-7 col-lg-5">
                            <img src="{{ url($shop->avatar()) }}" class="img-responsive" />
                        </div> <!-- /.col-xs-4 -->
                        <div class="col-xs-19 col-sm-17 col-lg-19 text-center" style="display: flex; align-items: center; height: 100px">
                            <p class="text-muted">
                                Необходимо использовать изображения 190x190px и весом до 400 Кб.<br />
                                Поддерживаются форматы JPG, PNG, GIF.
                            </p>
                        </div>
                    </div> <!-- /.row -->
                    <hr class="small" />
                    <div class="text-center">
                        <div class="kd-upload">
                            <span><i class="glyphicon glyphicon-upload"></i> <span class="upload-text">Загрузить аватар</span></span>
                            <input type="file" name="image" class="upload">
                        </div>
                        &nbsp;
                        <button type="submit" class="btn btn-orange">Сохранить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ url('/shop/management/settings/appearance/deleteAvatar?_token='.csrf_token()) }}">удалить</a>
                    </div>
                </div>

                <div class="well block">
                    <h3>Баннер магазина</h3>
                    <hr class="small" />
                    @if ($errors->has('banner'))
                        <div class="alert alert-warning">
                            {{ $errors->first('banner') }}
                        </div>
                    @endif
                    @if ($shop->banner_url)
                        <div class="row">
                            <div class="col-xs-24">
                                <img src="{{ url($shop->banner_url) }}" style="max-width: 100%" />
                            </div>
                        </div>
                    @else
                        <span class="text-muted">Баннер еще не загружен.</span>
                    @endif
                    <hr class="small" />
                    <p class="text-muted">
                        Необходимо использовать изображения не более 1400x470px и весом до 800 Кб. <br />
                        Поддерживаются форматы JPG, PNG.
                    </p>
                    <hr class="small" />
                    <div class="text-center">
                        <div class="kd-upload">
                            <span><i class="glyphicon glyphicon-upload"></i> <span class="upload-text">Загрузить баннер</span></span>
                            <input type="file" name="banner" class="upload">
                        </div>
                        &nbsp;
                        <button type="submit" class="btn btn-orange">Сохранить</button>
                        &nbsp;
                        <a class="text-muted" href="{{ url('/shop/management/settings/appearance/deleteBanner?_token='.csrf_token()) }}">удалить</a>
                    </div>
                </div>

                <div class="well block">
                    <h3>Название магазина</h3>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-20 col-xs-offset-2">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}" style="margin-bottom: 0">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Введите название магазина" value="{{ old('title') ?: $shop->title }}" required>
                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Изменить название</button>
                    </div>
                </div>

                <div class="well block">
                    <h3>Гостевой доступ</h3>
                    <hr class="small" />
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="guest_enabled" {{ $shop->guest_enabled ? 'checked' : '' }}> Разрешить просмотр товаров незарегистрированным пользователям
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