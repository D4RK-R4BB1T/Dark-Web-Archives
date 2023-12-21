{{--
This file is part of MM2-dev project.
Description: Shop management blocks page
--}}
@extends('layouts.master')

@section('title', 'Настройки блоков')

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
                    <h3>Блок информации</h3>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('information') ? ' has-error' : '' }}">
                                <textarea rows="10" class="form-control" name="information" placeholder="Содержимое блока информации...">{{ old('information') ?: $shop->information }}</textarea>
                                @if ($errors->has('information'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('information') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Поддерживается синтаксис <a href="{{ url("/help/employee/markdown") }}" target="_blank">Markdown</a>.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                    </div>
                </div>

                <div class="well block">
                    <h3>Блок проблемных заказов</h3>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('problem') ? ' has-error' : '' }}">
                                <textarea rows="6" class="form-control" name="problem" placeholder="Содержимое блока проблемных заказов...">{{ old('problem') ?: $shop->problem }}</textarea>
                                @if ($errors->has('problem'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('problem') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Поддерживается синтаксис <a href="{{ url("/help/employee/markdown") }}" target="_blank">Markdown</a>.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                    </div>
                </div>

                <div class="well block">
                    <h3>Блок поиска</h3>
                    <hr class="small" />
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="search_enabled" {{ ($shop->search_enabled) ? 'checked' : '' }}> Показывать блок поиска на главной странице магазина
                            </label>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить изменения</button>
                    </div>
                </div>

                <div class="well block">
                    <h3>Блок категорий</h3>
                    <hr class="small" />
                    <div class="form-group">
                        <div class="checkbox">
                            <label>
                                <input type="checkbox" name="categories_enabled" {{ ($shop->categories_enabled) ? 'checked' : '' }}> Показывать блок категорий на главной странице магазина
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