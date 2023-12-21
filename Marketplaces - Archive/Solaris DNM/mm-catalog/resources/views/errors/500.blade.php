@extends('layouts.master', ['hide_header' => true])

@section('title', 'Error 500')

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-16 col-sm-offset-4 col-md-10 col-md-offset-7 auth-container">
            <div class="panel panel-modal">
                <div class="panel-heading">{{ __('error.Error 500') }}</div>
                <div class="panel-body">
                    <p>
                        {{ __('error.Unexpected error') }}
                    </p>
                    <hr />
                    <a class="btn btn-orange" href="{{ URL::previous() }}">{{ __('layout.Go back') }}</a>
                    <a class="btn btn-orange" href="/">{{ __('layout.Home') }}</a>
                </div> <!-- /.panel-body -->
            </div> <!-- /.panel -->
        </div> <!-- /.auth-container -->
    </div> <!-- /.row -->
@endsection