<!-- balance/components/block-balance-reminder -->
<div class="panel panel-default panel-sidebar gray block no-padding">
    <div class="panel-heading">
        <div class="row no-margin">
            <div class="icon-container"><i class="icon mmicon-finances"></i></div>
            <div class="title-container">Памятка по обмену</div>
        </div>
    </div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <div class="list-group-item">
                <p>
                    Биткоины поступают на счет не мгновенно, а только после получения {{ config('mm2.confirmations_amount') }} {{ plural(config('mm2.confirmations_amount'), ['подтверждения', 'подтверждений', 'подтверждений']) }}.
                    Количество текущих подтверждений вы можете посмотреть на <a rel="noopener noreferrer" style="color: #eb9106" target="_blank" href="https://blockchain.info">сайте</a>
                </p>
            </div>
            <div class="list-group-item">
                <p>История баланса хранится в течение двух месяцев</p>
            </div>
        </div>
    </div>
</div>
<!-- / balance/components/block-balance-reminder -->