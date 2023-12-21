<?php
$prefix = isset($prefix) ? $prefix : '/admin';
?>


<form role="form" method="POST" action="{{ url($prefix . '/users/roles?id=' . $user->id) }}">
    {{ csrf_field() }}

    <div class="form-group">
        <h3>Редактирование ролей пользователя &laquo;<a href="{{ url('/admin/users/edit?id='.$user->id) }}">{{ $user->username }}</a>&raquo;</h3>
    </div>

    <hr class="small"/>

    @if($user->roles->count() > 0)
        <div class="form-group">
            <ul class="list-group">
                @foreach($user->roles as $role)
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        {{ $role->name }}
                        <a class="align-inline-right hint--top" aria-label="Удалить роль {{ $role->name }}" href="{{ url("/admin/users/roles/destroy?user_id=$user->id&role_type_id=$role->id") }}">
                            <span class="badge badge-danger badge-pill">x</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @else
        <div class="form-group">
            <i>Для этого пользователя не задано ролей по умолчанию.</i><br/>
            <small class="help-block">Выберите роль из списка ниже и нажмите кнопку &laquo;Добавить роль&raquo;</small>
        </div>
    @endif

    <div class="form-group">
        <div class="form-group has-feedback">
            <select class="form-control" name="role_type_id">
                @foreach(\App\Role::getAllRoles() as $roleId)
                    <option value="{{ $roleId }}">{{ \App\Role::getName($roleId) }}</option>
                @endforeach
            </select>
            <span class="glyphicon glyphicon-menu-down form-control-feedback"></span>
        </div>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">Добавить роль</button>
        <button type="button" class="btn btn-success" disabled>Роли</button>
    </div>
</form>
