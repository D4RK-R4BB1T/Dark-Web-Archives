<!-- tickets/components/block-filters -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">{{ __('feedback.Filters') }}</div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket?{{ http_build_query(request()->except(['category', 'page'])) }}" class="list-group-item @if(!request()->get('category'))active @endif">
                <span class="hint--top badge inverse" aria-label="Закрытых">{{ array_sum($counters['closed']) }}</span>
                <span class="hint--top badge yellow" aria-label="Открытых">{{ array_sum($counters['opened']) }}</span>
                Все
            </a>
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket?category={{ App\Models\Tickets\Ticket::CATEGORY_COMMON_SELLER_QUESTION }}&{{ http_build_query(request()->except(['category', 'page'])) }}" class="list-group-item @if(request()->get('category') === App\Models\Tickets\Ticket::CATEGORY_COMMON_SELLER_QUESTION)active @endif">
                <span class="hint--top badge inverse" aria-label="Закрытых">{{ $counters['closed'][App\Models\Tickets\Ticket::CATEGORY_COMMON_SELLER_QUESTION] }}</span>
                <span class="hint--top badge yellow" aria-label="Открытых">{{ $counters['opened'][App\Models\Tickets\Ticket::CATEGORY_COMMON_SELLER_QUESTION] }}</span>
                Общие вопросы от продавцов
            </a>
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket?category={{ App\Models\Tickets\Ticket::CATEGORY_COMMON_BUYER_QUESTION }}&{{ http_build_query(request()->except(['category', 'page'])) }}" class="list-group-item @if(request()->get('category') === App\Models\Tickets\Ticket::CATEGORY_COMMON_BUYER_QUESTION)active @endif">
                <span class="hint--top badge inverse" aria-label="Закрытых">{{ $counters['closed'][App\Models\Tickets\Ticket::CATEGORY_COMMON_BUYER_QUESTION] }}</span>
                <span class="hint--top badge yellow" aria-label="Открытых">{{ $counters['opened'][App\Models\Tickets\Ticket::CATEGORY_COMMON_BUYER_QUESTION] }}</span>
                Общие вопросы от покупателей
            </a>
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket?category={{ App\Models\Tickets\Ticket::CATEGORY_APPLICATION_FOR_OPENING }}&{{ http_build_query(request()->except(['category', 'page'])) }}" class="list-group-item @if(request()->get('category') === App\Models\Tickets\Ticket::CATEGORY_APPLICATION_FOR_OPENING)active @endif">
                <span class="hint--top badge inverse" aria-label="Закрытых">{{ $counters['closed'][App\Models\Tickets\Ticket::CATEGORY_APPLICATION_FOR_OPENING] }}</span>
                <span class="hint--top badge yellow" aria-label="Открытых">{{ $counters['opened'][App\Models\Tickets\Ticket::CATEGORY_APPLICATION_FOR_OPENING] }}</span>
                Заявки на открытие магазина
            </a>
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket?category={{ App\Models\Tickets\Ticket::CATEGORY_COOPERATION }}&{{ http_build_query(request()->except(['category', 'page'])) }}" class="list-group-item @if(request()->get('category') === App\Models\Tickets\Ticket::CATEGORY_COOPERATION)active @endif">
                <span class="hint--top badge inverse" aria-label="Закрытых">{{ $counters['closed'][App\Models\Tickets\Ticket::CATEGORY_COOPERATION] }}</span>
                <span class="hint--top badge yellow" aria-label="Открытых">{{ $counters['opened'][App\Models\Tickets\Ticket::CATEGORY_COOPERATION] }}</span>
                Сотрудничество
            </a>
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket?category={{ App\Models\Tickets\Ticket::CATEGORY_SECURITY_SERVICE }}&{{ http_build_query(request()->except(['category', 'page'])) }}" class="list-group-item @if(request()->get('category') === App\Models\Tickets\Ticket::CATEGORY_SECURITY_SERVICE)active @endif">
                <span class="hint--top badge inverse" aria-label="Закрытых">{{ $counters['closed'][App\Models\Tickets\Ticket::CATEGORY_SECURITY_SERVICE] }}</span>
                <span class="hint--top badge yellow" aria-label="Открытых">{{ $counters['opened'][App\Models\Tickets\Ticket::CATEGORY_SECURITY_SERVICE] }}</span>
                Служба безопасности
            </a>
            <a href="{{ \Auth::user()->isAdmin() ? '/admin' : '' }}/ticket?category={{ App\Models\Tickets\Ticket::CATEGORY_PAYMENT_ERRORS }}&{{ http_build_query(request()->except(['category', 'page'])) }}" class="list-group-item @if(request()->get('category') === App\Models\Tickets\Ticket::CATEGORY_PAYMENT_ERRORS)active @endif">
                <span class="hint--top badge inverse" aria-label="Закрытых">{{ $counters['closed'][App\Models\Tickets\Ticket::CATEGORY_PAYMENT_ERRORS] }}</span>
                <span class="hint--top badge yellow" aria-label="Открытых">{{ $counters['opened'][App\Models\Tickets\Ticket::CATEGORY_PAYMENT_ERRORS] }}</span>
                Ошибки оплат
            </a>
        </div>

        <hr class="small" />

        <form action="" method="get">
            <input type="hidden" name="category" value="{{ request()->get('category') }}">
            <div class="col-xs-24">
                <div class="form-group has-feedback">
                    <select class="form-control" name="status">
                        <option value="" @if(!request()->get('status'))selected="selected" @endif>Статус</option>
                        <option value="opened" @if(request()->get('status') === 'opened')selected="selected" @endif>Открытые</option>
                        <option value="closed" @if(request()->get('status') === 'closed')selected="selected" @endif>Закрытые</option>
                    </select>
                    <span class="glyphicon glyphicon glyphicon-chevron-down form-control-feedback"></span>
                </div>
            </div>

            <div class="col-xs-24">
                <div class="form-group has-feedback">
                    <input class="form-control" name="username" placeholder="Имя пользователя" value="{{ request('username') }}"/>
                    <span class="glyphicon glyphicon-user form-control-feedback"></span>
                </div>
            </div>

            <div class="col-xs-24">
                <div class="form-group has-feedback">
                    <input class="form-control" name="title" placeholder="Заголовок" value="{{ request('title') }}"/>
                    <span class="glyphicon glyphicon-comment form-control-feedback"></span>
                </div>
            </div>

            <div class="form-group text-center">
                <button class="btn btn-orange" type="submit">Фильтр</button>
            </div>
        </form>
    </div>
</div>
