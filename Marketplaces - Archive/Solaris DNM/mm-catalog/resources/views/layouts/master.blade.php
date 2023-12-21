<!DOCTYPE html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7"> <![endif]-->
<!--[if IE 7]> <html class="no-js lt-ie9 lt-ie8"> <![endif]-->
<!--[if IE 8]> <html class="no-js lt-ie9"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js"> <!--<![endif]-->
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <title>@yield('title') :: {{ config('catalog.application_title') }}</title>
    <meta name="description" content="">
    <meta name="keywords" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <link rel="icon" href="/assets/img/logo.svg" type="image/svg+xml">
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/catalog.css">
    <link rel="stylesheet" href="/assets/css/theme.css">
    {{--
    @if (false && config('app.debug'))
        <link rel="stylesheet" href="http://169.254.1.55:8000/assets/css/theme.css">
    @endif
    --}}
    @if (File::exists(public_path() . '/theme/theme.css'))
        <link rel="stylesheet" href="/theme/theme.css">
    @endif
    @yield('header_scripts')
</head>
<body>

<div id="header">
    <nav class="navbar navbar-default navbar-static-top" role="navigation">
        <div class="container">
            <div class="col-xs-24">
                <div class="navbar-header">
                    <a class="navbar-brand" style="padding: 1px;" href="/">
                        <span><img src="/assets/img/logo_ng.png" style="height: 100%"></span>
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
            </div> <!-- /.col-xs-24 -->
        </div> <!-- /.container -->
    </nav>
</div> <!-- /#header -->

<div id="main">
    <div class="container">
        <div class="col-md-24">
        @if (session('flash_success') || isset($flash_success))
            <div class="alert alert-success animated bounceIn">
                <i class="fa fa-info-circle"></i> {{ session('flash_success') ?: $flash_success }}
            </div>
        @endif

        @if (session('flash_info') || isset($flash_info))
            <div class="alert blue animated bounceIn">
                <i class="fa fa-info-circle"></i> {{ session('flash_info') ?: $flash_info }}
            </div>
        @endif

        @if (session('flash_warning') || isset($flash_warning))
            <div class="alert yellow animated bounceIn">
                <i class="fa fa-info-circle"></i> {{ session('flash_warning') ?: $flash_warning }}
            </div>
        @endif


        @if (session('flash_error') || isset($flash_error))
            <div class="alert red animated bounceIn">
                <i class="fa fa-info-circle"></i> {{ session('flash_error') ?: $flash_error }}
            </div>
        @endif

        @yield('content', __('layout.Nothing'))
        </div> <!-- /.col-md-24 -->
    </div><!-- /.container -->
</div><!-- /#main -->
<!-- modals -->
@if (Auth::check()) {{-- Authorized--}}
    @include('layouts.components.modals.logout')
    @if (isset($unreadNotifications) && $unreadNotifications->count() > 0)
        @include('layouts.components.modals.notifications')
    @endif
@elseif(!request()->is('auth/*')) {{-- Not on light pages --}}
    @include('layouts.components.modals.login')
@endif
@yield('modals')
<!-- /modals -->
<div id="footer" class="hidden-xs">
    <div class="container">
        <div class="row">
            <div class="col-xs-24 col-md-12">
                <ul>
                    @auth
                    <li>
                        <a href="{{ Auth::user()->isAdmin() ? '/admin' : '' }}/ticket" @cannot('create-ticket')class="cursor-not-allowed" disabled @endif>Связь с администрацией</a>
                    </li>
                    @endauth
                    
                    <li>
                        <a href="{{ '/advert' }}">Рекламодателям</a>
                    </li>
                </ul>
            </div>
            <div class="col-xs-24 col-md-12 text-right">
                Copyright © {{ date('Y') }} Solaris
            </div>
        </div> <!-- /.row -->
    </div> <!-- /.container -->
</div> <!-- /#footer -->
</body>
</html>
