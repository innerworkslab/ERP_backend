<?php

namespace Modules\AccessControl\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\AccessControl\app\Http\Requests\Feature\AssignRolesRequest;
use Modules\AccessControl\app\Http\Services\FeatureService;
use Modules\AccessControl\app\Http\Requests\Feature\CreateRequest;
use Modules\AccessControl\app\Http\Requests\Feature\ListingRequest;
use Modules\AccessControl\app\Http\Requests\Feature\UpdateRequest;

class FeatureController extends Controller
{
    use ApiResponser;

    private $feature_service;

    public function __construct(FeatureService $feature_service)
    {
        $this->feature_service = $feature_service;
    }

    public function index(ListingRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();

            $per_page = $validated['per_page'] ?? 20;
            $page = $validated['page'] ?? 1;

            $searches = [];
            $conditions = [];
            $filters = [];

            if (!empty($validated['search'])) {
                $search = $validated['search'];

                $searches = [
                    'name' => $search,
                ];
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            if (!empty($validated['role_id'])) {
                $filters['role_id'] = $validated['role_id'];
            }

            if (!empty($validated['branch_id'])) {
                $filters['branch_id'] = $validated['branch_id'];
            }

            if (!empty($validated['department_id'])) {
                $filters['department_id'] = $validated['department_id'];
            }

            $with = [
                'roles',
                'roles.branch',
                'roles.department',
                'permissions',
            ];

            $res_data = $this->feature_service->getDataWithPagination(
                perPage: $per_page,
                page: $page,
                searches: $searches,
                with: $with,
                conditions: $conditions,
                filters: $filters,
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Feature Lists');

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
            $data = $this->feature_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'feature');
            } else {
                return $this->errorResponse('Feature not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function assignRoles(AssignRolesRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
            $validated = $request->validated();
            $this->feature_service->assignRoles($validated);
            return $this->successResponse([], 200, 'Assigned Roles successfully');
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
            $data = $this->feature_service->whereFirst('id', $id);
            if ($data) {
                $this->feature_service->toggleFeatureStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Feature not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function recommendedFeatures($roleId)
    {
        try {
            if (!is_numeric($roleId)) {
                return $this->errorResponse('Role ID must be an integer!', 422);
            }
            $data = $this->feature_service->recommendedFeatures($roleId);
            return $this->successResponse($data, 200, 'Recommended features for the role');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function otherFeatures($roleId)
    {
        try {
            if (!is_numeric($roleId)) {
                return $this->errorResponse('Role ID must be an integer!', 422);
            }
            $data = $this->feature_service->otherFeatures($roleId);
            return $this->successResponse($data, 200, 'Another features for the role');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
