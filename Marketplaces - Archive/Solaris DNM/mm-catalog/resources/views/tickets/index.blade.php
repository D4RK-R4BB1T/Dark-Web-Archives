@extends('layouts.master')

@section('title', __('layout.Feedback'))

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block good-info">
                <h3>{{ __('feedback.Ticket list') }}</h3>
                @if ($tickets->count() === 0)
                    <hr class="small"/>
                    <div class="alert alert-info" style="margin-bottom: 0">{{ __('feedback.No tickets found') }}</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-header table-hover" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Заголовок</td>
                                <td>Категория</td>
                                <td>Дата</td>
                                <td>Статус</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($tickets as $t)
                                <tr style="cursor: pointer; {{ $t->closed ? 'background: #f1f1f1; font-style: italic' : '' }}" onclick="window.location='/ticket/{{ $t->id }}/view'">
                                    <td class="col-md-10">
                                        <a href="/ticket/{{ $t->id }}/view">{{ $t->title }}</a>
                                    </td>
                                    <td>{{ __('feedback.Category ' . $t->category) }}</td>
                                    <td>{{ $t->created_at->format('d.m.Y H:i') }}</td>
                                    <td><span class="hint--top" aria-label="Обращение {{ $t->closed ? 'закрыто' : 'открыто' }}"><i class="{{ $t->closed ? 'glyphicon glyphicon-lock text-red' : 'glyphicon glyphicon-comment text-green' }}"></i></span></td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($tickets->total() > $tickets->perPage())
                        <hr class="small"/>
                        <div class="text-center">
                            {{ $tickets->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-xs-24 col-sm-6 col-md-6 col-lg-5 pull-left">
            @include('tickets.components.block-actions')
            @if (Auth::user()->isAdmin())
                @include('tickets.components.block-filters')
            @endif
        </div> <!-- /.col-lg-5 -->
    </div> <!-- /.row -->
@endsection