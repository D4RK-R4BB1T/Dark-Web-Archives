{{--
This file is part of MM2-dev project.
Description: Goods add page
--}}
@extends('layouts.master')

@section('title', 'Добавление товара')

@section('content')
    @include('shop.management.components.sections-menu')
    @include('layouts.components.sections-breadcrumbs', [
    'breadcrumbs' =>
    [
        BREADCRUMB_MANAGEMENT_GOODS,
        ['title' => 'Добавление товара']
    ]])

    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Добавление нового товара</h3>
                <hr class="condensed"/>
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Название товара" value="{{ old('title') }}" required {{ autofocus_on_desktop() }}>

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
                            <div class="form-group has-feedback{{ $errors->has('category') ? ' has-error' : '' }}">
                                <select name="category" class="form-control" title="Категория товара">
                                    <option value="">Категория товара</option>
                                    @foreach (\App\Category::main() as $parent)
                                        @foreach ($parent->children() as $child)
                                            <option value="{{ $child->id }}" {{ old('category') == $child->id ? 'selected' : '' }}>{{ $parent->title }}: {{ $child->title }}</option>
                                        @endforeach
                                    @endforeach
                                </select>
                                <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>

                                @if ($errors->has('category'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('category') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group{{ $errors->has('priority') ? ' has-error' : '' }}">
                                <input id="priority" type="text" class="form-control" name="priority" placeholder="Приоритет (любое число)" value="{{ old('priority') }}">

                                @if ($errors->has('priority'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('priority') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Вы можете выставить приоритет отображения товара в каталоге, где 1 - это самый первый товар.
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-12">
                            <div class="form-group {{ $errors->has('image') ? ' has-error' : '' }}">
                                <div class="col-xs-24 text-center">
                                    <div class="kd-upload" style="width: 100%">
                                        <span><i class="glyphicon glyphicon-upload"></i> <span class="upload-text">Основное изображение</span></span>
                                        <input type="file" name="image" class="upload">
                                    </div>
                                    @if ($errors->has('image'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('image') }}</strong>
                                        </span>
                                    @endif
                                </div>
                            </div> <!-- /.form-group -->
                        </div> <!-- /.col-xs-12 -->
                        <div class="col-xs-12">
                            <div class="form-group {{ $errors->has('additional_images[]') ? ' has-error' : '' }}">
                                <div class="col-xs-24 text-center">
                                    <div class="kd-upload" style="width: 100%">
                                        <span><i class="glyphicon glyphicon-upload"></i> <span class="upload-text">Дополнит. изображения</span></span>
                                        <input type="file" name="additional_images[]" class="upload" multiple>
                                    </div>
                                    @if ($errors->has('additional_images[]'))
                                        <span class="help-block">
                                            <strong>{{ $errors->first('additional_images[]') }}</strong>
                                        </span>
                                    @else
                                        <span class="help-block">Максимум 3. <br />Необзяательно к заполнению.</span>
                                    @endif
                                </div>
                            </div> <!-- /.form-group -->
                        </div> <!-- /.col-xs-12 -->
                    </div> <!-- /.row -->
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group {{ $errors->has('description') ? ' has-error' : '' }}">
                                <textarea class="form-control" name="description" rows="5" title="Описание товара" placeholder="Описание товара">{{ old('description') }}</textarea>
                                @if ($errors->has('description'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('description') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div> <!-- /.col-xs-24 -->
                    </div> <!-- /.row -->
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Создать товар</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-goods-add-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection