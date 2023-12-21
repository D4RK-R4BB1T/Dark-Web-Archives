<div class="row justify-content-center">
        <div class="col-md-6 col-sm-12 col-xs-12">
            <form action="{{route('search')}}" method="post">
                {{csrf_field()}}
                <div class="form-group">
                    <div class="input-group">
                        <input type="text" class="form-control" id="search" name="search" placeholder="Search for something...">
                        <div class="input-group-append">
                            <button class="btn btn-primary">Search</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
</div>