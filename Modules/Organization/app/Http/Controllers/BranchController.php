<?php

namespace Modules\Organization\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Organization\app\Http\Services\BranchService;
use Modules\Organization\app\Http\Requests\Branch\CreateRequest;
use Modules\Organization\app\Http\Requests\Branch\ListingRequest;
use Modules\Organization\app\Http\Requests\Branch\UpdateRequest;

class BranchController extends Controller
{
    use ApiResponser;

    private $branch_service;

    public function __construct(BranchService $branch_service)
    {
        $this->branch_service = $branch_service;
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
                    'prefix' => $search,
                    'mobile_phones' => $search,
                    'email' => $search,
                    'website' => $search,
                    'facebook' => $search
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }
            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }
            if (!empty($validated['state_id'])) {
                $conditions['state_id'] = $validated['state_id'];
            }
            if (!empty($validated['city_id'])) {
                $conditions['city_id'] = $validated['city_id'];
            }
            $with = ['created_by', 'updated_by', 'state', 'city'];
            $res_data = $this->branch_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Branch Lists');
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
            $data = $this->branch_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'branch');
            } else {
                return $this->errorResponse('Branch not found', 404);
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
            $result = $this->branch_service->create($validated);
            return $this->successResponse($result, 200, 'Branch is created successfully');
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
            $data = $this->branch_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->branch_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Branch is updated successfully');
            } else {
                return $this->errorResponse('Branch not found', 404);
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
            $data = $this->branch_service->whereFirst('id', $id);
            if ($data) {
                if ($this->branch_service->delete($id)) {
                    return $this->successResponse([], 200, 'Branch deleted successfully!');
                }
            } else {
                return $this->errorResponse('Branch not found!', 500);
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
            $data = $this->branch_service->whereFirst('id', $id);
            if ($data) {
                $this->branch_service->toggleBranchStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Branch not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
