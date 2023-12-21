{{-- 
This file is part of MM2-dev project. 
Description: Shop page creation
--}}
@extends('layouts.master')

@section('title', 'Добавление страницы')

@section('content')
    @include('layouts.components.sections-menu')
    @if (Auth::user()->employee)
        @include('shop.management.components.sections-menu')
    @endif
    @include('layouts.components.sections-breadcrumbs', [
        'breadcrumbs' => [
            // BREADCRUMB_CATALOG,
            // BREADCRUMB_SHOPS,
            ['title' => $shop->title, 'url' => url('/shop/' . $shop->slug)],
            ['title' => 'Добавление страницы']
        ]
    ])
    @include('shop.sections-pages-menu', [
        'page' => 'page-add'
    ])
    <div class="row">
        <div class="col-sm-24 animated fadeIn">
            <form role="form" action="" method="post">
                {{ csrf_field() }}
                <div class="well block">
                    <h3>Добавление страницы</h3>
                    <hr class="small" />
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Название страницы" value="{{ old('title') }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('body') ? ' has-error' : '' }}">
                                <textarea rows="15" class="form-control" name="body" placeholder="Содержимое страницы..." required>{{ old('body') }}</textarea>
                                @if ($errors->has('body'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('body') }}</strong>
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
                        <button type="submit" class="btn btn-orange">Добавить страницу</button>
                    </div>
                </div>
            </form>
        </div> <!-- /.col-sm-9 -->
    </div> <!-- /.row -->
@endsection