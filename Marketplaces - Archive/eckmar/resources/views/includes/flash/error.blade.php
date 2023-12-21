@if(session()->has('errormessage'))
    <div id="error" class="alert alert-danger text-center">
        {{ session()->get('errormessage') }}
    </div>
@endif
@if(session()->has('error'))
    <div id="error" class="alert alert-danger text-center">
        {{ session()->get('error') }}
    </div>
@endif
