{{--
This file is part of MM2 project.
Description: Initial setup of shop.
--}}
@extends('layouts.master')

@section('title', 'Настройка магазина')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-12 col-md-offset-6 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">Создание магазина</div>
                <div class="panel-body">
                    <form enctype="multipart/form-data" class="form-horizontal" role="form" method="POST" action="{{ url('/shop/management/init') }}">
                        {{ csrf_field() }}
                        <div class="form-group {{ $errors->has('title') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="title" type="text" class="form-control" name="title" value="{{ old('title') }}" placeholder="Введите название магазина" {{ autofocus_on_desktop() }}>
                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.form-group -->

                        <div class="form-group {{ $errors->has('slug') ? ' has-error' : '' }}">
                            <div class="col-md-24">
                                <input id="slug" type="text" class="form-control" name="slug" value="{{ old('slug') }}" placeholder="Введите адрес магазина">
                                @if ($errors->has('slug'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('slug') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Ваш магазин будет иметь адрес вида {{ url('/') }}/shop/<strong>address</strong>.
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.form-group -->

                        <div class="form-group {{ $errors->has('image') ? ' has-error' : '' }}">
                            <div class="col-md-24 text-center">
                                <div class="kd-upload">
                                    <span><i class="glyphicon glyphicon-upload"></i> <span class="upload-text">Загрузите логотип</span></span>
                                    <input type="file" name="image" class="upload">
                                </div>
                                @if ($errors->has('image'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('image') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Необязательно к заполнению.
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.form-group -->

                        <div class="row">
                            <div class="col-md-16 col-md-offset-4 text-center">
                                <button type="submit" class="btn btn-lg btn-orange">
                                    Создать магазин
                                </button>
                            </div>
                        </div>

                        <br />

                        <p class="text-muted">
                            Подробную информацию о создании и работе моментальных магазинов можно прочитать <a href="#">здесь</a>.
                        </p>

                    </form>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel-modal -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection