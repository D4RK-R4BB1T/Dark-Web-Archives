<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>

@extends('layouts.master')

@section('title', "Admin :: Adding order")

@section('content')
    <div class="row">
        <div class="col-sm-7 col-md-5 col-lg-5">
            @include('admin.sidebar')
        </div>

        <div class="col-sm-17 col-md-19 col-lg-19 animated fadeIn">
            <form role="form" method="POST" action="{{ url($prefix . '/add_orders') }}">
                {{ csrf_field() }}

                <div class="form-group">
                    <label for="user_id">User</label>
                    <select class="form-control" id="user_id" name="user_id">
                        @foreach($users as $user)
                            <option value="{{ $user->id }}">{{ $user->username }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="city_id">City</label>
                    <select class="form-control" id="city_id" name="city_id">
                        @foreach($cities as $city)
                            <option value="{{ $city->id }}">{{ $city->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="good_id">Goods</label>
                    <select class="form-control" id="good_id" name="good_id">
                        @foreach($goods as $good)
                            <option value="{{ $good->id }}">{{ $good->title }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="form-group">
                    <label for="good_title">Goods title</label>
                    <input class="form-control" id="good_title" name="good_title" />
                </div>

                <div class="form-group">
                    <label for="good_image_url">Goods image url</label>
                    <input class="form-control" id="good_image_url" name="good_image_url" />
                </div>

                <div class="form-group">
                    <input class="form-check-input" type="checkbox" id="good_image_cached" name="good_image_cached"> Image cached
                </div>

                <div class="form-group">
                    <label for="package_amount">Amount</label>
                    <input class="form-control" id="package_amount" name="package_amount" type="number" step="0.1">
                </div>

                <div class="form-group">
                    <label for="package_measure">Package measure</label>
                    <select class="form-control" id="package_measure" name="package_measure">
                        <option value="gr">gr</option>
                        <option value="piece">piece</option>
                        <option value="ml">ml</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="package_price">Package price</label>
                    <input class="form-control" id="package_price" name="package_price" type="number" step="0.1">
                </div>

                <div class="form-group">
                    <label for="package_currency">Package currency</label>
                    <select class="form-control" id="package_currency" name="package_currency">
                        <option value="btc">btc</option>
                        <option value="rub">rub</option>
                        <option value="usd">usd</option>
                    </select>
                </div>

                <div class="form-group">
                    <input class="form-check-input" type="checkbox" id="package_preorder" name="package_preorder"> Preordered package
                </div>

                <div class="form-group">
                    <label for="package_currency">Package preorder time</label>
                    <select class="form-control" id="package_preorder_time" name="package_preorder_time">
                        <option value=""></option>
                        <option value="24">24</option>
                        <option value="48">48</option>
                        <option value="72">72</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="status">Status</label>
                    <select class="form-control" id="status" name="status">
                        <option value="preorder_paid">Preorder paid</option>
                        <option value="paid">Paid</option>
                        <option value="problem">Problem</option>
                        <option value="finished">Finished</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="comment">Comment</label>
                    <textarea class="form-control" id="comment" name="comment" rows="3"></textarea>
                </div>

                <button type="submit" class="btn btn-primary">Add</button>
            </form>
        </div>
    </div>
@endsection