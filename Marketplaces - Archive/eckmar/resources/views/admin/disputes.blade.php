@extends('master.admin')

@section('admin-content')
    <div class="row mb-4">
        <div class="col">
            <h3>
                All disputes
            </h3>
        </div>
    </div>

    <table class="table">
        <thead>
        <tr>
            <th>Purchase</th>
            <th>Buyer</th>
            <th>Vendor</th>
            <th>Winner</th>
            <th>Total</th>
            <th>Time</th>
        </tr>
        </thead>
        <tbody>
        @foreach($allDisputes as $dispute)
            <tr>
                <td>
                    <a href="{{ route('admin.purchase', $dispute -> purchase) }}" class="btn btn-sm btn-mblue mt-1">{{ $dispute -> purchase -> short_id }}</a>
                </td>
                <td>
                    {{ $dispute -> purchase -> buyer -> username }}
                </td>
                <td>
                    <a href="{{ route('vendor.show', $dispute -> purchase -> vendor) }}">{{ $dispute -> purchase -> vendor -> user -> username }}</a>
                </td>
                <td>
                    @if($dispute -> isResolved())
                        {{ $dispute -> winner -> username }}
                    @else
                        <span class="badge badge-warning">Unresolved</span>
                    @endif
                </td>
                <td>
                    {{$dispute->purchase->getSumLocalCurrency()}} {{$dispute -> purchase->getLocalSymbol()}}
                    {{--{{ $dispute -> purchase -> value_sum }} $--}}
                </td>
                <td>
                    {{ $dispute -> timeDiff() }}
                </td>

            </tr>
        @endforeach
        </tbody>
    </table>

    <div class="row">
        <div class="col-md-6 offset-md-3">
            <div class="text-center">
                {{ $allDisputes->links('includes.paginate') }}
            </div>
        </div>
    </div>


@stop