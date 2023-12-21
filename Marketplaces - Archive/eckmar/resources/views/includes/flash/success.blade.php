@if(session()->has('success'))
    <div class="alert alert-success my-2 text-center">
        {{session()->get('success')}}
    </div>
@endif