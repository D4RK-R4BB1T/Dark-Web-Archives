@extends('master.admin')

@section('admin-content')
    <div class="row">
        <div class="col">
            <h4>
                Bitmessage
            </h4>
            <hr>
            <table class="table table-bordered table-hover">
                <thead>
                    <th>Name</th>
                    <th>Status</th>
                </thead>
                <tr>
                    <td>
                        Bitmessage service
                    </td>
                    <td>
                        @if($enabled)
                            <span class="badge badge-success">Enabled</span>
                        @else
                            <span class="badge badge-danger">Disabled</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        Service test
                    </td>
                    <td>
                        @if($test)
                            <span class="badge badge-success">Online</span>
                        @else
                            <span class="badge badge-danger">Offline</span>
                        @endif
                    </td>
                </tr>
                <tr>
                    <td>
                        Marketplace address
                    </td>
                    <td>
                        @if($address !== null && $address !== '')
                            <span>{{$address}}</span>
                        @else
                            <span class="badge badge-danger">Not set</span>
                        @endif
                    </td>
                </tr>
            </table>
        </div>
    </div>

@stop