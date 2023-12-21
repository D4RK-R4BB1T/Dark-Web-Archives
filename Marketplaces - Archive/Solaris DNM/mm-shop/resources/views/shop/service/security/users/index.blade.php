@extends('layouts.master')

@section('title', 'Служба безопасности')

@section('content')
    <div class="col-sm-7 col-md-5 col-lg-5 col-xs-24">
        @include('shop.service.security.components.sidebar')
    </div>

    <div class="col-sm-17 col-md-19 col-lg-19 col-xs-24 pull-right animated fadeIn">
        @include('shop.service.security.components.user-search')

        <form action="" method="post">
            {{ csrf_field() }}
            <div class="well block">
                <h3>Пользователи</h3>
                <hr class="small" />
                @if ($users->count() == 0)
                    <hr class="small" />
                    <div class="alert alert-info" style="margin-bottom: 0">И только мертвые с косами стоят.</div>
                @else
                    @foreach ($users as $user)
                        <div class="panel panel-default">
                            <div class="panel-heading"><b class="hint--top" aria-label="{{ $user->username }}">{!! $user->getPublicDecoratedName() !!}</b></div>
                            <div class="panel-body">
                                <div class="row">
                                    <div class="col-md-10">Покупок:</div><div class="col-md-14">{{ $user->buy_count }}</div>
                                </div>

                                {{-- кошельки шопа --}}
                                @if($user->employee && $user->employee->shop())
                                    <hr class="small">
                                    <p>
                                        <b>Кошельки магазина:</b>
                                    </p>
                                    @foreach($shopWallets as $shopWallet)
                                        <div class="row">
                                        <div class="col-md-10">
                                            <a rel="noopener noreferrer" style="color: #eb9106" href="https://www.blockchain.com/btc/address/{{ $shopWallet->segwit_wallet }}" target="_blank">
                                                {{ $shopWallet->segwit_wallet }}
                                            </a>
                                        </div>
                                        <div class="col-md-14">
                                            <span class="hint--top cursor-pointer" aria-label="{{ human_price(btc2rub($shopWallet->getRealBalance()), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">
                                            {{ $shopWallet->getHumanRealBalance() }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif

                                <hr class="small" />
                                <div class="row">
                                    <div class="col-md-24">
                                        <b>Личный кошелек:</b>
                                    </div>
                                </div>
                                <div class="row margin-top-8">
                                    <div class="col-md-24">
                                        @foreach($user->wallets as $wallet)
                                            <div class="row">
                                                <div class="col-md-10">
                                                    <a rel="noopener noreferrer" style="color: #eb9106" href="https://www.blockchain.com/btc/address/{{ $wallet->segwit_wallet }}" target="_blank">
                                                        {{ $wallet->segwit_wallet }}
                                                    </a>
                                                </div>
                                                <div class="col-md-14">
                                                    <span class="hint--top cursor-pointer" aria-label="{{ human_price(btc2rub($wallet->getRealBalance()), \App\Packages\Utils\BitcoinUtils::CURRENCY_RUB) }}">
                                                    {{ $wallet->getHumanRealBalance() }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>

                            </div>
                        </div>
                    @endforeach

                    <hr class="small" />

                    @if ($users->total() > $users->perPage())
                        <hr class="small" />
                        <div class="text-center">
                            {{ $users->appends(request()->input())->links() }}
                        </div>
                    @endif
                @endif
            </div>
        </form>
    </div>
@endsection