{{--
This file is part of MM2-dev project.
Description: Review reply page
--}}
@extends('layouts.master')

@section('title', 'Ответ на отзыв')

@section('content')
    @include('shop.management.components.sections-menu')
    <div class="row">
        <div class="col-sm-6 col-lg-5">
            @include('shop.management.goods.sidebar')
        </div> <!-- /.col-sm-6 -->

        <div class="col-sm-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Отзыв</h3>
                <hr class="small" />
                @include('layouts.components.component-review', ['review' => $review])
            </div>

            <div class="well block">
                <h3>Ответ на отзыв</h3>
                <hr class="small" />
                <form action="" role="form" method="post" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <div class="form-group {{ $errors->has('reply_text') ? 'has-error' : '' }}">
                        <textarea style="margin-bottom: 8px" rows="3" name="reply_text" class="form-control" placeholder="Напишите ответ на отзыв..." {{ autofocus_on_desktop() }}>{{ old('reply_text') ?: $review->reply_text }}</textarea>
                        @if ($errors->has('reply_text'))
                            <span class="help-block">
                                <strong>{{ $errors->first('reply_text') }}</strong>
                            </span>
                        @endif
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить</button>
                    </div>
                </form>
            </div>
        </div> <!-- /.col-sm-12 -->

        <div class="col-sm-6 animated fadeIn">
            @include('orders.components.block-review-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection