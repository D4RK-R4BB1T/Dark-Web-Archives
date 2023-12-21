<?php
$prefix = $prefix ?? '/admin';
?>

<div>
    <div class="alert alert-info">
        Для сбора статистики необходимо к любой ссылке в каталоге или магазине добавить GET-параметр advstats=НОМЕР
        Например: http://solaris6hl3hd66utabkeuz2kb7nh5fgaa5zg7sgnxbm3r2uvsnvzzad.onion/?advstats=1
    </div>
    @if ($errors->any())
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <form class="navbar-form" method="post">
        {{ csrf_field() }}
        <div class="form-group">
            <input class="form-control" type="text" name="title" required placeholder="Название">
        </div>
        <button class="btn btn-default">Добавить</button>
    </form>
</div>

<div>
    {{ $advstats->links() }}
</div>

<style>
    .break-words {
        word-break: break-all;
        white-space: normal !important;
    }

    .inline {
        display: inline;
    }

    .no-break {
        width: max-content;
    }
</style>

<div class="table-responsive">
    <table class="table">
        <thead>
            <tr>
                <th>Номер</th>
                <th>Название</th>
                <th>Просмотров</th>
                <th>Переходов</th>
                <th>Регистраций</th>
                <th>Создано</th>
                <th>Отредактировано</th>
                <th>Действия</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($advstats as $stats)
                <tr>
                    <th scope="row">{{ $stats->id }}</th>
                    <td class="break-words">{{ $stats->title }}</td>
                    <td>{{ $stats->views }}</td>
                    <td>{{ $stats->uniques }}</td>
                    <td>{{ $stats->registrations }}</td>
                    <td>{{ $stats->created_at }}</td>
                    <td>{{ $stats->updated_at }}</td>
                    <td>
                        <div class="no-break">
                            <a href="{{ $prefix }}/advstats/{{ $stats->id }}/edit" class="btn btn-primary btn-xs">
                                <span class="glyphicon glyphicon-pencil"></span>
                            </a>
                            <form class="inline" method="post" action="{{ $prefix }}/advstats/{{ $stats->id }}">
                                {{ csrf_field() }}
                                <input type="hidden" name="_method" value="DELETE">
                                <button class="btn btn-danger btn-xs">
                                    <span class="glyphicon glyphicon-trash"></span>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>

<div>
{{ $advstats->links() }}
</div>