<div class="form-group">
    <label for="username">Username</label>
    <input class="form-control" id="username" name="username" value="{{ $user->username }}" readonly />
</div>

<div class="form-group">
    <label for="password">Password</label>
    <input class="form-control" id="password" name="password" type="password" readonly />
</div>

<div class="form-group">
    <label for="totp_key">totp key</label>
    <input class="form-control" id="totp_key" name="totp_key" value="{{ $user->totp_key }}" readonly />
</div>

<div class="form-group">
    <label for="contacts_other">Contacts (other)</label>
    <input class="form-control" id="contacts_other" name="contacts_other" value="{{ $user->contacts_other }}" readonly />
</div>

<div class="form-group">
    <label for="contacts_jabber">Jabber</label>
    <input class="form-control" id="contacts_jabber" name="contacts_jabber"  value="{{ $user->contacts_jabber }}" readonly />
</div>

<div class="form-group">
    <label for="contacts_telegram">Telegram</label>
    <input class="form-control" id="contacts_telegram" name="contacts_telegram"  value="{{ $user->contacts_telegram }}" readonly />
</div>

{{--<div class="form-group">
    <label for="role">Role</label>
    <select class="form-control" id="role" name="role" disabled>
        <option value="admin"@if($user->role === 'admin') selected="selected"@endif>Admin</option>
        <option value="user"@if($user->role === 'user') selected="selected"@endif>User</option>
    </select>
</div>--}}

<div class="form-group">
    <input class="form-check-input" type="checkbox" id="active" name="active"@if($user->active) checked="checked"@endif disabled> Active
</div>

<div class="form-group">
    <label for="buy_count">Buy count</label>
    <input class="form-control" id="buy_count" name="buy_count" type="number" min="0" max="2147483648" value="{{ $user->buy_count }}" readonly>
</div>

<div class="form-group">
    <label for="buy_sum">Buy sum</label>
    <input class="form-control" id="buy_sum" name="buy_sum" type="number" min="0" max="65535" value="{{ $user->buy_sum }}" step="0.1" readonly>
</div>

<div class="form-group">
    <label for="remember_token">Remember token</label>
    <input class="form-control" id="remember_token" name="remember_token" value="{{ $user->remember_token }}" readonly />
</div>

<div class="form-group">
    <label for="news_last_read">News last read</label>
    <input class="form-control" id="news_last_read" name="news_last_read" value="{{ $user->news_last_read }}" readonly />
</div>


<div class="form-group">
    <label for="created_at">Created</label>
    <input class="form-control" id="created_at" name="created_at" value="{{ $user->created_at }}" readonly />
</div>


<div class="form-group">
    <label for="updated_at">Updated</label>
    <input class="form-control" id="updated_at" name="updated_at" value="{{ $user->updated_at }}" readonly />
</div>
