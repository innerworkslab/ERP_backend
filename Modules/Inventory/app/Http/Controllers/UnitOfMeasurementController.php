<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Services\UnitOfMeasurementService;
use Modules\Inventory\app\Http\Requests\UOM\CreateRequest;
use Modules\Inventory\app\Http\Requests\UOM\ListingRequest;
use Modules\Inventory\app\Http\Requests\UOM\UpdateRequest;

class UnitOfMeasurementController extends Controller
{
    use ApiResponser;

    private $uom_service;

    public function __construct(UnitOfMeasurementService $uom_service)
    {
        $this->uom_service = $uom_service;
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
            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }
            $with = ['created_by', 'updated_by'];
            $res_data = $this->uom_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'UOM Lists');
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
            $data = $this->uom_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'uom');
            } else {
                return $this->errorResponse('UOM not found', 404);
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
            $result = $this->uom_service->create($validated);
            return $this->successResponse($result, 200, 'UOM is created successfully');
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
            $data = $this->uom_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->uom_service->update($id, $validated);
                return $this->successResponse($result, 200, 'UOM is updated successfully');
            } else {
                return $this->errorResponse('UOM not found', 404);
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
            $data = $this->uom_service->whereFirst('id', $id);
            if ($data) {
                if ($this->uom_service->delete($id)) {
                    return $this->successResponse([], 200, 'UOM deleted successfully!');
                }
            } else {
                return $this->errorResponse('UOM not found!', 500);
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
            $data = $this->uom_service->whereFirst('id', $id);
            if ($data) {
                $this->uom_service->toggleUOMStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('UOM not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
