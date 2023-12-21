{{--
This file is part of MM2-dev project.
Description: Settings contacts page
--}}
@extends('layouts.master')

@section('title', 'Ссылки :: Рефералы')

@section('content')
    <div class="row">
        <div class="col-sm-6 col-md-6 col-lg-5">
            @include('referral.sidebar')
        </div> <!-- /.col-lg-5 -->

        <div class="col-sm-12 col-md-12 col-lg-13 animated fadeIn">
            <div class="well block">
                <h3>Создать ссылку</h3>
                <hr class="small" />
                <p class="text-muted">
                    На этой странице вы можете создать уникальную ссылку для приглашения пользователей.
                    <br /><br />
                    При входе в магазин по этой ссылке все цены в магазине для приглашенного пользователя будут увеличены на выбранный процент.
                    <br /><br />
                    За каждую покупку от приглашенного пользователя вы будете получать этот процент на свой баланс.
                    <br /><br />
                    <strong>Важно!</strong> Вы можете создать не больше <strong>{{ config('mm2.referral_urls_count') }}</strong> ссылок. После создания ссылки её нельзя удалить или отредактировать.
                </p>
                <hr class="small" />
                <form action="" method="post">
                    {{ csrf_field() }}
                    <div class="row">
                        <div class="col-xs-16 col-xs-offset-4">
                            <div class="form-group{{ $errors->has('fee') ? ' has-error' : '' }}">
                                <input id="fee" type="text" class="form-control" name="fee" placeholder="Комиссия в процентах, например 10" value="{{ old('fee') }}" {{ autofocus_on_desktop() }}>
                                @if ($errors->has('fee'))
                                    <span class="help-block">
                                        <strong>{{ $errors->first('fee') }}</strong>
                                    </span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <hr class="small" />
                    <div class="text-center">
                        <button type="submit" class="btn btn-orange">Создать ссылку</button>
                    </div>
                </form>
            </div> <!-- /.col-sm-13 -->

            <div class="well block">
                <h3>Мои ссылки</h3>
                @if ($urls->count() == 0)
                    <hr class="small" />
                    <div class="alert alert-info" style="margin-bottom: 0">Не найдено ни одной ссылки.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-header" style="margin-bottom: 0">
                            <thead>
                            <tr>
                                <td>Ссылка</td>
                                <td>Комиссия</td>
                                <td>Дата создания</td>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($urls as $url)
                                <tr>
                                    <td>{{ config('mm2.application_referral_url') }}/~/{{ $url->slug }}</td>
                                    <td>{{ round($url->fee, 1) }}%</td>
                                    <td>{{ $url->created_at->format('d.m.Y в H:i') }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @if ($urls->total() > $urls->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $urls->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </div>

        <div class="col-sm-6 animated fadeIn">
            @include('referral.components.block-url-reminder')
        </div> <!-- /.col-sm-6 -->
    </div> <!-- /.row -->
@endsection