<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="keywords" content="">
    <title>@yield('title') :: {{ config('catalog.application_title') }}</title>
    <link rel="icon" href="{{ asset("assets/img/logo.svg") }} type="image/svg+xml">
    <link href="{{ asset('assets/css/bootstrap5/app.css') }}" rel="stylesheet">
    <link href="{{ asset('css/json-formatter.css') }}" rel="stylesheet">
    {{--@if (File::exists(public_path() . '/css/bootstrap5/theme.css'))<link rel="stylesheet" href="{{ asset('assets/css/bootstrap5/theme.css') }}">@endif--}}

    @yield('header_scripts')
</head>
<body>

<div id="app">
    <nav class="navbar navbar-default navbar-static-top" role="navigation">
        <div class="container">
            <div class="navbar-header">
                <a class="navbar-brand" style="padding: 8px 10px 8px 0;" href="/">
                    <span><img src="{{ asset('/assets/img/logo.svg') }}" style="height: 100%" alt=""></span>
                </a>

                <a class="navbar-brand" href="/">
                    {{ config('catalog.header_title') }} &nbsp;
                </a>
            </div>

            <div id="navbar-collapse">
                @if (!isset($hide_header) || !$hide_header)
                    @if (Auth::check())
                        @include('layouts.navbar.user-left')
                        @include('layouts.navbar.user-right')
                    @else
                        @include('layouts.navbar.guest-left')
                        @include('layouts.navbar.guest-right')
                    @endif
                @endif
            </div><!-- /.navbar-collapse -->

        </div>
    </nav>
</div>
</body>
<script src="{{ asset('js/app.js') }}" defer></script>
</html>
