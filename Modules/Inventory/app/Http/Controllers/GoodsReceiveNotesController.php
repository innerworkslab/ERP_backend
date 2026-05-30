<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Requests\GoodsReceiveNote\CreateRequest;
use Modules\Inventory\app\Http\Requests\GoodsReceiveNote\ListingRequest;
use Modules\Inventory\app\Http\Requests\GoodsReceiveNote\UpdateRequest;
use Modules\Inventory\app\Http\Services\GoodsReceiveNoteService;

class GoodsReceiveNotesController extends Controller
{
    use ApiResponser;

    public function __construct(private GoodsReceiveNoteService $service)
    {
    }

    public function index(ListingRequest $request)
    {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;
        $searches = [];
        $conditions = [];

        if (!empty($validated['search'])) {
            $searches = ['grn_no' => $validated['search'], 'remarks' => $validated['search']];
        }
        foreach (['supplier_id', 'purchase_order_id', 'branch_id', 'inventory_id', 'status'] as $key) {
            if (!empty($validated[$key])) {
                $conditions[$key] = $validated[$key];
            }
        }
        if (!empty($validated['grn_date_from'])) {
            $conditions['grn_date_from'] = $validated['grn_date_from'];
        }
        if (!empty($validated['grn_date_to'])) {
            $conditions['grn_date_to'] = $validated['grn_date_to'];
        }

        $result = $this->service->list($perPage, $page, $searches, $conditions);
        $result['data'] = $result['data']->map(function ($grn) {
            return [
                'id' => $grn->id,
                'grn_no' => $grn->grn_no,
                'grn_date' => $grn->grn_date,
                'supplier' => $grn->supplier?->name,
                'po_no' => $grn->purchaseOrder?->po_number,
                'branch' => $grn->branch?->name,
                'warehouse_location' => $grn->inventory?->name,
                'delivery_status' => $grn->purchaseOrder?->delivery_status,
                'total_amount' => $grn->total_amount,
                'currency' => $grn->currency?->code,
                'status' => $grn->status,
            ];
        })->values();
        return $this->paginatedSuccessResponse($result, 200, 'Goods receive note lists');
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->service->create($request->validated());
            return $this->successResponse($result, 200, 'Goods receive note is created successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show($id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }
        $data = $this->service->find((int) $id);
        if (!$data) {
            return $this->errorResponse('Goods receive note not found', 404);
        }
        return $this->successResponse($data, 200, 'Goods receive note');
    }

    public function update(UpdateRequest $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }
        try {
            $result = $this->service->update((int) $id, $request->validated());
            if ($result['status'] === 'not_found') {
                return $this->errorResponse('Goods receive note not found', 404);
            }
            if ($result['status'] === 'invalid_status') {
                return $this->errorResponse('Only pending GRN can be updated', 422);
            }
            return $this->successResponse($result['data'], 200, 'Goods receive note is updated successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function delete($id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }
        $result = $this->service->delete((int) $id);
        if ($result['status'] === 'not_found') {
            return $this->errorResponse('Goods receive note not found', 404);
        }
        if ($result['status'] === 'invalid_status') {
            return $this->errorResponse('Only pending GRN can be deleted', 422);
        }
        return $this->successResponse([], 200, 'Goods receive note deleted successfully');
    }

    public function approve(Request $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }
        $validator = Validator::make($request->all(), [
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'cashbook_id' => ['required_with:paid_amount', 'integer', 'exists:cashbooks,id'],
        ]);
        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        try {
            $result = $this->service->approve((int) $id, $validator->validated());
            if ($result['status'] === 'not_found') {
                return $this->errorResponse('Goods receive note not found', 404);
            }
            if ($result['status'] === 'already_approved') {
                return $this->errorResponse('Goods receive note already approved', 422);
            }
            if ($result['status'] === 'already_rejected') {
                return $this->errorResponse('Rejected GRN cannot be approved', 422);
            }
            return $this->successResponse($result['data'], 200, 'Goods receive note approved successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function reject($id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }
        $result = $this->service->reject((int) $id);
        if ($result['status'] === 'not_found') {
            return $this->errorResponse('Goods receive note not found', 404);
        }
        if ($result['status'] === 'invalid_status') {
            return $this->errorResponse('Only pending GRN can be rejected', 422);
        }
        return $this->successResponse($result['data'], 200, 'Goods receive note rejected successfully');
    }
}
