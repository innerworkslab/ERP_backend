<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Modules\Inventory\app\Http\Requests\PurchaseReturn\CreateRequest;
use Modules\Inventory\app\Http\Requests\PurchaseReturn\ListingRequest;
use Modules\Inventory\app\Http\Requests\PurchaseReturn\UpdateRequest;
use Modules\Inventory\app\Http\Services\PurchaseReturnService;

class PurchaseReturnController extends Controller
{
    use ApiResponser;

    public function __construct(private PurchaseReturnService $service)
    {
    }

    public function index(ListingRequest $request)
    {
        $validated = $request->validated();
        $perPage = $validated['per_page'] ?? 20;
        $page = $validated['page'] ?? 1;
        $search = $validated['search'] ?? null;
        $conditions = [];

        foreach (['goods_receive_note_id', 'purchase_order_id', 'supplier_id', 'branch_id', 'inventory_id', 'return_type', 'status'] as $key) {
            if (!empty($validated[$key])) {
                $conditions[$key] = $validated[$key];
            }
        }

        if (!empty($validated['return_date_from'])) {
            $conditions['return_date_from'] = $validated['return_date_from'];
        }

        if (!empty($validated['return_date_to'])) {
            $conditions['return_date_to'] = $validated['return_date_to'];
        }

        $result = $this->service->list($perPage, $page, $search, $validated['return_no'] ?? null, $conditions);
        $result['data'] = $result['data']->map(function ($purchaseReturn) {
            return [
                'id' => $purchaseReturn->id,
                'return_no' => $purchaseReturn->return_no,
                'return_date' => $purchaseReturn->return_date,
                'grn_no' => $purchaseReturn->goodsReceiveNote?->grn_no,
                'po_no' => $purchaseReturn->purchaseOrder?->po_number,
                'supplier' => $purchaseReturn->supplier?->name,
                'branch' => $purchaseReturn->branch?->name,
                'inventory' => $purchaseReturn->inventory?->name,
                'currency' => $purchaseReturn->currency?->code,
                'return_type' => $purchaseReturn->return_type,
                'exchange_type' => $purchaseReturn->exchange_type,
                'total_amount' => $purchaseReturn->total_amount,
                'status' => $purchaseReturn->status,
            ];
        })->values();

        return $this->paginatedSuccessResponse($result, 200, 'Purchase return lists');
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->service->create($request->validated());

            return $this->successResponse($result, 200, 'Purchase return is created successfully');
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
            return $this->errorResponse('Purchase return not found', 404);
        }

        return $this->successResponse($data, 200, 'Purchase return');
    }

    public function update(UpdateRequest $request, $id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }

        try {
            $result = $this->service->update((int) $id, $request->validated());
            if ($result['status'] === 'not_found') {
                return $this->errorResponse('Purchase return not found', 404);
            }

            if ($result['status'] === 'invalid_status') {
                return $this->errorResponse('Only pending purchase return can be updated', 422);
            }

            return $this->successResponse($result['data'], 200, 'Purchase return is updated successfully');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function approve($id)
    {
        if (!is_numeric($id)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }

        try {
            $result = $this->service->approve((int) $id);
            if ($result['status'] === 'not_found') {
                return $this->errorResponse('Purchase return not found', 404);
            }

            if ($result['status'] === 'already_approved') {
                return $this->errorResponse('Purchase return already approved', 422);
            }

            if ($result['status'] === 'already_rejected') {
                return $this->errorResponse('Rejected purchase return cannot be approved', 422);
            }

            return $this->successResponse($result['data'], 200, 'Purchase return approved successfully');
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
            return $this->errorResponse('Purchase return not found', 404);
        }

        if ($result['status'] === 'invalid_status') {
            return $this->errorResponse('Only pending purchase return can be rejected', 422);
        }

        return $this->successResponse($result['data'], 200, 'Purchase return rejected successfully');
    }

    public function returnableLines($goodsReceiveNoteId)
    {
        if (!is_numeric($goodsReceiveNoteId)) {
            return $this->errorResponse('ID must be an integer!', 422);
        }

        try {
            $result = $this->service->getReturnableLines((int) $goodsReceiveNoteId);

            return $this->successResponse($result, 200, 'Returnable GRN lines');
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 422);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
