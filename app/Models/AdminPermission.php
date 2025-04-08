<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminPermission extends Model
{
    use HasFactory;

    protected $fillable = ['admin_id', 'permission_id', 'list', 'create', 'delete', 'update'];

    public static function addAndInitPermission(string $name, string $title)
    {
        $permission = Permission::query()->firstOrCreate([
            'name' => $name,
            'title' => $title,
        ]);
        self::initPermissionForAll($permission->id, $name);
    }

    /**
     * Инициировать право для всех админов
     * @param int $permissionId
     * @param string $permissionName
     * @return void
     */
    public static function initPermissionForAll(int $permissionId, string $permissionName)
    {
        $adminIds = Admin::all()->pluck('id');
        foreach ($adminIds as $adminId) {
            AdminPermission::initPermission($adminId, $permissionId, $permissionName);
        }
    }

    /**
     * Дать указанному админу указанное право на просмотр
     * @param $admin_id
     * @param $permission_id
     * @param $permission_name
     * @return void
     */
    public static function initPermission($admin_id, $permission_id, $permission_name)
    {
        if ($permission_name !== 'settings')
            self::firstOrCreate([
                'admin_id' => $admin_id,
                'permission_id' => $permission_id
            ], [
                'list' => 1,
                'create' => 0,
                'update' => 0,
                'delete' => 0
            ]);
    }

    public static function removePermissions(array $names)
    {
        Permission::query()->whereIn('name', $names)->delete();
    }
}
