{{--
This file is part of MM2-dev project.
Description: Settings contacts page
--}}
@extends('layouts.master')

@section('title', 'Контакты :: Настройки')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('settings.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-md-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Контакты</h3>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('contacts_jabber') ? ' has-error' : '' }}">
                                <input id="contacts_jabber" type="text" class="form-control" name="contacts_jabber" placeholder="Jabber" value="{{ old('contacts_jabber') ?: Auth::user()->contacts_jabber }}" {{ autofocus_on_desktop() }}>
                                @if ($errors->has('contacts_jabber'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('contacts_jabber') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('contacts_telegram') ? ' has-error' : '' }}">
                                <input id="contacts_telegram" type="text" class="form-control" name="contacts_telegram" placeholder="Telegram" value="{{ old('contacts_telegram') ?: Auth::user()->contacts_telegram }}">
                                @if ($errors->has('contacts_telegram'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('contacts_telegram') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('contacts_other') ? ' has-error' : '' }}">
                                <input id="contacts_other" type="text" class="form-control" name="contacts_other" placeholder="Другое (укажите что именно)" value="{{ old('contacts_other') ?: Auth::user()->contacts_other }}">
                                @if ($errors->has('contacts_other'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('contacts_other') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Сохранить</button>
                    </div>
                </form>
            </div> <!-- /.col-sm-13 -->
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('settings.components.block-security-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection