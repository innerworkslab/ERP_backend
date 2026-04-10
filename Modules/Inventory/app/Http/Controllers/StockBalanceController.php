<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Requests\StockBalance\ListingRequest;
use Modules\Inventory\app\Http\Services\StockBalanceService;

class StockBalanceController extends Controller
{
    use ApiResponser;

    protected $stock_balance_service;

    public function __construct(StockBalanceService $stock_balance_service)
    {
        $this->stock_balance_service = $stock_balance_service;
    }

    public function index(ListingRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $perPage = array_key_exists('per_page', $validated) ? (int) $validated['per_page'] : 20;
            $page = array_key_exists('page', $validated) ? (int) $validated['page'] : 1;
            $nearExpiryDays = array_key_exists('near_expiry_days', $validated) ? (int) $validated['near_expiry_days'] : 30;

            $filters = [
                'search' => $validated['search'] ?? null,
                'branch_id' => isset($validated['branch_id']) ? (int) $validated['branch_id'] : null,
                'inventory_id' => isset($validated['inventory_id']) ? (int) $validated['inventory_id'] : null,
                'product_id' => isset($validated['product_id']) ? (int) $validated['product_id'] : null,
                'category_id' => isset($validated['category_id']) ? (int) $validated['category_id'] : null,
                'brand_id' => isset($validated['brand_id']) ? (int) $validated['brand_id'] : null,
                'collection_id' => isset($validated['collection_id']) ? (int) $validated['collection_id'] : null,
                'lot_no' => $validated['lot_no'] ?? null,
                'expired_date_from' => $validated['expired_date_from'] ?? null,
                'expired_date_to' => $validated['expired_date_to'] ?? null,
                'status' => $validated['status'] ?? null,
                'expiry_status' => $validated['expiry_status'] ?? null,
            ];

            $result = $this->stock_balance_service->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                filters: $filters,
                nearExpiryDays: $nearExpiryDays
            );

            return $this->paginatedSuccessResponse($result, 200, 'Stock balance lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function lotTotals($productId)
    {
        try {
            if (!is_numeric($productId)) {
                return $this->errorResponse('Product ID must be an integer!', 422);
            }

            $request = request();
            $validator = Validator::make($request->all(), [
                'inventory_id' => 'nullable|integer|exists:inventories,id',
                'near_expiry_days' => 'nullable|integer|min:1|max:365',
            ]);

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $inventoryId = $request->filled('inventory_id') ? (int) $request->input('inventory_id') : null;
            $nearExpiryDays = $request->filled('near_expiry_days') ? (int) $request->input('near_expiry_days') : 30;

            $data = $this->stock_balance_service->getProductLotTotals(
                productId: (int) $productId,
                inventoryId: $inventoryId,
                nearExpiryDays: $nearExpiryDays
            );

            return $this->successResponse($data, 200, 'Product lot totals');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
