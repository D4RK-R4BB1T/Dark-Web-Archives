<h3>Solaris Exchange API</h3>
<hr class="small" />
Данная страница содержит информацию по подключению к API магазина на платформе Solaris в качестве обменника.
<hr class="small" />
Подключение к магазину в качестве обменника, работающего через API, осуществляется в 3 этапа:
<ol>
    <li><strong>Реализация протокола Solaris Exchange API на сервере обменника (реализация обработчика).</strong></li>
    <li><strong>Регистрация в качестве обменника в магазине</strong></li>
    <li><strong>Настройка магазина на работу с сервером обменника.</strong></li>
</ol>
<hr class="small" />
<h3>Алгоритм работы магазина и обработчика</h3>
<p>
    Алгоритм работы магазина и обработчика определен следующим образом:
    <ol>
    <li>
        <strong>Периодически (раз в 5-10 минут) обменник отправляет на сервер информацию о текущем курсе обмена.</strong><br />
        Эта информация используется на странице обмена и позволяет пользователю видеть актуальный курс обмена, перед созданием заявки.
        <br /><br />
    </li>
    <li>
        <strong>При создании новой заявки на обмен магазин запрашивает на сервере обменника информацию о реквизитах: адрес кошелька, сумма и комментарий.</strong> <br />
        После того, как пользователь сообщает об оплате заявки с его стороны, на счете обменника резервируется необходимая сумма, обработчик получает на вход номер заявки и отправляет её на <strong>фоновую</strong> проверку. <br />
        <strong>Под фоновой проверкой понимается мгновенный (не блокирующий) ответ обработчика на поступившую заявку. </strong> Максимальное время ожидания ответа от обработчика ограничено 10 секундами с учетом инициализации соединения, поэтому крайне не рекомендуется начинать проверку оплаты до формирования ответа для магазина. Правильным способом является отправка задания на проверку в очередь.
        <br /><br />
    </li>
    <li>
        <strong>После проверки оплаты обработчик отправляет статус оплаты в магазин.</strong><br />
        Если магазин отмечает платеж успешным, то биткоины переводится на счет пользователя и обмен успешно завершается, если оплата не проходит - ситуация требует ручного вмешательства и биткоины остаются в резерве до решения проблемы.
    </li>
    </ol>
</p>
<hr class="small" />
<h3>Протокол обмена данными и методы API</h3>
<p>
    В качестве формата обмена данными используется <a href="https://en.wikipedia.org/wiki/JSON" target="_blank"><strong>JSON</strong></a>. Готовые реализации сериализаторов данного формата встроены во все популярные языки программирования.
    <br /><br />
    Ключ магазина задается в панели администратора в магазине и используется для идентификации магазина на стороне обработчика. <strong>Он должен храниться в секрете, так как именно он используется для авторизации обменника при запросах к магазину.</strong><br /><br />
    Структура запроса определяется как JSON вида:
<pre><code>{"shop_key":"ключ магазина","action":"название метода","request":"данные"}</code></pre>
    Структура ответа в случае успеха определяется как JSON вида:
<pre><code>{"status":"ok","response":"данные"}</code></pre> или в случае ошибки:
<pre><code>{"status":"error","error":"описание ошибки"}</code></pre>
<div class="alert alert-info">При запросе от обменника к магазину в запрос необходимо добавить дополнительное поле exchange_id, со значением, указанным в панели обменника.</div>

<strong>Важно!</strong> Все запросы выполняются без использования form-data и содержат в себе только JSON-содержимое. <br />
Пример получения содержимого запроса на языке PHP: <br />
<pre><code>&lt;?php
$request = file_get_contents('php://input');
var_dump(json_decode($request));
</code></pre>
</p>

<hr class="small" />

<h4>Метод update_rates</h4>
<img src="{{ url('/assets/img/exchange_api/4.png') }}" /><br />
Используется для обновления курса обмена. <br />
Запрос должен содержать следующие параметры:
<ul>
    <li><strong>btc_rub</strong> - курс 1 BTC к рублю, число или дробь</li>
</ul>

Вызывается на сервере магазина путем POST-запроса на адрес: <code><strong>{{ URL::to('/api/exchanges') }}</strong>?action=update_rates</code><br />

Пример запроса и ожидаемого ответа:
<div class="row">
    <div class="col-sm-24">
        <strong>Запрос:</strong>
        <pre><code>{"exchange_id":"1","shop_key":"demo","action":"update_rates","request":{"btc_rub":123456.78}}</code></pre>
    </div>
    <div class="col-sm-24">
        <strong>Ответ:</strong>
        <pre><code>{"status":"ok","response":true}</code></pre>
    </div>
</div>

<hr class="small" />

<h4>Метод make_exchange</h4>
<img src="{{ url('/assets/img/exchange_api/1.png') }}"><br />
Используется для получения параметров для платежа с сервера обменника. <br />
Запрос содержит в себе следующие параметры:
<ul>
    <li><strong>request_id</strong> - идентификатор заявки для последующей отправки результата</li>
    <li><strong>btc_amount</strong> - сумма для обмена в BTC</li>
    <li><strong>btc_rub_rate</strong> - последний курс BTC к рублю, который был загружен обменником на сервер. Его можно использовать для перевода стоимости обратно в рубли, но сумма платежа в любом случае генерируется на вашей стороне.</li>
</ul>
В ответ ожидает получить следующие параметры:
<ul>
    <li><strong>address</strong> - адрес кошелька для оплаты, строка</li>
    <li><strong>amount</strong> - сумма к оплате, дробь или число</li>
    <li><strong>comment</strong> - комментарий к платежу, строка</li>
</ul>
Дополнительно можно запросить у пользователя какую-либо информацию, например, его номер телефона. Для этого в ответе необходимо добавить 2 дополнительных параметра:
<ul>
    <li><strong>need_input</strong> - в значении true</li>
    <li><strong>input_description</strong> - описание текстового поля, строка</li>
</ul>

Вызывается на сервере обменника путем POST-запроса на адрес: <code><strong>http://%HANDLER%/</strong>?action=make_exchange</code><br />

Пример запроса и ожидаемого ответа:
<div class="row">
    <div class="col-sm-24">
        <strong>Запрос:</strong>
        <pre><code>{"shop_key":"demo","action":"make_exchange","request":{"request_id":33,"btc_amount":0.01,"btc_rub_rate":123456.78}}</code></pre>
    </div>
    <div class="col-sm-24">
        <strong>Ответ:</strong>
        <pre><code>{"status":"ok","response":{"address":"79991234567","amount":1234.56,"comment":"22222-33333"}}</code></pre>
    </div>
</div>

<hr class="small" />

<h4>Метод notify_exchange</h4>
<img src="{{ url('/assets/img/exchange_api/2.png') }}"><br />
Используется для уведомления обменника о совершении платежа пользователем. <br />
Начинать проверку следует именно после получения этого уведомления.<br />
Запрос содержит в себе следующие параметры:

<ul>
    <li><strong>request_id</strong> - идентификатор заявки</li>
</ul>

В случае, если при вызове make_exchange была запрошена дополнительная информация (need_input), то запрос будет содержать дополнительный параметр:
<ul>
    <li><strong>input</strong> - данные, которые ввел пользователь</li>
</ul>

Вызывается на сервере обменника путем POST-запроса на адрес: <code><strong>http://%HANDLER%/</strong>?action=notify_exchange</code><br />

Пример ожидаемого запроса и ответа:
<div class="row">
    <div class="col-sm-24">
        <strong>Запрос:</strong>
        <pre><code>{"shop_key":"demo","action":"notify_exchange","request":{"request_id":33}}</code></pre>
    </div>
    <div class="col-sm-24">
        <strong>Ответ:</strong>
        <pre><code>{"status":"ok"}</code></pre>
    </div>
</div>

<hr class="small" />

<h4>Метод exchange_result</h4>
<img src="{{ url('/assets/img/exchange_api/3.png') }}"><br />
Используется для уведомления магазина о результате проверки. <br />
Запрос должен содержать следующие параметры:

<ul>
    <li><strong>request_id</strong> - идентификатор заявки</li>
    <li><strong>success</strong> - true или false, если заявка была оплачена или не оплачена соответственно</li>
</ul>

Вызывается на сервере магазина путем POST-запроса на адрес: <code><strong>{{ URL::to('/api/exchanges') }}</strong>?action=exchange_result</code><br />

Пример ожидаемого запроса и ответа:
<div class="row">
    <div class="col-sm-24">
        <strong>Запрос:</strong>
        <pre><code>{"exchange_id":"1","shop_key":"demo","action":"exchange_result","request":{"request_id":"33","success":true}}</code></pre>
    </div>
    <div class="col-sm-24">
        <strong>Ответ:</strong>
        <pre><code>{"status":"ok","response":true}</code></pre>
    </div>
</div>

<hr class="small" />

<h4>Метод get_settings</h4>
Используется для изменения настроек обменника. <br />
Запрос не требует параметров. <br />
<br />
В ответ возвращаются следующие параметры:
<ul>
    <li><strong>active</strong> - true или false, если обменник включен для пользователей или выключен соответственно</li>
    <li><strong>min_amount</strong> - минимальная сумма для обмена в рублях, число или дробь</li>
    <li><strong>max_amount</strong> - максимальная сумма для обмена в рублях, число или дробь</li>
    <li><strong>reserve_time</strong> - время на оплату в минутах, число</li>
</ul>

Вызывается на сервере магазина путем POST-запроса на адрес: <code><strong>{{ URL::to('/api/exchanges') }}</strong>?action=get_settings</code><br />

Пример ожидаемого запроса и ответа:
<div class="row">
    <div class="col-sm-24">
        <strong>Запрос:</strong>
        <pre><code>{"exchange_id":"1","shop_key":"demo","action":"get_settings","request":{}}</code></pre>
    </div>
    <div class="col-sm-24">
        <strong>Ответ:</strong>
        <pre><code>{"status":"ok","response":{"active":true,"min_amount":100,"max_amount":10000,"reserve_time":20}}</code></pre>
    </div>
</div>

<hr class="small" />

<h4>Метод update_settings</h4>
Используется для изменения настроек обменника. <br />
Запрос должен содержать следующие параметры:

<ul>
    <li><strong>active</strong> - true или false, если обменник включен для пользователей или выключен соответственно</li>
    <li><strong>min_amount</strong> - минимальная сумма для обмена в рублях, число или дробь</li>
    <li><strong>max_amount</strong> - максимальная сумма для обмена в рублях, число или дробь</li>
    <li><strong>reserve_time</strong> - время на оплату в минутах, число</li>
</ul>

Вызывается на сервере магазина путем POST-запроса на адрес: <code><strong>{{ URL::to('/api/exchanges') }}</strong>?action=update_settings</code><br />

Пример ожидаемого запроса и ответа:
<div class="row">
    <div class="col-sm-24">
        <strong>Запрос:</strong>
        <pre><code>{"exchange_id":"1","shop_key":"demo","action":"update_settings","request":{"active":true,"min_amount":100,"max_amount":10000,"reserve_time":20}}</code></pre>
    </div>
    <div class="col-sm-24">
        <strong>Ответ:</strong>
        <pre><code>{"status":"ok","response":true}</code></pre>
    </div>
</div>

<hr class="small" />


<h3>Настройка магазина на работу с API</h3>
<p>
    После настройки обработчика, обменник может зарегистрироваться в магазине по следующей ссылке: <a target="_blank" href="{{ url("/exchange/register") }}">{{ URL::to('/exchange/register') }}</a>. <br />
    Для регистрации потребуется код приглашения, который может выдать главный администратор магазина. <br />

    Дальнейшая настройка осуществляется в панели обменника. Для удобства отладки в панели отображается дополнительная информация о последнем полученном запросе и его обработке. <br />
    <a href="{{ url("/assets/img/exchange_api/settings.jpg") }}" target="_blank"><img src="{{ url('/assets/img/exchange_api/settings.jpg') }}" style="max-width: 300px"/></a> <br />
    <span class="text-muted">(изображение кликабельно)</span>

</p>
