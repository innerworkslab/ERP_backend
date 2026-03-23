<?php

namespace Modules\Authentication\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Modules\Authentication\app\Http\Requests\LoginRequest;
use Modules\Authentication\app\Http\Services\AuthenticationService;

class AuthenticationController extends Controller
{
    use ApiResponser;

    private $authentication_service;

    public function __construct(AuthenticationService $authentication_service)
    {
        $this->authentication_service = $authentication_service;
    }
    public function login(LoginRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $user = $this->authentication_service->whereFirst('phone_number', $validated['phone_number']);
            if (!$user) {
                return $this->errorResponse("Phone number does not exist.", 401);
            }
            if ($user->status !== 'active') {
                return $this->errorResponse('Account is inactive. Please contact admin.', 403);
            }
            if (!Hash::check($validated['password'], $user->password)) {
                return $this->errorResponse("Wrong Password. Try again!", 401);
            }
            $token = $this->authentication_service->generateAccessToken($user);
            return $this->successResponse([
                'token_type' => 'bearer',
                'accessToken' => $token,
                'user' => $user
            ], 200, 'Logged in successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function logout()
    {
        try {
            $this->authentication_service->logout();
            return $this->successResponse([], 200, 'Successfully logged out');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
