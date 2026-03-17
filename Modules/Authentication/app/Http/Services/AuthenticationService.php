<?php

namespace Modules\Authentication\app\Http\Services;

use Exception;
use Modules\Authentication\app\Http\Repositories\AuthenticationRepository;

class AuthenticationService
{
    protected $authentication_repository;

    public function __construct(AuthenticationRepository $authentication_repository)
    {
        $this->authentication_repository = $authentication_repository;
    }

    public function generateAccessToken($user)
    {
        try {
            $result = $this->authentication_repository->generateAccessToken($user);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to create access token: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->authentication_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find user with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }

    public function logout()
    {
        try {
            $currentAccessToken = auth()->user()->currentAccessToken();

            $currentAccessToken->delete();

            auth()->user()->tokens()->where(
                'id',
                $currentAccessToken->id
            )->delete();
            return true;
        } catch (Exception $e) {
            logger()->error('Error : Failed to user logout: ' . $e->getMessage());
            throw $e;
        }
    }
}
