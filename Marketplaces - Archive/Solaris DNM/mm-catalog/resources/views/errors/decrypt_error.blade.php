@extends('layouts.master', ['hide_header' => true])

@section('title', __('error.Redirect error'))

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ __('error.Redirect error') }}</div>
                <div class="panel-body">
                    <p>
                        {!! __('error.Could not authorize') !!}
                    </p>
                    <hr />
                    <a class="btn btn-orange" href="/">{{ __('layout.Home') }}</a>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection