<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Modules\AccessControl\App\Models\Feature;
use Modules\AccessControl\App\Models\Role;
use Modules\Organization\App\Models\Branch;
use Modules\Organization\App\Models\Department;

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
        'salary',
        'sale_incentive',
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

    public function features()
    {
        return $this->belongsToMany(
            Feature::class,
            'feature_user'
        );
    }

    public function hasFeature($featureKey)
    {
        if ($this->role && $this->role->is_super) {
            return true;
        }

        $roleFeature = $this->role
            ->features()
            ->where('key', $featureKey)
            ->exists();

        $userFeature = $this
            ->features()
            ->where('key', $featureKey)
            ->exists();

        return $roleFeature || $userFeature;
    }
}
