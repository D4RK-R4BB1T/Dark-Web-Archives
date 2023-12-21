<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" method="POST" action="{{ url($prefix . '/edit_shops?id=' . $shop->id) }}">
    {{ csrf_field() }}

    <div class="form-group">
        <label for="app_id">{{ __('admin.App id') }}</label>
        <input class="form-control" id="app_id" name="app_id" value="{{ $shop->app_id }}" />
    </div>

    <div class="form-group">
        <label for="app_key">{{ __('admin.APP KEY') }}</label>
        <input class="form-control" id="app_key" name="app_key" value="{{ $shop->app_key }}" />
    </div>

    <div class="form-group">
        <label for="url">{{ __('admin.url') }}</label>
        <input class="form-control" id="url" name="url" value="{{ $shop->url }}" />
    </div>

    <div class="form-group">
        <label for="title">{{ __('admin.Title') }}</label>
        <input class="form-control" id="title" name="title" value="{{ $shop->title }}" />
    </div>

    <div class="form-group">
        <label for="contacts_telegram">{{ __('layout.Image url') }}</label>
        <input class="form-control" id="image_url" name="image_url" value="{{ $shop->image_url }}" />
    </div>

    <div class="form-group">
        <label for="users_count">{{ __('layout.Users') }}</label>
        <input class="form-control" id="users_count" name="users_count" type="number" min="0" max="2147483648" value="{{ $shop->users_count }}">
    </div>

    <div class="form-group">
        <label for="orders_count">{{ __('admin.Orders count') }}</label>
        <input class="form-control" id="orders_count" name="orders_count" type="number" min="0" max="2147483648" value="{{ $shop->orders_count }}">
    </div>

    <div class="form-group">
        <label for="rating">{{ __('layout.Rating') }}</label>
        <input class="form-control" id="rating" name="rating" type="number" value="{{ $shop->rating }}" step="0.1">
    </div>

    <div class="form-group">
        <input class="form-check-input" type="checkbox" id="enabled" name="enabled" @if($shop->enabled) checked @endif> {{ mb_ucfirst(__('layouts.enabled m')) }}
    </div>

    <div class="form-group">
        <label for="plan">Plan</label>
        {{-- TODO --}}
        <select class="form-control" id="plan" name="plan">
            <option value="basic"@if($shop->plan === 'basic') selected="selected"@endif>{{ __('admin.Plan basic') }}</option>
            <option value="advanced"@if($shop->plan === 'advanced') selected="selected"@endif>{{ __('admin.Plan advanced') }}</option>
            <option value="individual"@if($shop->plan === 'individual') selected="selected"@endif>{{ __('admin.Plan individual') }}</option>
            <option value=""@if($shop->plan === ' ') selected="selected"@endif></option>
        </select>
    </div>

    <button type="submit" class="btn btn-primary">{{ __('admin.Update') }}</button>
</form>
