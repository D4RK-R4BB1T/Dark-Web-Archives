@extends('master.product')

@section('product-content')


    <p>{!! \GrahamCampbell\Markdown\Facades\Markdown::convertToHtml(nl2br(e($product -> rules))) !!}</p>


@stop