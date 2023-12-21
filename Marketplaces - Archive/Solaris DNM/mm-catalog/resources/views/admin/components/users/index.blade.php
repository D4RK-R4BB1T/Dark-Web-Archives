@php
$prefix = isset($prefix) ? $prefix : '/admin';
@endphp
{{--<div>--}}
    {{--<a href="{{ $prefix }}/users/add" class="btn btn-success"><span class="glyphicon glyphicon-plus"></span> </a>--}}
{{--</div>--}}

{{--<p></p>--}}
@include('users.components.component-search')

<div>
    @if ($users->total() > $users->perPage())
        <hr class="small" />
        <div>
            {{ $users->appends(request()->input())->links() }}
        </div>
        <hr class="small" />
    @endif
</div>

<div class="list-group">
    @foreach ($users as $user)
        {{--<a href="{{ $prefix }}/users/edit?id={{ $user->id }}" class="list-group-item" id="user_id_{{ $user->id }}">--}}
        <a href="{{ $prefix }}/users/{{ $action }}?id={{ $user->id }}" class="list-group-item" id="user_id_{{ $user->id }}">
            <h4 class="list-group-item-heading">{{ $user->username }}</h4>
            <p class="list-group-item-text">
                #{{ $user->id }}
                {{--<b>
                @if($user->role === 'admin')
                    {{ __('admin.Admin') }};
                @elseif($user->role === 'user')
                    {{ __('admin.User') }};
                @else
                    {{ __('admin.Role unknown', ['role' => $user->role]) }};
                @endif
                </b>

                @if($user->active)
                    {{ __('admin.Active') }};
                @else
                    {{ __('admin.Not active') }};
                @endif--}}
            </p>
        </a>
        {{--<a href="{{ $prefix }}/delete_{{ $category }}?id={{ $user->id }}" class="list-group-item"><span class="glyphicon glyphicon-minus"> delete</span></a>--}}
    @endforeach
</div>

<div>
    @if ($users->total() > $users->perPage())
        <hr class="small" />
        <div>
            {{ $users->appends(request()->input())->links() }}
        </div>
        <hr class="small" />
    @endif
</div>