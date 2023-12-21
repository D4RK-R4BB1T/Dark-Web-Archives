@extends('master.admin')

@section('admin-content')

    @include('includes.flash.success')
    @include('includes.flash.error')
    @include('includes.validation')

    <h3 class="mb-5">Message to everyone</h3>
    <hr>
    <form action="{{ route('admin.messages.send') }}" method="POST">
        {{ csrf_field() }}
        <div class="form-row">
            <div class="col-md-12 mb-2">
                <label for="message">
                    Message:
                </label>
                <textarea name="message" placeholder="Paste your message here." id="message" class="form-control" rows="7"></textarea>
            </div>
            <div class="col-md-6">
                <label>Groups:</label>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="admins" id="admins" name="groups[]">
                    <label class="form-check-label" for="admins">
                        Admins
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="vendors" id="vendors" name="groups[]">
                    <label class="form-check-label" for="vendors">
                        Vendors
                    </label>
                </div>
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="buyers" id="buyers" name="groups[]">
                    <label class="form-check-label" for="buyers">
                        Buyers
                    </label>
                </div>
            </div>
            <div class="col-md-6 justify-content-lg-end d-flex">
                <button type="submit" class="btn btn-outline-primary mt-auto">Send messages</button>
            </div>
        </div>


    </form>
    

@endsection