@extends('layouts.master')

@section('title', 'Ошибка 500')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">Ошибка 500</div>
                <div class="panel-body">
                    <p>
                        При обработке запроса возникла непредвиденная ошибка сервера.
                        Мы сохранили отчет и в ближайшее время начнем ее исправление.
                    </p>
                    <hr />
                    <a class="btn btn-orange" href="{{ URL::previous() }}">Вернуться назад</a>
                    <a class="btn btn-orange" href="{{ url('/') }}">На главную</a>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection