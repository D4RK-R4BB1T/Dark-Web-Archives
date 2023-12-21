@if($fb->type == 'positive')
    <span class="fas fa-plus-circle text-success"></span>
@endif
@if($fb->type == 'neutral')
    <span class="fas fa-stop-circle text-secondary"></span>
@endif
@if($fb->type == 'negative')
    <span class="fas fa-minus-circle text-danger"></span>
@endif