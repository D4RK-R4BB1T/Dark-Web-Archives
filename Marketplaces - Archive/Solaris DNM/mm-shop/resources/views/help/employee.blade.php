{{--
This file is part of MM2-dev project.
Description: Main page of the shop
--}}
@extends('layouts.master')

@section('title', 'Помощь')

@section('content')
    @include('layouts.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-md-5">
            @include('help.sidebar_employee')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-18 col-md-19 animated fadeIn">
            <div class="well block">
                @if ($content !== null)
                    {!! $content !!}
                @else
                    <div class="alert alert-info" style="margin-bottom: 0">
                        Выберите раздел помощи в меню слева.
                    </div>
                @endif
            </div>
        </div> <!-- /.col-sm-9 -->
    </div> <!-- /.row -->

@endsection