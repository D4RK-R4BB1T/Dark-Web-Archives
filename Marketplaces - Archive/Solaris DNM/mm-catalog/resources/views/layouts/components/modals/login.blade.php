<!-- layouts/components/modals/login -->
<form class="form-horizontal" role="form" action="/auth/login" method="post">
    {{ csrf_field() }}
    <input type="hidden" name="redirect_to" value="/auth/login" />
    <input type="hidden" name="redirect_after_login" value="{{ request()->getRequestUri() }}" />

    @component('layouts.components.component-modal', ['id' => 'login'])
        @slot('title', __('layout.Log in'))
        <div class="form-group">
            <div class="col-xs-24">
                <input id="username" type="text" class="form-control" name="username" placeholder="{{ __('layout.Username') }}" required>
            </div>
        </div>
        <div class="form-group">
            <div class="col-md-24">
                <input id="password" type="password" class="form-control" name="password" placeholder="{{ __('layout.Password') }}" required>
            </div>
        </div>

        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">{{ __('layout.Log in') }}</button>
        @endslot
        @slot('footer_btn', __('layout.Close'))
    @endcomponent
</form>
<!-- / layouts/components/modals/login -->