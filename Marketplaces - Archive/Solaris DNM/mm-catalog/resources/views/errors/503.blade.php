@extends('layouts.master', ['hide_header' => true])

@section('title', 'Maintenance mode')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ __('layout.Maintenance mode') }}</div>
                <div class="panel-body">
                    <p>{{ __('layout.Maintenance description') }}</p>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection