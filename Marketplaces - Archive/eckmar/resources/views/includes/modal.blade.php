
<div class="modal fade in show position-static d-block" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">{{ $title }}</h5>
                <a href="{{ back() }}" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </a>
            </div>
            <div class="modal-body">
                {{ $slot }}
            </div>
            <div class="modal-footer text-center justify-content-center">
                <a href="{{ $backRoute }}" class="btn btn-secondary" data-dismiss="modal">Dismiss</a>
                @if($nextRoute !== '')
                    <a href="{{ $nextRoute }}" class="btn btn-success">Confirm</a>
                @endif
            </div>
        </div>
    </div>
</div>