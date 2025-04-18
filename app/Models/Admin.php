<?php

namespace App\Models;

use Backpack\CRUD\app\Exceptions\AccessDeniedException;
use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

/**
 * Админ
 * @property int $id
 * @property \DateTime $created_at
 * @property \DateTime $updated_at
 * @property ?string $name
 * @property string $email
 * @property Department $department
 */
class Admin extends Authenticatable
{
    use CrudTrait, HasFactory, Notifiable;

    const PERMISSION_LIST = 'list';
    const PERMISSION_CREATE = 'create';
    const PERMISSION_DELETE = 'delete';
    const PERMISSION_UPDATE = 'update';

    /*
    |--------------------------------------------------------------------------
    | GLOBAL VARIABLES
    |--------------------------------------------------------------------------
    */

    protected $table = 'admins';
    // protected $primaryKey = 'id';
    // public $timestamps = false;
    protected $guarded = ['id'];
    protected $fillable = [
        'name', 'email', 'password', 'is_root', 'department_id',
    ];
    // protected $hidden = [];
    // protected $dates = [];

    public static function boot(): void
    {
        parent::boot();
        static::created(function ($admin) {
            $permissions = Permission::all();
            foreach ($permissions as $permission) {
                AdminPermission::initPermission($admin->id, $permission['id'], $permission['name']);
            }
        });
    }

    public function department():BelongsTo{
        return $this->belongsTo(Department::class, 'department_id', 'id');
    }

    public function departments(): BelongsToMany {
        return $this->belongsToMany(Department::class, 'admin_department', 'admin_id', 'department_id');
    }

    public function getDepartmentsStrVal()
    {
        $arNames = [];
        foreach ($this->departments as $dep){
            $arNames[] = $dep->name;
        }

        return join(', ', $arNames);
    }
    /*
    |--------------------------------------------------------------------------
    | FUNCTIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | RELATIONS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | SCOPES
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | ACCESSORS
    |--------------------------------------------------------------------------
    */

    /*
    |--------------------------------------------------------------------------
    | MUTATORS
    |--------------------------------------------------------------------------
    */

    /**
     * Проверить у текущего авторизованного backpack-юзера доступность указанного действия на указанной странице.
     * В случае отсутствия прав будет вызвано соответствующее исключение, если параметр $return == false,
     * иначе будет возвращено булево значение.
     *
     * Если передано на проверку несколько действий, проверка будет успешна только если ВСЕ права будут у текущего юзера.
     *
     * @param string $page - страница или раздел
     * @param string|array $actions - действие или массив действий (варианты: 'list', 'create', 'delete', 'update'). Смотри self::PERMISSION_*
     * @param bool $return - если true, возвращает bool значение, иначе вызывает исключение
     * @throws AccessDeniedException
     */
    public static function checkAccess(string $page, $actions, bool $return = false): bool
    {
        if (backpack_user()->is_root) return true; // Всё ок, это рут, ему всё можно, жук

        if (is_array($actions)) {
            foreach ($actions as $action) {
                if (!self::checkAccess($page, $action, $return)) // Если хоть одно действие нельзя, то всё нельзя
                    return false;
            }
            return true;
        }

        /** @var self $user */
        $user = backpack_user();
        if ($user->denyAccessByType($page, $actions)) {
            if ($return)
                return false;

            throw new AccessDeniedException(trans('backpack::crud.unauthorized_access', ['access' => $actions]));
        }
        return true;
    }

    public function denyAccessByType($page, $type)
    {
        if (backpack_user()->is_root) return false;

        $permission = $this->belongsToMany(Permission::class, 'admin_permissions', 'admin_id', 'permission_id')
            ->withPivot($type)
            ->where('name', $page)->first();

        return !$permission || !$permission->pivot[$type];
    }

    public function setPasswordAttribute($value)
    {
        if (!$value) return; // пустой пароль задать нельзя
        $this->attributes['password'] = self::isHashedPass($value) ? $value : Hash::make($value);
    }

    public static function isHashedPass(string $pass): bool
    {
        return isset(Hash::info($pass)['algo']);
    }

    public function permissions(): ?BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'admin_permissions', 'admin_id', 'permission_id')->withPivot('list', 'create', 'delete', 'update');
    }

    public function denyAccess($page): array
    {
        if (backpack_user()->is_root) return [];

        $types = ['list', 'create', 'delete', 'update'];

        $permission = $this->belongsToMany(Permission::class, 'admin_permissions', 'admin_id', 'permission_id')
            ->withPivot('list', 'create', 'delete', 'update')
            ->where('name', $page)->first();

        if ($permission) {
            foreach ($types as $key => $type) {
                if ($permission->pivot[$type]) unset($types[$key]);
            }
        }

        return $types;
    }

    public function denyAccessMenu()
    {
        if (backpack_user()->is_root) return 'all';

        $pages = [];
        $permissions = $this->belongsToMany(Permission::class, 'admin_permissions', 'admin_id', 'permission_id')
            ->withPivot('list')
            ->get();

        if ($permissions) {
            foreach ($permissions as $permission) {
                if ($permission->pivot['list']) $pages[] = $permission['name'];
            }
        }

        return $pages;
    }
}
