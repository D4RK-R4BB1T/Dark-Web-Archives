<div class="row">
    <div class="col-xs-8">
        {{--<strong>Рейтинг покупателя:</strong> {{ $review->user->getRating() }} <br />--}}
        <span class="text-muted">Впечатление от покупки:</span><br />
        @include('shop.sections-rating', ['rating' => $review->getAverageRating()])<br />
        <span class="semi-small">{{ $review->created_at->format('d.m.Y') }}</span><br />
        @if(isset($showCity) && $showCity && $review->city)
            <span class="semi-small">
                <a href="?{{ http_build_query(['review_city' => $review->city->id, 'city' => Request::get('city')]) }}">{{ $review->city->title }}</a>
            </span>
            <br />
        @endif
        @if (Auth::check() && Auth::user()->employee && Auth::user()->can('management-sections-orders'))
            &nbsp;
            <a class="dark-link hint--top" aria-label="Перейти к заказу" href="{{ url('/shop/management/orders/'.$review->order_id) }}">
                <i class="glyphicon glyphicon-share"></i>
            </a>
        @endif
        @if (Auth::check() && Auth::user()->employee && Auth::user()->can('management-reviews', $review))
            &nbsp;
            <a class="dark-link hint--top" aria-label="{{ $review->hidden ? 'Показать отзыв' : 'Скрыть отзыв' }}" href="{{ url('/shop/management/goods/reviews/hideToggle/'.$review->good_id.'/'.$review->id.'?_token='.csrf_token()) }}">
                <i class="glyphicon glyphicon-eye-{{ $review->hidden ? 'open' : 'close' }}"></i>
            </a>
            &nbsp;
            <a class="dark-link hint--top" aria-label="Ответить на отзыв" href="{{ url('/shop/management/goods/reviews/reply/'.$review->good_id.'/'.$review->id) }}" target="_blank"><i class="glyphicon glyphicon-share-alt"></i></a>
        @endif
        @if (Auth::check() && (Auth::user()->isSecurityService() || Auth::user()->isModerator()) && $review->hidden)
            <br />
            <strong class="text-danger">Отзыв скрыт</strong>
        @endif
    </div>
    <div class="col-xs-16">
        <p class="text-break-all" style="min-height: 26px">{!! App\Packages\Utils\Formatters::formatReview($review->text) !!}</p>
        <p class="text-muted semi-small">
            Оценка сервиса: <span class="text-orange">{{ $review->shop_rating }}</span>&nbsp;&nbsp;&nbsp;
            Оценка кладмена: <span class="text-orange">{{ $review->dropman_rating }}</span>&nbsp;&nbsp;&nbsp;
            Оценка стаффа: <span class="text-orange">{{ $review->item_rating }}</span>
        </p>
        @if ($review->reply_text)
            <hr class="small" />
            <i class="text-muted text-break-all">Ответ магазина: {{ $review->reply_text }}</i>
        @endif

    </div>
</div>