<div class="card">
    <img class="card-img-top" src="{{ asset('storage/'  . $product -> frontImage() -> image) }}" alt="{{ $product -> name }}">
    <div class="card-body">
        <a href="{{ route('product.show', $product) }}"><h4 class="card-title">{{ $product -> name }}</h4></a>
        <p class="card-subtitle">From: <strong>{{ $product->getLocalPriceFrom() }} {{ \App\Marketplace\Utility\CurrencyConverter::getLocalSymbol() }}</strong>  - {{ $product -> category -> name }} - <span class="badge badge-info">{{ $product -> type }}</span></p>
        <p class="card-text">
            Posted by <a href="{{ route('vendor.show', $product -> user) }}" class="badge badge-info">{{ $product -> user -> username }}</a>, <strong>{{ $product -> quantity }}</strong> left
        </p>
        <a href="{{ route('product.show', $product) }}" class="btn btn-primary d-block">Buy now</a>
    </div>
</div>