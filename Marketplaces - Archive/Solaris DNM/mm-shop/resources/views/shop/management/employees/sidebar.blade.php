<!-- shop/management/employees/sidebar -->
@include('shop.management.components.block-employees-actions')
@include('shop.management.components.block-employees-list')
<!-- / shop/management/employees/sidebar -->

@section('modals')
    @include('shop.management.components.modals.employees-add')
@endsection