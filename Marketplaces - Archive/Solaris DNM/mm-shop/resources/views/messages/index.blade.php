{{--
This file is part of MM2-dev project.
Description: Messages list page
--}}
@extends('layouts.master')

@section('title', 'Сообщения')

@section('content')
    <div class="row">
        <form action="" method="post">
            {{ csrf_field() }}
            <div class="col-sm-8 col-md-9 col-lg-8">
                @include('messages.components.block-threads', ['threads' => $threads, 'deleting' => isset($deleting) ? $deleting : false])
            </div> <!-- /.col-lg-5 -->

            <div class="col-sm-16 col-md-15 col-lg-16 animated fadeIn">
                @if (isset($deleting) && $deleting)
                    <div class="well block">
                        <h3>Удаление диалогов</h3>
                        <hr class="small" />
                        <button type="submit" class="btn btn-orange">Удалить выбранные диалоги</button>
                    </div>
                @else
                    <div class="well block">
                        <h3>Сообщения</h3>
                        <hr class="small" />
                        <div class="well" style="margin-bottom: 0">Выберите диалог в списке слева.</div>
                    </div>
                @endif
            </div> <!-- /.col-sm-18 -->
        </form>
    </div> <!-- /.row -->
@endsection