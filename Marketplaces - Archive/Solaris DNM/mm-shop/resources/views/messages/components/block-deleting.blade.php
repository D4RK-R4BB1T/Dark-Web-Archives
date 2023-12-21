<!-- messages/components/block-deleting -->
<div class="panel panel-default panel-sidebar block no-padding">
    <div class="panel-heading">Удаление диалога</div>
    <div class="panel-body text-center">
        <p>Вы действительно хотите удалить этот диалог?</p>
        <form action="" method="post">
            {{ csrf_field() }}
            <button type="submit" class="btn btn-orange">Удалить диалог</button>
            &nbsp; &nbsp;
            <a class="text-muted" href="{{ URL::previous() }}">отменить</a>
        </form>
    </div>
</div>
<!-- / messages/components/block-threads -->