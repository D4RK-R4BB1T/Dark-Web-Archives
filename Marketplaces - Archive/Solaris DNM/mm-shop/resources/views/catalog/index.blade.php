{{-- 
This file is part of MM2 project. 
--}}
@extends('layouts.master')

@section('title', 'Каталог')

@section('content')
    @include('layouts.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('catalog.sidebar')
        </div> <!-- /.col-sm-3 -->

        <div class="col-sm-18 col-lg-19 animated fadeIn">
            @include('layouts.components.component-search')
                @if(count($goods) > 0)
                    @foreach($goods->chunk(4) as $chunk)
                        <div class="row">
                            @foreach($chunk as $good)
                                @include('layouts.components.component-card', ['good' => $good])
                            @endforeach
                        </div>
                    @endforeach
                @else
                    <div class="alert alert-info">Не найдено ни одного товара соответствующего заданным критериям.</div>
                @endif
        </div> <!-- /.col-sm-9 -->
    </div> <!-- /.row -->
@endsection