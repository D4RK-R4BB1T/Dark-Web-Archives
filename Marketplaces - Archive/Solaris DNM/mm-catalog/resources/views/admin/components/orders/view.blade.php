<div class="form-group">
    <label for="user_id">User</label>
    <select class="form-control" id="user_id" name="user_id" disabled>
        @foreach($users as $user)
            <option value="{{ $user->id }}" @if($user->id === $order->user_id) selected="selected" @endif>{{ $user->username }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="city_id">City</label>
    <select class="form-control" id="city_id" name="city_id" disabled>
        @foreach($cities as $city)
            <option value="{{ $city->id }}" @if($city->id === $good->city_id) selected="selected" @endif>{{ $city->title }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="review_id">Review</label>
    <input class="form-control" id="review_id" name="review_id" value="{{ $order->review_id }}" readonly/>
</div>

<div class="form-group">
    <label for="app_id">App id</label>
    <input class="form-control" id="app_id" name="app_id" value="{{ $order->app_id }}" readonly/>
</div>

<div class="form-group">
    <label for="app_order_id">App order id</label>
    <input class="form-control" id="app_order_id" name="app_order_id" value="{{ $order->app_order_id }}" readonly/>
</div>

<div class="form-group">
    <label for="app_good_id">App good id</label>
    <input class="form-control" id="app_good_id" name="app_good_id" value="{{ $order->app_good_id }}" readonly/>
</div>

<div class="form-group">
    <label for="good_id">Goods</label>
    <select class="form-control" id="good_id" name="good_id" disabled>
        @foreach($goods as $good)
            <option value="{{ $good->id }}" @if($good->id === $order->good_id) selected="selected" @endif>{{ $good->title }}</option>
        @endforeach
    </select>
</div>

<div class="form-group">
    <label for="good_title">Goods title</label>
    <input class="form-control" id="good_title" name="good_title" value="{{ $good->title }}" readonly/>
</div>

<div class="form-group">
    <label for="good_image_url">Goods image url</label>
    <input class="form-control" id="good_image_url" name="good_image_url" value="{{ $good->image_url }}" readonly/>
</div>

<div class="form-group">
    <label for="good_image_url_local">Goods image url local</label>
    <input class="form-control" id="good_image_url_local" name="good_image_url_local" value="{{ $order->good_image_url_local }}" readonly/>
</div>

<div class="form-group">
    <input class="form-check-input" type="checkbox" id="good_image_cached" name="good_image_cached" @if($order->good_image_cached) checked @endif disabled> Image cached
</div>

<div class="form-group">
    <label for="package_amount">Amount</label>
    <input class="form-control" id="package_amount" name="package_amount" type="number" step="0.1" value="{{ $package->amount }}" readonly>
</div>

<div class="form-group">
    <label for="package_measure">Package measure</label>
    <select class="form-control" id="package_measure" name="package_measure" disabled>
        <option value="gr" @if($package->measure === 'gr') selected @endif>gr</option>
        <option value="piece" @if($package->measure === 'piece') selected @endif>piece</option>
        <option value="ml" @if($package->measure === 'ml') selected @endif>ml</option>
    </select>
</div>

<div class="form-group">
    <label for="package_price">Package price</label>
    <input class="form-control" id="package_price" name="package_price" type="number" step="0.1" value="{{ $package->price }}" readonly>
</div>

<div class="form-group">
    <label for="package_currency">Package currency</label>
    <select class="form-control" id="package_currency" name="package_currency" disabled>
        <option value="btc" @if($package->currency === 'btc') selected @endif>btc</option>
        <option value="rub" @if($package->currency === 'rub') selected @endif>rub</option>
        <option value="usd" @if($package->currency === 'usd') selected @endif>usd</option>
    </select>
</div>

<div class="form-group">
    <input class="form-check-input" type="checkbox" id="package_preorder" name="package_preorder" @if($package->preorder) checked @endif disabled> Preordered package
</div>

<div class="form-group">
    <label for="package_preorder_time">Package preorder time</label>
    <select class="form-control" id="package_preorder_time" name="package_preorder_time" disabled>
        <option value=""></option>
        <option value="24" @if($package->preorder_time == 24) selected @endif>24</option>
        <option value="48" @if($package->preorder_time == 48) selected @endif>48</option>
        <option value="72" @if($package->preorder_time == 72) selected @endif>72</option>
    </select>
</div>

<div class="form-group">
    <label for="status">Status</label>
    <select class="form-control" id="status" name="status" disabled>
        <option value="preorder_paid" @if($order->status === 'preorder_paid') selected @endif>Preorder paid</option>
        <option value="paid" @if($order->status === 'paid') selected @endif>Paid</option>
        <option value="problem" @if($order->status === 'problem') selected @endif>Problem</option>
        <option value="finished" @if($order->status === 'finished') selected @endif>Finished</option>
    </select>
</div>

<div class="form-group">
    <label for="comment">Comment</label>
    <textarea class="form-control" id="comment" name="comment" rows="3" readonly>{{ $order->comment }}</textarea>
</div>

<div class="form-group">
    <label for="app_created_at">App created at</label>
    <input class="form-control" id="app_created_at" name="app_created_at" value="{{ $order->app_created_at }}" readonly>
</div>

<div class="form-group">
    <label for="package_price">App updated at</label>
    <input class="form-control" id="app_updated_at" name="app_updated_at" value="{{ $order->app_updated_at }}" readonly>
</div>

<div class="form-group">
    <label for="package_price">Created</label>
    <input class="form-control" id="created_at" name="created_at" value="{{ $order->created_at }}" readonly>
</div>

<div class="form-group">
    <label for="package_price">Updated</label>
    <input class="form-control" id="updated_at" name="updated_at" value="{{ $order->updated_at }}" readonly>
</div>
