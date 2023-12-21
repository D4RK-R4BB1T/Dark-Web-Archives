<div class="nav flex-column nav-pills" id="v-pills-tab" role="tablist" aria-orientation="vertical">
    <a class="nav-link @isroute('admin.index') active @endisroute" href="{{ route('admin.index') }}">
        <i class="fas fa-columns mr-2"></i>
        Index
    </a>

    @hasAccess('categories')
    <a class="nav-link @isroute('admin.categories') active @endisroute" href="{{ route('admin.categories') }}">
        <i class="fas fa-list mr-2"></i>
        Categories
    </a>
    @endhasAccess

    @hasAccess('messages')
    <a class="nav-link @isroute('admin.messages.mass') active @endisroute" href="{{ route('admin.messages.mass') }}">
        <i class="fas fa-envelope mr-2"></i>
        Mass Messages
    </a>
    @endhasAccess

    @hasAccess('users')
    <a class="nav-link @isroute('admin.users') active @endisroute" href="{{ route('admin.users') }}">
        <i class="fas fa-users mr-2"></i>
        Users
    </a>
    @endhasAccess

    @hasAccess('products')
    <a class="nav-link @isroute('admin.product') active @endisroute" href="{{ route('admin.products') }}">
        <i class="fas fa-shopping-bag mr-2"></i>
        Products
    </a>
    @endhasAccess

    @hasAccess('products')
    @isModuleEnabled('FeaturedProducts')
    <a class="nav-link @isroute('admin.featuredproducts') active @endisroute" href="{{ route('admin.featuredproducts.show') }}">
        <i class="fas fa-medal mr-2"></i>
        Featured Products
    </a>
    @endisModuleEnabled
    @endhasAccess

    @hasAccess('purchases')
    <a class="nav-link @isroute('admin.purchases') active @endisroute @isroute('admin.purchase') active @endisroute" href="{{ route('admin.purchases') }}">
        <i class="fas fa-shopping-cart mr-2"></i>
        Purchases
    </a>
    @endhasAccess

    @hasAccess('logs')
    <a class="nav-link @isroute('admin.log') active @endisroute" href="{{ route('admin.log') }}">
        <i class="fas fa-list-alt mr-2"></i>
        Log
    </a>
    @endhasAccess

    <a class="nav-link @isroute('admin.bitmessage') active @endisroute" href="{{ route('admin.bitmessage') }}">
        <i class="fas fa-envelope-open mr-2"></i>
        Bitmessage
    </a>

    @hasAccess('disputes')
    <a class="nav-link @isroute('admin.disputes') active @endisroute" href="{{ route('admin.disputes') }}">
        <i class="fas fa-exclamation-triangle mr-2"></i>
        Disputes
    </a>
    @endhasAccess

    @hasAccess('tickets')
    <a class="nav-link @isroute('admin.tickets') active @endisroute @isroute('admin.tickets') active @endisroute" href="{{ route('admin.tickets') }}">
        <i class="far mr-2 fa-question-circle"></i>
        Tickets
    </a>
    @endhasAccess

    @hasAccess('vendorpurchase')
    <a class="nav-link @isroute('admin.vendor.purchases') active @endisroute @isroute('admin.vendor.purchases') active @endisroute" href="{{ route('admin.vendor.purchases') }}">
        <i class="far mr-2 fa-list-alt"></i>
        Vendor Purchases
    </a>
    @endhasAccess

</div>