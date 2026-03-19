<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

use Modules\Staff\app\Models\StaffBankingInformation;
use Modules\Staff\app\Models\StaffEmploymentInformation;
use Modules\Staff\app\Models\StaffPersonalInformation;
use Modules\AccessControl\app\Models\Feature;
use Modules\AccessControl\app\Models\Role;
use Modules\AccessControl\app\Models\Permission;
use Modules\Organization\app\Models\Branch;
use Modules\Organization\app\Models\Department;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone_number',
        'password',
        'role_id',
        'branch_id',
        'department_id',
        'status'
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function toArray()
    {
        $attributes = parent::toArray();
        if (array_key_exists('created_at', $attributes)) {
            $attributes['created_at'] = Carbon::parse($attributes['created_at'])->format('Y-m-d H:i:s');
        }
        if (array_key_exists('updated_at', $attributes)) {
            $attributes['updated_at'] = Carbon::parse($attributes['updated_at'])->format('Y-m-d H:i:s');
        }
        return $attributes;
    }

    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(
            Permission::class,
            'user_permission'
        );
    }

    public function hasPermission($permissionKey)
    {
        // if ($this->role && $this->role->is_super) {
        //     return true;
        // }

        // $roleFeature = $this->role
        //     ->features()
        //     ->where('key', $featureKey)
        //     ->exists();

        $userPermission = $this
            ->permissions()
            ->where('key', $permissionKey)
            ->exists();

        return $userPermission;
    }

    public function staffPersonalInformation()
    {
        return $this->hasOne(StaffPersonalInformation::class);
    }

    public function staffEmploymentInformation()
    {
        return $this->hasOne(StaffEmploymentInformation::class);
    }

    public function staffBankingInformation()
    {
        return $this->hasOne(StaffBankingInformation::class);
    }
    
}
