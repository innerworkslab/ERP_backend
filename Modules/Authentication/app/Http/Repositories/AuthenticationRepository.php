<?php

namespace Modules\Authentication\app\Http\Repositories;


use App\Models\User;
use Modules\Authentication\app\Http\Repositories\BaseRepo;


class AuthenticationRepository extends BaseRepo
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function generateAccessToken(User $user)
    {
        $user->tokens()->delete();
        return $user->createToken($user->email . '_AccessToken', [''], now()->addDays(2))->plainTextToken;
    }

    public function generateRefreshToken(User $user)
    {
        return $user->createToken($user->email . '_RefreshToken', [''], now()->addWeek())->plainTextToken;
    }
}
