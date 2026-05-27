<?php

namespace Modules\Accounting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Accounting\app\Http\Requests\CashbookTransfer\CreateRequest;
use Modules\Accounting\app\Http\Requests\CashbookTransfer\ListingRequest;
use Modules\Accounting\app\Http\Requests\CashbookTransfer\UpdateRequest;
use Modules\Accounting\app\Http\Services\CashbookTransferService;

class CashbookTransferController extends Controller
{
    use ApiResponser;

    private CashbookTransferService $cashbook_transfer_service;

    public function __construct(CashbookTransferService $cashbook_transfer_service)
    {
        $this->cashbook_transfer_service = $cashbook_transfer_service;
    }

    public function index(ListingRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $perPage = array_key_exists('per_page', $validated) ? $validated['per_page'] : 20;
            $page = array_key_exists('page', $validated) ? $validated['page'] : 1;
            $searches = [];
            $conditions = [];

            if (!empty($validated['search'])) {
                $searches = [
                    'reference_no' => $validated['search'],
                ];
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

            $with = [
                'sourceCashbook',
                'destinationCashbook',
                'currency',
                'created_by',
                'updated_by',
            ];

            $result = $this->cashbook_transfer_service->getDataWithPagination(
                perPage: $perPage,
                page: $page,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );

            return $this->paginatedSuccessResponse($result, 200, 'Cashbook Transfer Lists');
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

            $data = $this->cashbook_transfer_service->find((int) $id);

            if ($data) {
                return $this->successResponse($data, 200, 'cashbook transfer');
            }

            return $this->errorResponse('Cashbook Transfer not found', 404);
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
            $result = $this->cashbook_transfer_service->create($validated);

            return $this->successResponse($result, 200, 'Cashbook Transfer is created successfully');
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

            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $existing = $this->cashbook_transfer_service->whereFirst('id', (int) $id);

            if (!$existing) {
                return $this->errorResponse('Cashbook Transfer not found', 404);
            }

            $result = $this->cashbook_transfer_service->update((int) $id, $request->validated());

            return $this->successResponse($result, 200, 'Cashbook Transfer is updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function confirmTransfer($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->cashbook_transfer_service->confirmTransfer((int) $id);

            if (!$result) {
                return $this->errorResponse('Cashbook Transfer not found', 404);
            }

            return $this->successResponse($result, 200, 'Cashbook Transfer is confirmed successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }

    public function rejectTransfer($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->cashbook_transfer_service->rejectTransfer((int) $id);

            if (!$result) {
                return $this->errorResponse('Cashbook Transfer not found', 404);
            }

            return $this->successResponse($result, 200, 'Cashbook Transfer is rejected successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse($e->getMessage() ?: 'Something went wrong!', 500);
        }
    }
}
