<h3>Edit profile</h3>
<hr>



<form action="{{route('profile.vendor.update.post')}}" method="post" class="mb-3">
    {{csrf_field()}}
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="description">Description</label>
                <textarea name="description" id="" cols="30" rows="6" class="form-control" style="resize: none;">{{$vendor->about}}</textarea>
                <span class="form-text text-muted">Displayed on your vendor profile page. Limited to 120 characters</span>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="profilebg">Profile background</label>
                <select name="profilebg" id="profilebg" class="form-control">
                    @foreach(config('vendor.profile_bgs') as $key => $class)
                        <option value="{{$key}}" @if($vendor->getProfileBg() == $class) selected @endif>{{ucfirst($key)}}</option>
                    @endforeach
                </select>
                <div class="card mt-1">
                    <div class="card-header">
                        Current background
                    </div>
                    <div class="card-body h-100 {{$vendor->getProfileBg()}}">
                        <span style="opacity: 0;">test</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <button type="submit" class="btn btn-primary">Update</button>
        </div>
    </div>
</form>