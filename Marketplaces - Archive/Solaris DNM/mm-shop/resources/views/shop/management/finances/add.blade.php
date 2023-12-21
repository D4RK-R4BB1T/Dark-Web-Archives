{{--
This file is part of MM2-dev project.
Description: Finances wallets create page
--}}
@extends('layouts.master')

@section('title', 'Добавление кошелька :: Финансы')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.finances.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3 class="one-line">Добавление кошелька</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-20 col-xs-offset-2">
                            <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}" style="margin-bottom: 0">
                                <input id="title" type="text" class="form-control" name="title" placeholder="Введите название нового кошелька" value="{{ old('title') }}" required {{ autofocus_on_desktop() }}>
                                @if ($errors->has('title'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('title') }}</strong>
                                    </span>
                                @else
                                    <span class="help-block">
                                        Максимальное количество символов для названия - 20.
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Добавить кошелек</button>
                    </div>
                </form>
            </div> <!-- /.row -->
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('shop.management.components.block-finances-reminder')
        </div> <!-- /.col-sm-6 -->

    </div> <!-- /.row -->
@endsection