<!-- catalog/sidebar -->
{{--@include('layouts.components.block-user-cabinet', ['user' => Auth::user()])--}}
@include('layouts.components.block-categories', ['prefix' => '/catalog'])
<!-- / catalog/sidebar -->