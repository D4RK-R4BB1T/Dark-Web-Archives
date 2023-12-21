<!-- layouts/components/modals/logout -->
<form action="/auth/logout" method="get">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'logout'])
        @slot('title', 'Sign out')
        {{ __('layout.Confirm sign out') }}
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">{{ __('layout.Yes') }}</button>
        @endslot
        @slot('footer_btn', __('layout.No'))
    @endcomponent
</form>
<!-- /layouts/components/modals/logout -->