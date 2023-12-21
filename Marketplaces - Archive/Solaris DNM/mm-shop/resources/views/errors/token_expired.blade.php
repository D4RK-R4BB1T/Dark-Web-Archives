@extends('layouts.master')

@section('title', 'Ошибка авторизации')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">Ошибка авторизации</div>
                <div class="panel-body">
                    <p>
                        Не получилось авторизовать вас как пользователя каталога. <br />
                        Пожалуйста, вернитесь на страницу каталога и попробуйте еще раз.
                    </p>
                    <hr />
                    <a class="btn btn-orange" href="{{ url('/') }}">На главную</a>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection