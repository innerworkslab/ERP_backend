<?php

namespace Modules\Inventory\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Inventory\app\Http\Requests\StockMovement\ListingRequest;
use Modules\Inventory\app\Http\Services\StockMovementService;

class StockMovementController extends Controller
{
    use ApiResponser;

    private $stock_movement_service;

    public function __construct(StockMovementService $stock_movement_service)
    {
        $this->stock_movement_service = $stock_movement_service;
    }

    /**
     * Display a listing of the resource.
     */
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

            $searches = [];
            $conditions = [];

            if (!empty($validated['voucher_no'])) {
                $searches['voucher_no'] = $validated['voucher_no'];
            }

            if (!empty($validated['product_search'])) {
                $searches['product_search'] = $validated['product_search'];
            }


            if (!empty($validated['inventory_name'])) {
                $searches['inventory_name'] = $validated['inventory_name'];
            }

            if (!empty($validated['branch_name'])) {
                $searches['branch_name'] = $validated['branch_name'];
            }

            if (!empty($validated['reference_type'])) {
                $conditions['reference_type'] = $validated['reference_type'];
            }

            if (!empty($validated['reference_id'])) {
                $conditions['reference_id'] = (int) $validated['reference_id'];
            }

            if (!empty($validated['transaction_date_from'])) {
                $conditions['transaction_date_from'] = $validated['transaction_date_from'];
            }

            if (!empty($validated['transaction_date_to'])) {
                $conditions['transaction_date_to'] = $validated['transaction_date_to'];
            }

            $result = $this->stock_movement_service->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                searches: $searches,
                conditions: $conditions
            );

            return $this->paginatedSuccessResponse($result, 200, 'Stock ledger lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
