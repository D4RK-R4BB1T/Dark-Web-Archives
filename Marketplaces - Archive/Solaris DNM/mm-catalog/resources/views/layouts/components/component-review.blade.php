<div class="row">
    <div class="col-xs-8">
        {{--<strong>Рейтинг покупателя:</strong> {{ $review->user->getRating() }} <br />--}}
        <span class="text-muted">{{ __('shop.Buyer rating') }}:</span><br />
        @include('layouts.components.sections-rating', ['rating' => $review->getAverageRating()])<br />
        <span class="semi-small">{{ $review->created_at->format('d.m.Y') }}</span>
    </div>
    <div class="col-xs-16">
        <p style="min-height: 26px">{!! App\Packages\Utils\Formatters::formatReview($review->text) !!}</p>
        <p class="text-muted semi-small">
            {{ __('shop.Service rating') }}: <span class="text-orange">{{ $review->shop_rating }}</span>&nbsp;&nbsp;&nbsp;
            {{ __('shop.Courier rating') }}: <span class="text-orange">{{ $review->dropman_rating }}</span>&nbsp;&nbsp;&nbsp;
            {{ __('shop.Item rating') }}: <span class="text-orange">{{ $review->item_rating }}</span>
        </p>
        @if ($review->reply_text)
            <hr class="small" />
            <i class="text-muted">{{ __('shop.Staff reply') }}: {{ $review->reply_text }}</i>
        @endif
    </div>
</div>