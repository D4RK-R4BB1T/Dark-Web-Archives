@search
    <div class="container-fluid bg-light py-4">
        <div class="container ">
            <div class="row justify-content-center">
                <div class="col-md-6">
                    <form action="{{route('search')}}" method="POST" class="form-inline h-100">
                        {{csrf_field()}}
                        <div class="input-group w-100">
                            <input type="text" class="form-control form-control" id="search" name="search"
                                   placeholder="Search for something..." value="{{app('request')->input('query')}}">
                            <div class="input-group-append">
                                <button class="btn  btn-info">Search</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsearch
