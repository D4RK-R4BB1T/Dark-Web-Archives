<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" method="POST" action="{{ url($prefix . '/users/edit?id=' . $user->id) }}">
    {{ csrf_field() }}

    <div class="form-group">
        <label for="username">{{ __('layout.Username') }}</label>
        <input class="form-control" id="username" name="username" value="{{ $user->username }}" />
    </div>

    <div class="form-group">
        <label for="password">{{ __('layout.Password') }}</label>
        <input class="form-control" id="password" name="password" type="password" />
    </div>

    <div class="form-group">
        <label for="contacts_other">{{ __('layout.Contacts other') }}</label>
        <input class="form-control" id="contacts_other" name="contacts_other" value="{{ $user->contacts_other }}" />
    </div>

    <div class="form-group">
        <label for="contacts_jabber">Jabber</label>
        <input class="form-control" id="contacts_jabber" name="contacts_jabber"  value="{{ $user->contacts_jabber }}" />
    </div>

    <div class="form-group">
        <label for="contacts_telegram">Telegram</label>
        <input class="form-control" id="contacts_telegram" name="contacts_telegram"  value="{{ $user->contacts_telegram }}" />
    </div>

    {{--<div class="form-group">
        <label for="role">{{ __('admin.Role') }}</label>
        <select class="form-control" id="role" name="role">
            <option value="admin"@if($user->role === 'admin') selected="selected"@endif>{{ __('admin.Admin') }}</option>
            <option value="user"@if($user->role === 'user') selected="selected"@endif>{{ __('admin.User') }}</option>
        </select>
    </div>--}}

    <div class="form-group">
        <input class="form-check-input" type="checkbox" id="active" name="active"@if($user->active) checked="checked"@endif> {{ __('admin.Active') }}
    </div>

    <div class="form-group">
        <label for="buy_count">{{ __('admin.Buy count') }}</label>
        <input class="form-control" id="buy_count" name="buy_count" type="number" min="0" max="2147483648" value="{{ $user->buy_count }}">
    </div>

    <div class="form-group">
        <label for="buy_sum">{{ __('admin.Buy sum') }}</label>
        <input class="form-control" id="buy_sum" name="buy_sum" type="number" min="0" max="65535" value="{{ $user->buy_sum }}" step="0.1">
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">{{ __('layout.Save') }}</button>
        <a href="{{ url($prefix) . '/users/roles/view?id=' . request()->get('id') }}">
            <button type="button" class="btn btn-success">Роли</button>
        </a>
    </div>
</form>
