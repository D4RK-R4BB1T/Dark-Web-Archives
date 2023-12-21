@extends('layouts.master')

@section('title', 'Магазин недоступен')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">Доступ в магазин закрыт.</div>
                <div class="panel-body">
                    <p>
                        @if(!$shop->disabled_reason)
                            Сейчас магазин недоступен. Заходите позже.
                        @else
                            {{ $shop->disabled_reason }}
                        @endif
                    </p>
                    <hr />
                    <a class="btn btn-orange" href="{{ url('/') }}">Обновить</a>
                </div>
            </div>
        </div>
    </div>
@endsection