<!-- layouts/navbar/guest-left -->
<style>
    .navbar-dropdown .dropdown-menu>li>a {
        padding: 5px 10px;
    }
    .navbar-dropdown .dropdown-menu>li>a>i {
        top: 2px;
        padding-right: 3px;
    }
</style>
<ul class="nav navbar-nav navbar-dropdown">
    {{--
    <li class="active"><a href="#"><i class="fa fa-shopping-cart fa-fw"></i> Product</a></li>
    <li><a href="#"><i class="fa fa-asterisk fa-fw"></i> Promotion</a></li>
    <li><a href="#"><i class="fa fa-book fa-fw"></i> Lookbook</a></li>
    <li><a href="#"><i class="fa fa-archive fa-fw"></i> Blog</a></li>
    <li><a href="#"><i class="fa fa-phone fa-fw"></i> About us</a></li>
    --}}
    <li class="{{ (isset($page) && $page === 'catalog') ? 'active' : '' }}"><a href="/catalog">{{ __('layout.Catalog') }}</a></li>
    <li class="{{ (isset($page) && $page === 'shops') ? 'active' : '' }}"><a href="/shops">{{ __('layout.Shops') }}</a></li>
    <li class="{{ (isset($page) && $page === 'news') ? 'active' : '' }}"><a href="/news">{{ __('layout.News') }}</a></li>
    <li class="{{ (isset($page) && $page === 'rules') ? 'active' : '' }}"><a href="/pages/rules">{{ __('layout.Rules') }}</a></li>
    <li class="dropdown">
        <a href="#">Форум <span class="caret"></span></a>
        <ul class="dropdown-menu orange" role="menu">
            <li role="presentation"><a role="menuitem" tabindex="-1" href="http://sol4rumagyfttumdv44d4dceoy7az4s37paicifeacwpemeahfkdnyad.onion"><i class="glyphicon glyphicon-link"></i> sol4rumagyfttumdv44d4dceoy7az4s37paicifeacwpemeahfkdnyad.onion</a></li>
            <li role="presentation"><a role="menuitem" tabindex="-1" href="http://sol4rumgahoy5yyidwqkcj3jtzyhk3d3bizpmiyywjblnsz4circqeid.onion"><i class="glyphicon glyphicon-link"></i> sol4rumgahoy5yyidwqkcj3jtzyhk3d3bizpmiyywjblnsz4circqeid.onion</a></li>
            <li role="presentation"><a role="menuitem" tabindex="-1" href="http://sol4rum7bebadscfsr46e7kfapybnayxcj3nvhghdqi2dqoelcmjgcad.onion"><i class="glyphicon glyphicon-link"></i> sol4rum7bebadscfsr46e7kfapybnayxcj3nvhghdqi2dqoelcmjgcad.onion</a></li>
            <li role="presentation"><a role="menuitem" tabindex="-1" href="http://sol4rums4vmv44rmtwtyd2kv3tj4quec4wydlan6p4us34uvmltf5nqd.onion"><i class="glyphicon glyphicon-link"></i> sol4rums4vmv44rmtwtyd2kv3tj4quec4wydlan6p4us34uvmltf5nqd.onion</a></li>
        </ul>
    </li>
</ul>
<!-- / layouts/navbar/guest-left -->