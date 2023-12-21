@if($paginator->hasPages())
    <nav>
        <ul class="pagination justify-content-center">
            <li class="page-item @if($paginator->onFirstPage()) disabled @endif">
                <a class="page-link" href="{{ $paginator->previousPageUrl() }}" rel="prev" @if($paginator->onFirstPage()) tabindex="-1" @endif><i class="fa fa-angle-left"></i> Previous</a>
            </li>

            @foreach($elements as $element)
                {{-- "Three Dots" Separator --}}
                @if(is_string($element))
                    <li class="page-item"><a class="page-link">{{ $element }}</a></li>
                @endif

                {{-- Array Of Links --}}
                @if(is_array($element))
                    @foreach ($element as $page => $url)
                        @if($page == $paginator->currentPage())
                            <li class="page-item disabled"><a class="page-link" tabindex="-1">{{ $page }}</a></li>
                        @else
                            <li class="page-item"><a class="page-link" href="{{ $url }}">{{ $page }}</a></li>
                        @endif
                    @endforeach
                @endif
            @endforeach

            <li class="page-item @if(!$paginator->hasMorePages()) disabled @endif">
                <a class="page-link" rel="next" href="{{ $paginator->nextPageUrl() }}">Next <i class="fa fa-angle-right"></i></a>
            </li>
        </ul>
    </nav>

@endif
