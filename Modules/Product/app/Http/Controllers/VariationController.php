<?php

namespace Modules\Product\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Product\app\Http\Services\VariationService;
use Modules\Product\app\Http\Requests\Variation\CreateRequest;
use Modules\Product\app\Http\Requests\Variation\ListingRequest;
use Modules\Product\app\Http\Requests\Variation\UpdateRequest;

class VariationController extends Controller
{
    use ApiResponser;

    private $variation_service;

    public function __construct(VariationService $variation_service)
    {
        $this->variation_service = $variation_service;
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
                    'value_data_type' => $search,
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }
            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }
            $with = ['created_by', 'updated_by'];
            $res_data = $this->variation_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Variation Lists');
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
            $data = $this->variation_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'variation');
            } else {
                return $this->errorResponse('Variation not found', 404);
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
            $result = $this->variation_service->create($validated);
            return $this->successResponse($result, 200, 'Variation is created successfully');
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
            $data = $this->variation_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->variation_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Variation is updated successfully');
            } else {
                return $this->errorResponse('Variation not found', 404);
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
            $data = $this->variation_service->whereFirst('id', $id);
            if ($data) {
                if ($this->variation_service->delete($id)) {
                    return $this->successResponse([], 200, 'Variation deleted successfully!');
                }
            } else {
                return $this->errorResponse('Variation not found!', 500);
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
            $data = $this->variation_service->whereFirst('id', $id);
            if ($data) {
                $this->variation_service->toggleVariationStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Variation not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
