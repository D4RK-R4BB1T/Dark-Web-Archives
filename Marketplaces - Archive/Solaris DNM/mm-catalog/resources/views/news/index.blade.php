{{--
This file is part of MM2-dev project.
Description: News page
--}}
@extends('layouts.master')

@section('title', __('layout.News'))

@section('content')
    <div class="row">
        <div class="col-xs-24 animated fadeIn">
            @foreach($news as $post)
                <div class="well block">
                    <h3 class="{{ $post->isUnread() ? 'text-danger' : '' }}">
                        @if ($post->isUnread())
                            <span class="text-danger"><i class="glyphicon glyphicon-play"></i></span>&nbsp;
                        @endif
                        {{ $post->title }}</h3>
                    <hr class="small" />
                    {!! $post->text !!}
                    <hr class="small" />
                    <span class="text-muted">
                        <strong><i class="glyphicon glyphicon-user"></i>&nbsp;{{ $post->author }}</strong>
                        &nbsp;&nbsp;/&nbsp;&nbsp;
                        <i class="glyphicon glyphicon-calendar"></i> {{ $post->created_at->format('d.m.Y') }}
                    </span>
                </div>
            @endforeach
            @if ($news->total() > $news->perPage())
                <hr class="small" />
                <div class="text-center">
                    {{ $news->appends(request()->input())->links() }}
                </div>
                <hr class="small" />
            @endif
        </div>
    </div> <!-- /.row -->
@endsection