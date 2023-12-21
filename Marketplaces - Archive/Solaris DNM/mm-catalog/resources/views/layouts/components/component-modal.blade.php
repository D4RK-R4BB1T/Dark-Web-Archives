<!-- layouts/components/component-modal -->
<div class="mm-modal">
    @if (!isset($values))
        <input class="hidden" name="mm-modal-toggle_{{ $id }}" value="0" type="radio" id="mm-modal-toggle_{{ $id }}_hide" autocomplete="off" checked="checked">
        <input class="mm-modal-toggle" name="mm-modal-toggle_{{ $id }}" value="1" type="radio" id="mm-modal-toggle_{{ $id }}" autocomplete="off">
    @endif
    <div class="modal animated fadeIn modal-scrollable" id="mm-modal_{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="mm-modal-label_{{ $id }}">
        <label class="modal-backdrop fade" for="mm-modal-toggle_{{ $id }}_hide"></label>
        <div class="modal-dialog @if(isset($modal_lg) && $modal_lg)modal-lg @endif" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <label for="mm-modal-toggle_{{ $id }}_hide" class="close" data-dismiss="modal" aria-label="Close" style="display: flex; align-items: center;">
                        <span aria-hidden="true">&times;</span>
                    </label>
                    @if (isset($title))
                        <h4 class="modal-title" id="mm-modal-label_{{ $id }}">{{ $title }}</h4>
                    @endif
                </div>
                <div class="modal-body">
                    {{ isset($slot) ? $slot : '-' }}
                </div>
                @if (isset($footer_btn_before) || isset($footer_btn) || isset($footer_btn_after))
                    <div class="modal-footer">
                        {{ isset($footer_btn_before) ? $footer_btn_before : '' }}
                        @if (isset($footer_btn))
                            <label for="mm-modal-toggle_{{ $id }}_hide" class="btn btn-default" data-dismiss="modal">
                                {{ $footer_btn ?: 'Close' }}
                            </label>
                        @endif
                        {{ isset($footer_btn_after) ? $footer_btn_after : '' }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
<!-- / layouts/components/component-modal -->