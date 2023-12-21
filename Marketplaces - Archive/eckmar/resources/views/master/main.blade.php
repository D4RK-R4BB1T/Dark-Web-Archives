<!doctype html>
<html lang="en">
<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="/css/app.css">


    @hasSection('title')
        <title>{{config('app.name')}} - @yield('title')</title>
    @else
        <title>{{config('app.name')}}</title>
    @endif

</head>
<body class="pb-4">
@include('master.navbar')
@include('master.search')

@hasSection('container-fluid')
    <div class="container-fluid">
@else
    <div class="container">
@endif
        @include('includes.jswarning')
        <div class="mt-4">
            @yield('content')
        </div>


</div>

</body>
</html>