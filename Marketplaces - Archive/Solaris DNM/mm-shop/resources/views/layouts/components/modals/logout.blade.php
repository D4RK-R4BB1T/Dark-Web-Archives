<!-- layouts/components/modals/logout -->
<form action="{{ url('/auth/logout') }}" method="get">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'logout'])
    @slot('title', 'Выход из аккаунта')
        Вы действительно хотите выйти из аккаунта?
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Да</button>
        @endslot
        @slot('footer_btn', 'Нет')
    @endcomponent
</form>
<!-- /layouts/components/modals/logout -->