<?php
use App\Role;

/*
 * цветовая палитра по умолчанию
 * значение может быть null, hex rgb или css классами до 64 символов
 */

return [
    'theme' => 'default',
    'default' => [
        Role::User => null,
        Role::JuniorModerator => '#400030',
        Role::SeniorModerator => '#400030',
        Role::Admin => 'text-danger',
        Role::SecurityService => 'text-danger',
        Role::Banned => 'text-muted',
    ],
];