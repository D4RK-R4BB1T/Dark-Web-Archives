<span class="rating">
@for($i = 0; $i < round($rating); $i++)
    <i class="glyphicon glyphicon-star text-orange"></i>
@endfor
@for($i = 0; $i < 5 - round($rating); $i++)
    <i class="glyphicon glyphicon-star-empty text-muted"></i>
@endfor
</span>