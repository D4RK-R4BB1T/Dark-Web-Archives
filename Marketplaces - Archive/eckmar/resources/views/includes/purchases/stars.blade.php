@for($i = 1; $i<= $stars; $i++)
    <i class="fas fa-star"></i>
@endfor
{{-- half star --}}
@if(($stars - round($stars, 0)) != 0)
    <i class="fas fa-star-half-alt"></i>
@endif
@for($i = 1; $i<= 5 -$stars; $i++)
    <i class="far fa-star"></i>
@endfor