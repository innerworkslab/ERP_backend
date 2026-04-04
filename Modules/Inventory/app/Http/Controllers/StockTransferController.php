<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Requests\StockTransfer\CreateRequest;
use Modules\Inventory\app\Http\Requests\StockTransfer\ListingRequest;
use Modules\Inventory\app\Http\Requests\StockTransfer\UpdateRequest;
use Modules\Inventory\app\Http\Services\StockTransferService;

class StockTransferController extends Controller
{
    use ApiResponser;

    private $stock_transfer_service;

    public function __construct(StockTransferService $stock_transfer_service)
    {
        $this->stock_transfer_service = $stock_transfer_service;
    }

    public function stockTransfers(ListingRequest $request)
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
                    'reference_id' => $validated['search'],
                    'remarks' => $validated['search'],
                ];
            }

            if (!empty($validated['reference_id'])) {
                $searches['reference_id'] = $validated['reference_id'];
            }

            if (!empty($validated['source_inventory_id'])) {
                $conditions['source_inventory_id'] = (int) $validated['source_inventory_id'];
            }

            if (!empty($validated['target_inventory_id'])) {
                $conditions['target_inventory_id'] = (int) $validated['target_inventory_id'];
            }

            if (!empty($validated['source_inventory'])) {
                $whereHas['source_inventory'] = function ($query) use ($validated) {
                    $query->where('name', 'LIKE', '%' . $validated['source_inventory'] . '%');
                };
            }

            if (!empty($validated['target_inventory'])) {
                $whereHas['target_inventory'] = function ($query) use ($validated) {
                    $query->where('name', 'LIKE', '%' . $validated['target_inventory'] . '%');
                };
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            if (!empty($validated['transfer_date'])) {
                $conditions['transfer_date_from'] = $validated['transfer_date'];
                $conditions['transfer_date_to'] = $validated['transfer_date'];
            }

            if (!empty($validated['transfer_date_from'])) {
                $conditions['transfer_date_from'] = $validated['transfer_date_from'];
            }

            if (!empty($validated['transfer_date_to'])) {
                $conditions['transfer_date_to'] = $validated['transfer_date_to'];
            }

            $res_data = $this->stock_transfer_service->getDataWithPagination(
                perPage: $per_page,
                page: $page,
                searches: $searches,
                conditions: $conditions,
                whereHas: !empty($whereHas) ? $whereHas : null
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Stock transfer lists');
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
            $result = $this->stock_transfer_service->create($validated);
            return $this->successResponse($result, 200, 'Stock transfer is created successfully');
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

            $data = $this->stock_transfer_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'Stock transfer');
            }

            return $this->errorResponse('Stock transfer not found', 404);
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

            $data = $this->stock_transfer_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Stock transfer not found', 404);
            }

            if ($data->status !== 'pending') {
                return $this->errorResponse('Only pending stock transfer can be updated', 422);
            }

            $validated = $request->validated();
            $result = $this->stock_transfer_service->update((int) $id, $validated);
            return $this->successResponse($result, 200, 'Stock transfer is updated successfully');
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

            $data = $this->stock_transfer_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Stock transfer not found', 404);
            }

            if ($data->status !== 'pending') {
                return $this->errorResponse('Only pending stock transfer can be deleted', 422);
            }

            $this->stock_transfer_service->delete((int) $id);
            return $this->successResponse([], 200, 'Stock transfer deleted successfully!');
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

            $data = $this->stock_transfer_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Stock transfer not found', 404);
            }

            if ($data->status === 'confirmed') {
                return $this->errorResponse('Stock transfer already confirmed', 422);
            }

            if ($data->status === 'rejected') {
                return $this->errorResponse('Rejected stock transfer cannot be confirmed', 422);
            }

            $result = $this->stock_transfer_service->confirm((int) $id);
            return $this->successResponse($result, 200, 'Stock transfer confirmed successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function reject($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $data = $this->stock_transfer_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Stock transfer not found', 404);
            }

            if ($data->status === 'rejected') {
                return $this->errorResponse('Stock transfer already rejected', 422);
            }

            if ($data->status === 'confirmed') {
                return $this->errorResponse('Confirmed stock transfer cannot be rejected', 422);
            }

            $result = $this->stock_transfer_service->reject((int) $id);
            return $this->successResponse($result, 200, 'Stock transfer rejected successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
