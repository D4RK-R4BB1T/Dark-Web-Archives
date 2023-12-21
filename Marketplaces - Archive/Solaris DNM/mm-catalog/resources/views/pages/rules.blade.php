@extends('layouts.master')

@section('title', __('layout.Rules'))

@section('content')
    <div class="row">
        <div class="col-xs-24 animated fadeIn">
            <div class="well block">
                <h2>Правила ресурса</h2>
                <hr class="small" />
                @include('rules')
            </div>
        </div>
    </div> <!-- /.row -->
@endsection