<!-- shop/management/finances/sidebar -->
@include('shop.management.components.block-finances-actions')
@include('shop.management.components.block-finances-list')
<!-- / shop/management/finances/sidebar -->

@section('modals')
    @include('shop.management.components.modals.finances-add')
@endsection