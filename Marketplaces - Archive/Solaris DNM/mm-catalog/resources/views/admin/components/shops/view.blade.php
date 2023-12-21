<div class="form-group">
    <label for="app_id">{{ __('admin.App id') }}</label>
    <input class="form-control" id="app_id" name="app_id" value="{{ $shop->app_id }}" readonly/>
</div>


@if(Auth::user()->hasRoles())
    <div class="form-group">
        <label for="app_key">{{ __('admin.APP KEY') }}</label>
        <input class="form-control" id="app_key" name="app_key" value="{{ $shop->app_key }}" readonly/>
    </div>
@endif

<div class="form-group">
    <label for="url">{{ __('admin.url') }}</label>
    <input class="form-control" id="url" name="url" value="{{ $shop->url }}" readonly/>
</div>

<div class="form-group">
    <label for="gateUrl">URL гейта</label>
    <input class="form-control" id="gateUrl" name="gateUrl" value="{{ url('/') . $guard_url }}" readonly/>
</div>

<div class="form-group">
    <label for="title">{{ __('admin.Title') }}</label>
    <input class="form-control" id="title" name="title" value="{{ $shop->title }}" readonly/>
</div>

<div class="form-group">
    <label for="contacts_telegram">{{ __('layout.Image url') }}</label>
    <input class="form-control" id="image_url" name="image_url" value="{{ $shop->image_url }}" readonly/>
</div>

<div class="form-group">
    <label for="contacts_telegram">{{ __('admin.Image url local') }}</label>
    <input class="form-control" id="image_url_local" name="image_url_local" value="{{ $shop->image_url_local }}"
           readonly>
</div>

<div class="form-group">
    <input class="form-check-input" type="checkbox" id="enabled" name="enabled" @if($shop->image_cached) checked
           @endif disabled> {{ __('admin.Image cached') }}
</div>

<div class="form-group">
    <label for="users_count">{{ __('layout.Users') }}</label>
    <input class="form-control" id="users_count" name="users_count" type="number" min="0" max="2147483648"
           value="{{ $shop->users_count }}" readonly>
</div>

<div class="form-group">
    <label for="orders_count">{{ __('admin.Orders count') }}</label>
    <input class="form-control" id="orders_count" name="orders_count" type="number" min="0" max="2147483648"
           value="{{ $shop->orders_count }}" readonly>
</div>

<div class="form-group">
    <label for="bitcoin_connections">{{ __('admin.Bitcoin connections') }}</label>
    <input class="form-control" id="bitcoin_connections" name="bitcoin_connections"
           value="{{ $shop->bitcoin_connections }}" readonly>
</div>

<div class="form-group">
    <label for="bitcoin_block_count">{{ __('admin.Bitcoin block count') }}</label>
    <input class="form-control" id="bitcoin_block_count" name="bitcoin_block_count"
           value="{{ $shop->bitcoin_block_count }}" readonly>
</div>

<div class="form-group">
    <label for="rating">{{ __('layout.Rating') }}</label>
    <input class="form-control" id="rating" name="rating" type="number" value="{{ $shop->rating }}" step="0.1" readonly>
</div>

<div class="form-group">
    <input class="form-check-input" type="checkbox" id="enabled" name="enabled"
           disabled> {{ mb_ucfirst(__('layout.enabled m')) }}
</div>

<div class="form-group">
    <label for="plan">{{ __('admin.Plan') }}</label>
    {{-- TODO --}}
    <select class="form-control" id="plan" name="plan" disabled>
        <option value="basic"
                @if($shop->plan === 'basic') selected="selected"@endif>{{ __('admin.Plan basic') }}</option>
        <option value="advanced"
                @if($shop->plan === 'advanced') selected="selected"@endif>{{ __('admin.Plan advanced') }}</option>
        <option value="individual"
                @if($shop->plan === 'individual') selected="selected"@endif>{{ __('admin.Plan individual') }}</option>
        <option value="" @if($shop->plan === ' ') selected="selected"@endif></option>
    </select>
</div>

<div class="form-group">
    <label for="last_sync_at">{{ __('admin.Last sync at') }}</label>
    <input class="form-control" id="last_sync_at" name="last_sync_at" value="{{ $shop->last_sync_at }}" readonly>
</div>

<div class="form-group">
    <label for="expires_at">{{ __('admin.Expires') }}</label>
    <input class="form-control" id="expires_at" name="expires_at" value="{{ $shop->expires_at }}" readonly>
</div>

<div class="form-group">
    <label for="created_at">{{ __('layout.Created') }}</label>
    <input class="form-control" id="created_at" name="created_at" value="{{ $shop->created_at }}" readonly>
</div>

<div class="form-group">
    <label for="updated_at">{{ __('layout.Updated') }}</label>
    <input class="form-control" id="updated_at" name="updated_at" value="{{ $shop->updated_at }}" readonly>
</div>
