<!-- settings/components/block-security-reminder -->
<div class="panel panel-default panel-sidebar gray block no-padding">
    <div class="panel-heading">
        <div class="row no-margin">
            <div class="title-container">{{ __('layout.Twofa authorization notes') }}</div>
        </div>
    </div>
    <div class="panel-body no-padding">
        <div class="list-group hover-menu">
            <div class="list-group-item">
                <p>
                    {{ __('layout.Two-fa app download') }} (<a target="_blank" href="https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2">Android</a>,
                    <a target="_blank" href="https://itunes.apple.com/pl/app/google-authenticator/id388497605?mt=8">iOS</a>)
                </p>
            </div>
            <div class="list-group-item">
                <p>{{ __('layout.Two-fa description line one') }}</p>
            </div>
            <div class="list-group-item">
                <p>{{ __('layout.Two-fa description line two') }}</p>
            </div>
            <div class="list-group-item">
                <p>{{ __('layout.Two-fa description line three') }}</p>
            </div>
        </div>
    </div>
</div>
<!-- / settings/components/block-security-reminder -->
