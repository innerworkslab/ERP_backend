<?php

namespace Modules\AccessControl\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\AccessControl\app\Http\Services\RoleService;
use Modules\AccessControl\app\Http\Requests\Role\CreateRequest;
use Modules\AccessControl\app\Http\Requests\Role\ListingRequest;
use Modules\AccessControl\app\Http\Requests\Role\UpdateRequest;

class RoleController extends Controller
{
    use ApiResponser;

    private $role_service;

    public function __construct(RoleService $role_service)
    {
        $this->role_service = $role_service;
    }

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
            $conditions = [];
            $status = null;

            if (!empty($validated['search'])) {
                $search = $validated['search'];

                $searches = [
                    'name' => $search,
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            if (!empty($validated['parent_role_id'])) {
                $conditions['parent_role_id'] = $validated['parent_role_id'];
            }

            if (!empty($validated['branch_id'])) {
                $conditions['branch_id'] = $validated['branch_id'];
            }

            if (!empty($validated['department_id'])) {
                $conditions['department_id'] = $validated['department_id'];
            }

            $with = ['parentRole', 'children', 'branch', 'department', 'features', 'created_by', 'updated_by'];
            $res_data = $this->role_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Role Lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function findOrFail($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }
            $data = $this->role_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'role');
            } else {
                return $this->errorResponse('Role not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function create(CreateRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
            $validated = $request->validated();
            $result = $this->role_service->create($validated);
            return $this->successResponse($result, 200, 'Role is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
            $validated = $request->validated();
            $data = $this->role_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->role_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Role is updated successfully');
            } else {
                return $this->errorResponse('Role not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }

    }

    public function delete($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }
            $data = $this->role_service->whereFirst('id', $id);
            if ($data) {
                if ($this->role_service->delete($id)) {
                    return $this->successResponse([], 200, 'Role deleted successfully!');
                }
            } else {
                return $this->errorResponse('Role not found!', 500);
            }
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
            $data = $this->role_service->whereFirst('id', $id);
            if ($data) {
                $this->role_service->toggleRoleStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Role not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
