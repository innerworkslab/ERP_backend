<?php

namespace Modules\Staff\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Staff\app\Http\Requests\Staff\ChangePasswordRequest;
use Modules\Staff\app\Http\Requests\Staff\CreateRequest;
use Modules\Staff\app\Http\Requests\Staff\ListingRequest;
use Modules\Staff\app\Http\Requests\Staff\UpdateRequest;
use Modules\Staff\app\Http\Services\StaffService;

class StaffController extends Controller
{
    use ApiResponser;

    private $staff_service;

    public function __construct(StaffService $staff_service)
    {
        $this->staff_service = $staff_service;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(ListingRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $per_page = array_key_exists('per_page', $validated) ? $validated['per_page'] : 20;
            $page = array_key_exists('page', $validated) ? $validated['page'] : 1;
            $searches = [];
            $status = null;

            if (!empty($validated['search'])) {
                $search = $validated['search'];

                $searches = [
                    'name' => $search,
                    'email' => $search,
                    'phone_number' => $search,
                ];

                if (in_array(strtolower($search), ['active', 'inactive'])) {
                    $searches = [];
                    $status = strtolower($search);
                }
            }

            $with = [
                'role',
                'branch',
                'department',
                'staffPersonalInformation',
                'staffEmploymentInformation',
                'staffBankingInformation',
                'staffAuthorizedFeatures',
                'staffAuthorizedFeatures.feature',
                'staffAuthorizedFeatures.assignedBy',
            ];

            $res_data = $this->staff_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with);

            return $this->paginatedSuccessResponse($res_data, 200, 'Staff Lists');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CreateRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
            $validated = $request->validated();
            $result = $this->staff_service->create($validated);

            return $this->successResponse($result, 201, 'Staff is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }
            $data = $this->staff_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'staff');
            }

            return $this->errorResponse('Staff not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRequest $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
            $validated = $request->validated();
            $result = $this->staff_service->update((int) $id, $validated);
            if ($result) {
                return $this->successResponse($result, 200, 'Staff is updated successfully');
            }

            return $this->errorResponse('Staff not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $deleted = $this->staff_service->delete((int) $id);

            if (!$deleted) {
                return $this->errorResponse('Staff not found.', 404);
            }

            return $this->successResponse([], 200, 'Staff deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function changePassword(ChangePasswordRequest $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $result = $this->staff_service->changePassword((int) $id, $validated['old_password'], $validated['password']);

            if ($result === null) {
                return $this->errorResponse('Staff not found', 404);
            }

            if ($result === false) {
                return $this->errorResponse('Old password is incorrect', 422);
            }

            return $this->successResponse($result, 200, 'Staff password changed successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function toggleActive($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $data = $this->staff_service->find((int) $id);
            if ($data) {
                $this->staff_service->toggleStaffStatus($data);

                return $this->successResponse([], 200, 'Toggle status successfully');
            }

            return $this->errorResponse('Staff not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
