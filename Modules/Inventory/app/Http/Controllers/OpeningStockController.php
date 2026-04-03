<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Requests\OpeningStock\CreateRequest;
use Modules\Inventory\app\Http\Requests\OpeningStock\ListingRequest;
use Modules\Inventory\app\Http\Requests\OpeningStock\UpdateRequest;
use Modules\Inventory\app\Http\Services\OpeningStockService;

class OpeningStockController extends Controller
{
    use ApiResponser;

    private $opening_stock_service;

    public function __construct(OpeningStockService $opening_stock_service)
    {
        $this->opening_stock_service = $opening_stock_service;
    }

    public function openingStocks(ListingRequest $request)
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
            $searches = [];
            $whereHas = [];

            if (!empty($validated['search'])) {
                $searches = [
                    'voucher_no' => $validated['search'],
                    'remarks' => $validated['search'],
                ];
            }

            if (!empty($validated['voucher_no'])) {
                $searches['voucher_no'] = $validated['voucher_no'];
            }

            if (!empty($validated['inventory_id'])) {
                $conditions['inventory_id'] = (int) $validated['inventory_id'];
            }

            if (!empty($validated['inventory'])) {
                $whereHas['inventory'] = function ($query) use ($validated) {
                    $query->where('name', 'LIKE', '%' . $validated['inventory'] . '%');
                };
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            if (!empty($validated['voucher_date'])) {
                $conditions['voucher_date_from'] = $validated['voucher_date'];
                $conditions['voucher_date_to'] = $validated['voucher_date'];
            }

            if (!empty($validated['voucher_date_from'])) {
                $conditions['voucher_date_from'] = $validated['voucher_date_from'];
            }

            if (!empty($validated['voucher_date_to'])) {
                $conditions['voucher_date_to'] = $validated['voucher_date_to'];
            }

            $res_data = $this->opening_stock_service->getDataWithPagination(
                perPage: $per_page,
                page: $page,
                searches: $searches,
                conditions: $conditions,
                whereHas: $whereHas
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Opening stock lists');
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
            $result = $this->opening_stock_service->create($validated);
            return $this->successResponse($result, 200, 'Opening stock is created successfully');
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

            $data = $this->opening_stock_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'Opening stock');
            }

            return $this->errorResponse('Opening stock not found', 404);
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

            $data = $this->opening_stock_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Opening stock not found', 404);
            }

            if ($data->status === 'confirmed') {
                return $this->errorResponse('Confirmed opening stock cannot be updated', 422);
            }

            $validated = $request->validated();
            $result = $this->opening_stock_service->update((int) $id, $validated);
            return $this->successResponse($result, 200, 'Opening stock is updated successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
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

            $data = $this->opening_stock_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Opening stock not found', 404);
            }

            if ($data->status === 'confirmed') {
                return $this->errorResponse('Confirmed opening stock cannot be deleted', 422);
            }

            $this->opening_stock_service->delete((int) $id);
            return $this->successResponse([], 200, 'Opening stock deleted successfully!');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function confirm($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $data = $this->opening_stock_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Opening stock not found', 404);
            }

            if ($data->status === 'confirmed') {
                return $this->errorResponse('Opening stock already confirmed', 422);
            }

            $result = $this->opening_stock_service->confirm((int) $id);
            return $this->successResponse($result, 200, 'Opening stock confirmed successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
