<!-- shop/management/components/modals/order-extend -->
<form action="{{ url('/shop/management/orders/'.$orderId.'/extendPreorderTime') }}" method="post">
    {{ csrf_field() }}
    @component('layouts.components.component-modal', ['id' => 'orderExtend'])
        @slot('title', 'Продление времени предзаказа')
        <div class="row">
            <div class="col-xs-20 col-xs-offset-2">
                <div class="form-group" style="margin-bottom: 0">
                    <label for="time">
                        Выберите, на сколько нужно продлить предзаказ:
                    </label>
                    <select name="time" id="time" class="form-control">
                        @foreach($steps as $time)
                            <option value="{{ $time }}">{{ trans_choice('plur.hours', $time, ['value' => $time]) }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        @slot('footer_btn_before')
            <button type="submit" class="btn btn-orange">Продлить</button>
        @endslot
        @slot('footer_btn', 'Закрыть')
    @endcomponent
</form>
<!-- / shop/management/components/modals/order-extend -->
