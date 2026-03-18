<?php

namespace Modules\Organization\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Organization\app\Http\Requests\Department\DepartmentsBySelectedBranchRequest;
use Modules\Organization\app\Http\Services\DepartmentService;
use Modules\Organization\app\Http\Requests\Department\CreateRequest;
use Modules\Organization\app\Http\Requests\Department\ListingRequest;
use Modules\Organization\app\Http\Requests\Department\UpdateRequest;

class DepartmentController extends Controller
{
    use ApiResponser;

    private $department_service;

    public function __construct(DepartmentService $department_service)
    {
        $this->department_service = $department_service;
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
                    'code' => $search,
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }
            $with = ['branch'];

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            $res_data = $this->department_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Department Lists');
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
            $data = $this->department_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'department');
            } else {
                return $this->errorResponse('Department not found', 404);
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
            $result = $this->department_service->create($validated);
            return $this->successResponse($result, 200, 'Department is created successfully');
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
            $data = $this->department_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->department_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Department is updated successfully');
            } else {
                return $this->errorResponse('Department not found', 404);
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
            $data = $this->department_service->whereFirst('id', $id);
            if ($data) {
                if ($this->department_service->delete($id)) {
                    return $this->successResponse([], 200, 'Department deleted successfully!');
                }
            } else {
                return $this->errorResponse('Department not found!', 500);
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
            $data = $this->department_service->whereFirst('id', $id);
            if ($data) {
                $this->department_service->toggleDepartmentStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Department not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function departmentsBySelectedBranch(DepartmentsBySelectedBranchRequest $request)
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
                    'code' => $search,
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }
            $branch_id = $validated['branch_id'];
            $whereHas = [
                'branch' => function ($q) use ($branch_id) {
                    $q->where('id', $branch_id);
                }
            ];
            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }
            $res_data = $this->department_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, whereHas: $whereHas, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Department Lists By Selected Branch');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
