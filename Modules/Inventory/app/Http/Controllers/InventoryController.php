<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Requests\Inventory\CreateRequest;
use Modules\Inventory\app\Http\Requests\Inventory\ListingRequest;
use Modules\Inventory\app\Http\Requests\Inventory\UpdateRequest;
use Modules\Inventory\app\Http\Services\InventoryService;

class InventoryController extends Controller
{
    use ApiResponser;

    private $inventory_service;

    public function __construct(InventoryService $inventory_service)
    {
        $this->inventory_service = $inventory_service;
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
            $searches = [];
            $whereHas = [];

            if (!empty($validated['search'])) {
                $searches = [
                    'name' => $validated['search'],
                ];
            }

            if (!empty($validated['branch_id'])) {
                $branchId = (int) $validated['branch_id'];
                $whereHas['branches'] = function ($query) use ($branchId) {
                    $query->where('branches.id', $branchId);
                };
            }

            $res_data = $this->inventory_service->getDataWithPagination(
                perPage: $per_page,
                page: $page,
                searches: $searches,
                conditions: $conditions,
                with: ['branches'],
                whereHas: !empty($whereHas) ? $whereHas : null
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Inventory lists');
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

            $data = $this->inventory_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'Inventory');
            }

            return $this->errorResponse('Inventory not found', 404);
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
            $result = $this->inventory_service->create($validated);
            return $this->successResponse($result, 200, 'Inventory is created successfully');
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

            $data = $this->inventory_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Inventory not found', 404);
            }

            $validated = $request->validated();
            $result = $this->inventory_service->update((int) $id, $validated);
            return $this->successResponse($result, 200, 'Inventory is updated successfully');
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

            $data = $this->inventory_service->whereFirst('id', (int) $id);
            if (!$data) {
                return $this->errorResponse('Inventory not found', 404);
            }

            $this->inventory_service->delete((int) $id);
            return $this->successResponse([], 200, 'Inventory deleted successfully!');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function toggleActive($id, $branchId)
    {
        try {
            if (!is_numeric($id) || !is_numeric($branchId)) {
                return $this->errorResponse('ID and branchId must be integers!', 422);
            }

            $updated = $this->inventory_service->toggleBranchStatus((int) $id, (int) $branchId);
            if (!$updated) {
                return $this->errorResponse('Branch inventory not found', 404);
            }

            return $this->successResponse([], 200, 'Branch inventory status toggled successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
