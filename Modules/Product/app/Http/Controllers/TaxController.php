<?php

namespace Modules\Product\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Product\app\Http\Requests\Tax\CreateRequest;
use Modules\Product\app\Http\Requests\Tax\ListingRequest;
use Modules\Product\app\Http\Requests\Tax\UpdateRequest;
use Modules\Product\app\Http\Services\TaxService;

class TaxController extends Controller
{
    use ApiResponser;

    protected $tax_service;

    public function __construct(TaxService $tax_service)
    {
        $this->tax_service = $tax_service;
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
                    'category' => $search,
                    'code' => $search,
                    'type' => $search,
                ];
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            if (!empty($validated['type'])) {
                $conditions['type'] = $validated['type'];
            }

            $with = ['created_by', 'updated_by'];
            $res_data = $this->tax_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Tax Lists');
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
            $data = $this->tax_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'tax');
            } else {
                return $this->errorResponse('Tax not found', 404);
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
            $result = $this->tax_service->create($validated);
            return $this->successResponse($result, 200, 'Tax is created successfully');
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
            $data = $this->tax_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->tax_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Tax is updated successfully');
            } else {
                return $this->errorResponse('Tax not found', 404);
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
            $data = $this->tax_service->whereFirst('id', $id);
            if ($data) {
                $this->tax_service->toggleTaxStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Tax not found', 404);
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
            $data = $this->tax_service->whereFirst('id', $id);
            if ($data) {
                if ($this->tax_service->delete($id)) {
                    return $this->successResponse([], 200, 'Tax deleted successfully!');
                }
            } else {
                return $this->errorResponse('Tax not found!', 500);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
