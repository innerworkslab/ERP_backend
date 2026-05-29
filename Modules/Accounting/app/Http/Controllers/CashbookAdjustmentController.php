<?php

namespace Modules\Accounting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Accounting\app\Http\Services\CashbookAdjustmentService;
use Modules\Accounting\app\Http\Requests\CashbookAdjustment\CreateRequest;
use Modules\Accounting\app\Http\Requests\CashbookAdjustment\ListingRequest;
use Modules\Accounting\app\Http\Requests\CashbookAdjustment\UpdateRequest;

class CashbookAdjustmentController extends Controller
{
    use ApiResponser;

    private CashbookAdjustmentService $cashbookAdjustmentService;

    public function __construct(CashbookAdjustmentService $cashbookAdjustmentService)
    {
        $this->cashbookAdjustmentService = $cashbookAdjustmentService;
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
            $searches = [];
            $conditions = [];

            if (!empty($validated['cashbook_id'])) {
                $conditions['cashbook_id'] = (int) $validated['cashbook_id'];
            }

            if (!empty($validated['type'])) {
                $conditions['type'] = $validated['type'];
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            if (!empty($validated['from_date'])) {
                $conditions['from_date'] = $validated['from_date'];
            }

            if (!empty($validated['to_date'])) {
                $conditions['to_date'] = $validated['to_date'];
            }

            $with = ['cashbook.branch', 'cashbook.currency', 'creator', 'approver', 'branch'];
            $result = $this->cashbookAdjustmentService->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                searches: $searches,
                conditions: $conditions,
                with: $with
            );

            return $this->paginatedSuccessResponse($result, 200, 'Cashbook Adjustment Lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }

    public function findOrFail($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->cashbookAdjustmentService->find((int) $id);
            if (!$result) {
                return $this->errorResponse('Cashbook Adjustment not found', 404);
            }

            return $this->successResponse($result, 200, 'cashbook adjustment');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }

    public function create(CreateRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $result = $this->cashbookAdjustmentService->create($request->validated());
            return $this->successResponse($result, 200, 'Cashbook Adjustment is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->cashbookAdjustmentService->update((int) $id, $request->validated());
            if (!$result) {
                return $this->errorResponse('Cashbook Adjustment not found', 404);
            }

            return $this->successResponse($result, 200, 'Cashbook Adjustment is updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }

    public function approve($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $existing = $this->cashbookAdjustmentService->find((int) $id);
            if (!$existing) {
                return $this->errorResponse('Cashbook Adjustment not found', 404);
            }

            if ($existing->status === 'approved') {
                return $this->errorResponse('Cashbook adjustment already approved.', 422);
            }

            if ($existing->status === 'rejected') {
                return $this->errorResponse('Rejected cashbook adjustment cannot be approved.', 422);
            }

            $result = $this->cashbookAdjustmentService->approve((int) $id);

            return $this->successResponse($result, 200, 'Cashbook Adjustment is approved successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }

    public function reject($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $existing = $this->cashbookAdjustmentService->find((int) $id);
            if (!$existing) {
                return $this->errorResponse('Cashbook Adjustment not found', 404);
            }

            if ($existing->status === 'rejected') {
                return $this->errorResponse('Cashbook adjustment already rejected.', 422);
            }

            if ($existing->status === 'approved') {
                return $this->errorResponse('Approved cashbook adjustment cannot be rejected.', 422);
            }

            $result = $this->cashbookAdjustmentService->reject((int) $id);

            return $this->successResponse($result, 200, 'Cashbook Adjustment is rejected successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }
}
