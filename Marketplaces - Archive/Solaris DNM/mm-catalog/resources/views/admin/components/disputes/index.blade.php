<?php
    $prefix = isset($prefix) ? $prefix : '/admin';
?>
<form role="form" action="{{ url('/admin/disputes/filter') }}" method="post">
    {{ csrf_field() }}
    <div class="row">
        <div class="col-lg-10  col-sm-10 col-xs-24">
            <div class="form-group has-feedback">
                <select class="form-control" name="status">
                    <option value="" readonly @if(empty(request()->get('status')))selected @endif>Статус</option>
                    <option value="opened" @if('opened' == request()->get('status'))selected @endif>Открыт</option>
                    <option value="closed" @if('closed' == request()->get('status'))selected @endif>Закрыт</option>
                </select>
                <span class="glyphicon glyphicon-menu-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-lg-10  col-sm-10 col-xs-24">
            <div class="form-group has-feedback">
                <select class="form-control" name="moderator">
                    <option value="" readonly @if(empty(request()->get('moderator')))selected @endif>Модератор</option>
                    <option value="undefined" @if('undefined' == request()->get('moderator'))selected @endif>Не указан</option>
                    <option value="all" @if('all' == request()->get('moderator'))selected @endif>Все</option>
                    @foreach($moderators as $moderator)
                        <option value="{{ $moderator->moderator }}" @if($moderator == request()->get('moderator'))selected @endif>{{ $moderator->moderator }}</option>
                    @endforeach
                </select>
                <span class="glyphicon glyphicon-menu-down form-control-feedback"></span>
            </div>
        </div>
        <div class="col-lg-4 col-sm-4 col-xs-24 text-left">
            <div class="form-group" style="height: 33px; line-height: 32px;">
                <button class="btn btn-orange" type="submit">{{ __('layout.Search') }}</button>
                <a class="btn btn-orange" href="{{ url('/admin/disputes/') }}">Сбросить</a>
            </div>
        </div>
    </div> <!-- /.row -->
</form>


<div>
    {{ $disputes->links() }}
</div>

<div class="list-group">
    @foreach ($disputes as $dispute)
        <a href="{{ catalog_jump_url($dispute->shop->id, '/') }}" class="list-group-item">
            <h4 class="list-group-item-heading">Диспут #{{ $dispute->dispute_id }}</h4>
            <p class="list-group-item-text">Название магазина: {{ $dispute->shop->title }}</p>
            <p class="list-group-item-text">Пользователь: {{ $dispute->creator }}</p>
            <p class="list-group-item-text">Статус: {{ $dispute->status === 'opened' ? 'Открыт' : 'Закрыт' }}</p>
            <p class="list-group-item-text">Дата последнего обновления: {{ \Carbon\Carbon::parse($dispute->dispute_updated_at)->format('d.m.Y H:i') }}</p>
            <p class="list-group-item-text text-green"><b>Ответственный модератор:</b> {{ $dispute->moderator }}</p>
        </a>
    @endforeach
</div>
<div>
    {{ $disputes->links() }}
</div>

