<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Services\UnitOfMeasurementConversionService;
use Modules\Inventory\app\Http\Requests\UOMConversion\CreateRequest;
use Modules\Inventory\app\Http\Requests\UOMConversion\ListingRequest;
use Modules\Inventory\app\Http\Requests\UOMConversion\UpdateRequest;

class UnitOfMeasurementConversionController extends Controller
{
    use ApiResponser;

    private $uom_conversion_service;

    public function __construct(UnitOfMeasurementConversionService $uom_conversion_service)
    {
        $this->uom_conversion_service = $uom_conversion_service;
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
            $conditions = [];
            $status = null;

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }
            if (!empty($validated['base_unit_id'])) {
                $conditions['base_unit_id'] = $validated['base_unit_id'];
            }
            if (!empty($validated['conversion_unit_id'])) {
                $conditions['conversion_unit_id'] = $validated['conversion_unit_id'];
            }
            $with = ['created_by', 'updated_by', 'baseUnit', 'conversionUnit'];
            $res_data = $this->uom_conversion_service->getDataWithPagination($per_page, $page, status: $status, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'UOM Conversion Lists');
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
            $data = $this->uom_conversion_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'uom conversion');
            } else {
                return $this->errorResponse('UOM Conversion not found', 404);
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
            $result = $this->uom_conversion_service->create($validated);
            return $this->successResponse($result, 200, 'UOM Conversion is created successfully');
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
            $data = $this->uom_conversion_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->uom_conversion_service->update($id, $validated);
                return $this->successResponse($result, 200, 'UOM Conversion is updated successfully');
            } else {
                return $this->errorResponse('UOM Conversion not found', 404);
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
            $data = $this->uom_conversion_service->whereFirst('id', $id);
            if ($data) {
                if ($this->uom_conversion_service->delete($id)) {
                    return $this->successResponse([], 200, 'UOM Conversion deleted successfully!');
                }
            } else {
                return $this->errorResponse('UOM Conversion not found!', 500);
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
            $data = $this->uom_conversion_service->whereFirst('id', $id);
            if ($data) {
                $this->uom_conversion_service->toggleUOMConversionStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('UOM Conversion not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
