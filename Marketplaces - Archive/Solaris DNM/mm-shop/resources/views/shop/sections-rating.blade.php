<span class="rating hint--top" aria-label="Оценка: {{ round($rating, 2) }}">
@for($i = 0, $j = round($rating); $i < $j; $i++)
    <i class="glyphicon glyphicon-star text-orange {{ ($i == $j-1 && ($rating - (int)$rating) > 0.5 && ($rating - (int)$rating) !== 0) ? 'half' : ''}}"></i>
@endfor
@for($i = 0, $j = 5 - round($rating); $i < $j; $i++)
    <i class="glyphicon glyphicon-star text-very-muted"></i>
@endfor
</span>