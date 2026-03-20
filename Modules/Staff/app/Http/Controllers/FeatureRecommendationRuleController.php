<?php

namespace Modules\Staff\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Staff\app\Http\Requests\FeatureRecommendationRule\CreateRequest;
use Modules\Staff\app\Http\Requests\FeatureRecommendationRule\ListingRequest;
use Modules\Staff\app\Http\Requests\FeatureRecommendationRule\UpdateRequest;
use Modules\Staff\app\Http\Services\FeatureRecommendationRuleService;

class FeatureRecommendationRuleController extends Controller
{
    use ApiResponser;

    private $feature_recommendation_rule_service;

    public function __construct(FeatureRecommendationRuleService $feature_recommendation_rule_service)
    {
        $this->feature_recommendation_rule_service = $feature_recommendation_rule_service;
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

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $searches = [
                    'status' => $search,
                ];
            }

            if (array_key_exists('status', $validated)) {
                $conditions['status'] = $validated['status'];
            }

            if (array_key_exists('role_id', $validated)) {
                $conditions['role_id'] = $validated['role_id'];
            }

            if (array_key_exists('department_id', $validated)) {
                $conditions['department_id'] = $validated['department_id'];
            }

            if (array_key_exists('feature_id', $validated)) {
                $conditions['feature_id'] = $validated['feature_id'];
            }

            if (array_key_exists('is_default_recommended', $validated)) {
                $conditions['is_default_recommended'] = $validated['is_default_recommended'];
            }

            $with = ['role', 'department', 'feature'];
            $res_data = $this->feature_recommendation_rule_service->getDataWithPagination(
                perPage: $per_page,
                page: $page,
                searches: $searches,
                with: $with,
                conditions: $conditions,
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Feature Recommendation Rule Lists');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $result = $this->feature_recommendation_rule_service->create($validated);

            return $this->successResponse($result, 201, 'Feature recommendation rule is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $data = $this->feature_recommendation_rule_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'feature recommendation rule');
            }

            return $this->errorResponse('Feature recommendation rule not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

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
            $result = $this->feature_recommendation_rule_service->update((int) $id, $validated);
            if ($result) {
                return $this->successResponse($result, 200, 'Feature recommendation rule is updated successfully');
            }

            return $this->errorResponse('Feature recommendation rule not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $deleted = $this->feature_recommendation_rule_service->delete((int) $id);
            if (!$deleted) {
                return $this->errorResponse('Feature recommendation rule not found.', 404);
            }

            return $this->successResponse([], 200, 'Feature recommendation rule deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
