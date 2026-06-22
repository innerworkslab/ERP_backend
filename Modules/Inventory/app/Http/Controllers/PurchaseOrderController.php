<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Requests\PurchaseOrder\CreateRequest;
use Modules\Inventory\app\Http\Requests\PurchaseOrder\ListingRequest;
use Modules\Inventory\app\Http\Requests\PurchaseOrder\PaymentRequest;
use Modules\Inventory\app\Http\Requests\PurchaseOrder\UpdateRequest;
use Modules\Inventory\app\Http\Services\PurchaseOrderService;

class PurchaseOrderController extends Controller
{
    use ApiResponser;

    private $purchase_order_service;

    public function __construct(PurchaseOrderService $purchase_order_service)
    {
        $this->purchase_order_service = $purchase_order_service;
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

            $conditions = [];
            $searches = [];

            if (!empty($validated['search'])) {
                $searches = [
                    'po_number' => $validated['search'],
                    'remarks' => $validated['search'],
                ];
            }

            if (!empty($validated['po_number'])) {
                $searches['po_number'] = $validated['po_number'];
            }

            foreach (['supplier_id', 'branch_id', 'inventory_id', 'status', 'payment_status', 'delivery_status'] as $key) {
                if (!empty($validated[$key])) {
                    $conditions[$key] = $validated[$key];
                }
            }

            if (!empty($validated['po_date'])) {
                $conditions['po_date_from'] = $validated['po_date'];
                $conditions['po_date_to'] = $validated['po_date'];
            }

            if (!empty($validated['po_date_from'])) {
                $conditions['po_date_from'] = $validated['po_date_from'];
            }

            if (!empty($validated['po_date_to'])) {
                $conditions['po_date_to'] = $validated['po_date_to'];
            }

            $resData = $this->purchase_order_service->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                searches: $searches,
                conditions: $conditions
            );

            return $this->paginatedSuccessResponse($resData, 200, 'Purchase order lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $result = $this->purchase_order_service->create($request->validated());
            return $this->successResponse($result, 200, 'Purchase order is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $data = $this->purchase_order_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'Purchase order');
            }

            return $this->errorResponse('Purchase order not found', 404);
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

            $result = $this->purchase_order_service->update((int) $id, $request->validated());
            if ($result['status'] == 'item_not_found') {
                return $this->errorResponse('Purchase order not found', 404);
            }
            if ($result['status'] == 'invalid_status') {
                return $this->errorResponse('Only pending or draft purchase order can be processed', 422);
            }

            return $this->successResponse($result['data'] ?? [], 200, 'Purchase order is updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function destroy($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->purchase_order_service->delete((int) $id);
            if ($result['status'] == 'item_not_found') {
                return $this->errorResponse('Purchase order not found', 404);
            }
            if ($result['status'] == 'invalid_status') {
                return $this->errorResponse('Only pending or draft purchase order can be processed', 422);
            }

            return $this->successResponse([], 200, 'Purchase order deleted successfully!');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $validator = Validator::make($request->all(), [
                'status' => ['required', 'in:pending,draft,ordered,cancelled'],
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $result = $this->purchase_order_service->updateStatus((int) $id, $request->input('status'));
            if ($result['status'] == 'item_not_found') {
                return $this->errorResponse('Purchase order not found', 404);
            }

            return $this->successResponse($result['data'] ?? [], 200, 'Purchase order status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function updatePaymentStatus(PaymentRequest $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $validated = $request->validated();

            $result = $this->purchase_order_service->updatePaymentStatus(
                (int) $id,
                $validated
            );
            
            if ($result['status'] == 'item_not_found') {
                return $this->errorResponse('Purchase order not found', 404);
            }
            if ($result['status'] == 'already_paid') {
                return $this->errorResponse('Purchase order is already fully paid. Additional payment is not allowed.', 422);
            }
            if ($result['status'] == 'paid_amount_exceeded') {
                return $this->errorResponse('Paid amount cannot be greater than total amount.', 422);
            }
            if ($result['status'] == 'cashbook_required') {
                return $this->errorResponse('Cashbook is required when paid amount is greater than zero.', 422);
            }
            if ($result['status'] == 'ap_parent_missing') {
                return $this->errorResponse('AP parent account (4-2100) is missing in COA.', 422);
            }
            if ($result['status'] == 'inventory_account_missing') {
                return $this->errorResponse('Inventory account (2-1024) is missing in COA.', 422);
            }
            if ($result['status'] == 'supplier_missing') {
                return $this->errorResponse('Supplier is required for AP posting.', 422);
            }
            if ($result['status'] == 'currency_invalid') {
                return $this->errorResponse('Transaction currency is invalid.', 422);
            }
            if ($result['status'] == 'cashbook_not_found') {
                return $this->errorResponse('Cashbook not found.', 422);
            }
            if ($result['status'] == 'cashbook_currency_mismatch') {
                return $this->errorResponse('Cashbook currency does not match the purchase order currency. Please select a cashbook with the same currency.', 422);
            }

            return $this->successResponse($result['data'] ?? [], 200, 'Purchase order payment status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function updateDeliveryStatus(Request $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $validator = Validator::make($request->all(), [
                'delivery_status' => ['required', 'in:not_delivered,partially_delivered,fully_delivered'],
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $result = $this->purchase_order_service->updateDeliveryStatus((int) $id, $request->input('delivery_status'));
            if ($result['status'] == 'item_not_found') {
                return $this->errorResponse('Purchase order not found', 404);
            }

            return $this->successResponse($result['data'] ?? [], 200, 'Purchase order delivery status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function calculateLineTotal(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'quantity' => ['required', 'numeric', 'min:0.01'],
                'unit_price' => ['required', 'numeric', 'min:0'],
                'discount_amount' => ['nullable', 'numeric', 'min:0'],
                'tax_amount' => ['nullable', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $result = $this->purchase_order_service->calculateLineTotal($validator->validated());
            return $this->successResponse($result, 200, 'Purchase order line total calculated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function calculateTotalAmount(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'discount_amount' => ['nullable', 'numeric', 'min:0'],
                'tax_amount' => ['nullable', 'numeric', 'min:0'],
                'lines' => ['required', 'array', 'min:1'],
                'lines.*.quantity' => ['required', 'numeric', 'min:0.01'],
                'lines.*.unit_price' => ['required', 'numeric', 'min:0'],
                'lines.*.discount_amount' => ['nullable', 'numeric', 'min:0'],
                'lines.*.tax_amount' => ['nullable', 'numeric', 'min:0'],
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $result = $this->purchase_order_service->calculateTotalAmount($validator->validated());
            return $this->successResponse($result, 200, 'Purchase order total amount calculated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

}
