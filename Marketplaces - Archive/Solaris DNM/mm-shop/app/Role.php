<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class Role extends Model
{
    public $table = 'roles';
    public $timestamps = false;
    public $fillable = ['id', 'name'];

    public const User = 1;
    public const JuniorModerator = 2;
    public const SeniorModerator = 3;
    public const Admin = 4;
    public const SecurityService = 5;
    public const Banned = 6;

    public const UserRoleName = '(Пользователь)';
    public const JunModerRoleName = '(Модератор)';
    public const SenModerRoleName = '(Старший модератор)';
    public const AdminRoleName = '(Администратор)';
    public const SecurityServiceRoleName = 'Служба безопасности';
    public const BannedRoleName = '(Забаненный)';

    /**
     * @param int $role
     * @return null|string
     */
    public static function style(int $role): ?string
    {
        $theme = config('role_colors.theme');
        return config("role_colors.$theme.$role") ?? '';
    }

    /**
     * @param int $roleId
     * @return string
     */
    public static function getName(int $roleId): ?string
    {
        switch ($roleId) {
            case self::JuniorModerator:
                return self::JunModerRoleName;
            case self::SeniorModerator:
                return self::SenModerRoleName;
            case self::SecurityService:
                return self::SecurityServiceRoleName;
            case self::Admin:
                return self::AdminRoleName;
            case self::Banned:
                return self::BannedRoleName;
        }

        return self::UserRoleName;
    }

    /**
     * @return Collection
     */
    public static function getAllRoles(): \Illuminate\Support\Collection
    {
        return collect([
            self::User,
            self::JuniorModerator,
            self::SeniorModerator,
            self::SecurityService,
            self::Admin,
            self::Banned
        ]);
    }

    /**
     * @param $roleId
     * @return int
     */
    public static function getRole($roleId): int
    {
        switch ($roleId) {
            case self::JuniorModerator:
                return self::JuniorModerator;
            case self::SeniorModerator:
                return self::SeniorModerator;
            case self::SecurityService;
                return self::SecurityService;
            case self::Admin:
                return self::Admin;
            case self::Banned:
                return self::Banned;
        }

        return self::User;
    }
}
