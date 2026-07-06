<?php

namespace Modules\Sale\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Sale\app\Http\Requests\SaleInvoice\CreateRequest;
use Modules\Sale\app\Http\Requests\SaleInvoice\ListingRequest;
use Modules\Sale\app\Http\Requests\SaleInvoice\UpdateRequest;
use Modules\Sale\app\Http\Services\SaleInvoiceService;

class SaleInvoiceController extends Controller
{
    use ApiResponser;

    protected $sale_invoice_service;

    public function __construct(SaleInvoiceService $sale_invoice_service)
    {
        $this->sale_invoice_service = $sale_invoice_service;
    }

    public function index(ListingRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $perPage = $validated['per_page'] ?? 20;
            $page = $validated['page'] ?? 1;
            $searches = !empty($validated['search']) ? [$validated['search']] : [];
            $conditions = [
                'branch_id' => $validated['branch_id'] ?? null,
                'inventory_id' => $validated['inventory_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'currency_id' => $validated['currency_id'] ?? null,
                'selling_price_group_id' => $validated['selling_price_group_id'] ?? null,
                'payment_status' => $validated['payment_status'] ?? null,
                'cashbook_id' => $validated['cashbook_id'] ?? null,
                'delivery_provider_id' => $validated['delivery_provider_id'] ?? null,
                'invoice_date_from' => $validated['invoice_date_from'] ?? null,
                'invoice_date_to' => $validated['invoice_date_to'] ?? null,
            ];
            $status = $validated['status'] ?? null;

            $result = $this->sale_invoice_service->getDataWithPagination($perPage, $page, $searches, $conditions, $status);

            return $this->paginatedSuccessResponse($result, 200, 'Sale Invoice Lists');
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

            $data = $this->sale_invoice_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'sale invoice');
            }

            return $this->errorResponse('Sale invoice not found', 404);
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

            $result = $this->sale_invoice_service->create($request->validated());

            return $this->successResponse($result, 200, 'Sale invoice is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage(), 422);
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

            $result = $this->sale_invoice_service->update((int) $id, $request->validated());

            if (($result['status'] ?? null) === 'not_found') {
                return $this->errorResponse('Sale invoice not found', 404);
            }

            if (($result['status'] ?? null) === 'invalid_status') {
                return $this->errorResponse('Sale invoice cannot be edited in its current status', 422);
            }

            return $this->successResponse($result['data'] ?? [], 200, 'Sale invoice is updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function delete($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->sale_invoice_service->delete((int) $id);

            if (($result['status'] ?? null) === 'not_found') {
                return $this->errorResponse('Sale invoice not found', 404);
            }

            if (($result['status'] ?? null) === 'invalid_status') {
                return $this->errorResponse('Sale invoice cannot be deleted in its current status', 422);
            }

            return $this->successResponse([], 200, 'Sale invoice deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function pending($id)
    {
        return $this->changeStatus($id, 'pending');
    }

    public function ordered($id)
    {
        return $this->changeStatus($id, 'ordered');
    }

    public function reserved($id)
    {
        return $this->changeStatus($id, 'reserved');
    }

    public function delivered(Request $request, $id)
    {
        return $this->changeStatus($id, 'delivered', $request);
    }

    private function changeStatus($id, string $status, ?Request $request = null)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $payload = $request ? $request->all() : [];
            $result = $this->sale_invoice_service->changeStatus((int) $id, $status, $payload);

            if (($result['status'] ?? null) === 'not_found') {
                return $this->errorResponse('Sale invoice not found', 404);
            }

            if (($result['status'] ?? null) === 'invalid_transition') {
                return $this->errorResponse('Invalid status transition', 422);
            }

            return $this->successResponse([], 200, 'Sale invoice status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
