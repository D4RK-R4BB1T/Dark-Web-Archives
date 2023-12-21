@extends('layouts.master', ['hide_header' => true, 'hide_modals' => true])

@section('title', 'Магазин недоступен')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">Небезопасный вход</div>
                <div class="panel-body">
                    <p>
                        Не удалось идентифицировать вас в качестве доверенного пользователя.
                        В целях безопасности вход в магазин возможен только по специальной ссылке.
                    </p>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection