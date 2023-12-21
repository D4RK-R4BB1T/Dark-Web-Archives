<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" method="POST" action="{{ url($prefix . '/edit_orders?id=' . $good->id) }}">
    {{ csrf_field() }}
    <div class="form-group">
        <label for="user_id">User</label>
        <select class="form-control" id="user_id" name="user_id">
            @foreach($users as $user)
                <option value="{{ $user->id }}" @if($user->id === $order->user_id) selected="selected" @endif>{{ $user->username }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="city_id">City</label>
        <select class="form-control" id="city_id" name="city_id">
            @foreach($cities as $city)
                <option value="{{ $city->id }}" @if($city->id === $good->city_id) selected="selected" @endif>{{ $city->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="good_id">Goods</label>
        <select class="form-control" id="good_id" name="good_id">
            @foreach($goods as $good)
                <option value="{{ $good->id }}" @if($good->id === $order->good_id) selected="selected" @endif>{{ $good->title }}</option>
            @endforeach
        </select>
    </div>

    <div class="form-group">
        <label for="good_title">Goods title</label>
        <input class="form-control" id="good_title" name="good_title" value="{{ $good->title }}" />
    </div>

    <div class="form-group">
        <label for="good_image_url">Goods image url</label>
        <input class="form-control" id="good_image_url" name="good_image_url" value="{{ $good->image_url }}" />
    </div>

    <div class="form-group">
        <input class="form-check-input" type="checkbox" id="good_image_cached" name="good_image_cached" @if($order->good_image_cached) checked @endif> Image cached
    </div>

    <div class="form-group">
        <label for="package_amount">Amount</label>
        <input class="form-control" id="package_amount" name="package_amount" type="number" step="0.1" value="{{ $package->amount }}">
    </div>

    <div class="form-group">
        <label for="package_measure">Package measure</label>
        <select class="form-control" id="package_measure" name="package_measure">
            <option value="gr" @if($package->measure === 'gr') selected @endif>gr</option>
            <option value="piece" @if($package->measure === 'piece') selected @endif>piece</option>
            <option value="ml" @if($package->measure === 'ml') selected @endif>ml</option>
        </select>
    </div>

    <div class="form-group">
        <label for="package_price">Package price</label>
        <input class="form-control" id="package_price" name="package_price" type="number" step="0.1" value="{{ $package->price }}">
    </div>

    <div class="form-group">
        <label for="package_currency">Package currency</label>
        <select class="form-control" id="package_currency" name="package_currency">
            <option value="btc" @if($package->currency === 'btc') selected @endif>btc</option>
            <option value="rub" @if($package->currency === 'rub') selected @endif>rub</option>
            <option value="usd" @if($package->currency === 'usd') selected @endif>usd</option>
        </select>
    </div>

    <div class="form-group">
        <input class="form-check-input" type="checkbox" id="package_preorder" name="package_preorder" @if($package->preorder) checked @endif> Preordered package
    </div>

    <div class="form-group">
        <label for="package_preorder_time">Package preorder time</label>
        <select class="form-control" id="package_preorder_time" name="package_preorder_time">
            <option value=""></option>
            <option value="24" @if($package->preorder_time == 24) selected @endif>24</option>
            <option value="48" @if($package->preorder_time == 48) selected @endif>48</option>
            <option value="72" @if($package->preorder_time == 72) selected @endif>72</option>
        </select>
    </div>

    <div class="form-group">
        <label for="status">Status</label>
        <select class="form-control" id="status" name="status">
            <option value="preorder_paid" @if($order->status === 'preorder_paid') selected @endif>Preorder paid</option>
            <option value="paid" @if($order->status === 'paid') selected @endif>Paid</option>
            <option value="problem" @if($order->status === 'problem') selected @endif>Problem</option>
            <option value="finished" @if($order->status === 'finished') selected @endif>Finished</option>
        </select>
    </div>

    <div class="form-group">
        <label for="comment">Comment</label>
        <textarea class="form-control" id="comment" name="comment" rows="3">{{ $order->comment }}</textarea>
    </div>

    <button type="submit" class="btn btn-primary">Update</button>
</form>
