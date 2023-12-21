@extends('layouts.master')

@section('title', __('feedback.Create new ticket title'))

@section('content')
    <div class="row">
        <div class="col-xs-24 col-sm-18 col-md-18 col-lg-19 pull-right animated fadeIn">
            <div class="well block good-info">
                <h3>{{ __('feedback.New ticket') }}</h3>
                <hr class="small" />
                <form role="form" action="" method="post"  enctype="multipart/form-data">
                    {{ csrf_field() }}

                    <div class="row">
                        <div class="form-group{{ $errors->has('title') ? ' has-error' : '' }}">
                            <input class="form-control" name="title"
                                   placeholder="{{ __('feedback.Title placeholder') }}" value="{{ old('title') }}" required/>

                            @if ($errors->has('title'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('title') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group{{ $errors->has('category') ? ' has-error' : '' }}">
                            <select class="form-control" name="category" required>
                                <option value=""
                                        @if(!old('category'))selected="selected" @endif>{{ __('feedback.Choose category') }}</option>
                                <option value="{{ App\Models\Tickets\Ticket::CATEGORY_COMMON_SELLER_QUESTION }}"
                                        @if(old('category') === App\Models\Tickets\Ticket::CATEGORY_COMMON_SELLER_QUESTION) selected="selected" @endif>{{ __('feedback.Category ' . App\Models\Tickets\Ticket::CATEGORY_COMMON_SELLER_QUESTION) }}</option>
                                <option value="{{ App\Models\Tickets\Ticket::CATEGORY_COMMON_BUYER_QUESTION }}"
                                        @if(old('category') === App\Models\Tickets\Ticket::CATEGORY_COMMON_BUYER_QUESTION) selected="selected" @endif>{{ __('feedback.Category ' . App\Models\Tickets\Ticket::CATEGORY_COMMON_BUYER_QUESTION) }}</option>
                                <option value="{{ App\Models\Tickets\Ticket::CATEGORY_APPLICATION_FOR_OPENING }}"
                                        @if(old('category') === App\Models\Tickets\Ticket::CATEGORY_APPLICATION_FOR_OPENING) selected="selected" @endif>{{ __('feedback.Category ' . App\Models\Tickets\Ticket::CATEGORY_APPLICATION_FOR_OPENING) }}</option>
                                <option value="{{ App\Models\Tickets\Ticket::CATEGORY_COOPERATION }}"
                                        @if(old('category') === App\Models\Tickets\Ticket::CATEGORY_COOPERATION) selected="selected" @endif>{{ __('feedback.Category ' . App\Models\Tickets\Ticket::CATEGORY_COOPERATION) }}</option>
                                <option value="{{ App\Models\Tickets\Ticket::CATEGORY_SECURITY_SERVICE }}"
                                        @if(old('category') === App\Models\Tickets\Ticket::CATEGORY_SECURITY_SERVICE) selected="selected" @endif>{{ __('feedback.Category ' . App\Models\Tickets\Ticket::CATEGORY_SECURITY_SERVICE) }}</option>
                                <option value="{{ App\Models\Tickets\Ticket::CATEGORY_PAYMENT_ERRORS }}"
                                        @if(old('category') === App\Models\Tickets\Ticket::CATEGORY_PAYMENT_ERRORS) selected="selected" @endif>{{ __('feedback.Category ' . App\Models\Tickets\Ticket::CATEGORY_PAYMENT_ERRORS) }}</option>
                            </select>

                            @if ($errors->has('category'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('category') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="form-group{{ $errors->has('message') ? ' has-error' : '' }}">
                            <textarea class="form-control" name="message" rows="10" placeholder="{{ __('feedback.Description placeholder') }}" required>{{ old('message') }}</textarea>

                            @if ($errors->has('message'))
                                <span class="help-block">
                                    <strong>{{ $errors->first('message') }}</strong>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-xs-24 text-center">
                            <span class="help-block">
                                Вы можете загрузить до 3-х картинок весом до 5 мб.
                            </span>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-24">
                            <div class="form-group text-center {{ $errors->has('images.*') ? 'has-error' : '' }}">
                                <div class="kd-upload">
                                    <span><i class="glyphicon glyphicon-upload"></i> <span class="upload-text">Картинки</span></span>
                                    <input type="file" name="images[]" class="upload" multiple>
                                </div>
                                @if ($errors->has('images.*'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('images.*') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <hr class="small">

                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Отправить</button>
                    </div>
                </form>
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