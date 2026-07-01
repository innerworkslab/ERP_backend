<?php

namespace Modules\Accounting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Modules\Accounting\app\Http\Requests\CashbookTransaction\CreateRequest;
use Modules\Accounting\app\Http\Requests\CashbookTransaction\ListingRequest;
use Modules\Accounting\app\Http\Requests\CashbookTransaction\UpdateRequest;
use Modules\Accounting\app\Http\Services\CashbookTransactionService;


class CashbookTransactionController extends Controller
{
    use ApiResponser;

    private $cashbook_transaction_service;

    public function __construct(CashbookTransactionService $cashbook_transaction_service)
    {
        $this->cashbook_transaction_service = $cashbook_transaction_service;
    }

    public function index(ListingRequest $request)
    {
        try {
            $validated = $request->validated();
            $per_page = array_key_exists('per_page', $validated) ? $validated['per_page'] : 20;
            $page = array_key_exists('page', $validated) ? $validated['page'] : 1;
            $searches = [];
            $conditions = [];
            $status = null;

            if (!empty($validated['search'])) {
                $search = $validated['search'];

                $searches = [
                    'reference_no' => $search,
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }
            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }
            if (!empty($validated['cashbook_id'])) {
                $conditions['cashbook_id'] = (int) $validated['cashbook_id'];
            }
            if (!empty($validated['category'])) {
                $conditions['category'] = $validated['category'];
            }

            $with = ['cashbook', 'currency', 'source_account', 'destination_account', 'created_by', 'updated_by', 'attachments'];
            $res_data = $this->cashbook_transaction_service->getDataWithPagination($per_page, $page, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Account Transaction Lists');
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
            $data = $this->cashbook_transaction_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'cashbook transaction');
            } else {
                return $this->errorResponse('Cashbook Transaction not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function create(CreateRequest $request)
    {
        try {
            $validated = $request->validated();
            $result = $this->cashbook_transaction_service->create($validated);
            return $this->successResponse($result, 200, 'Cashbook Transaction is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            $validated = $request->validated();
            $data = $this->cashbook_transaction_service->whereFirst('id', $id);
            if ($data) {
                if ($errorResponse = $this->ensureTransactionIsMutable($data)) {
                    return $errorResponse;
                }
                $result = $this->cashbook_transaction_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Cashbook Transaction is updated successfully');
            } else {
                return $this->errorResponse('Cashbook Transaction not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function confirm($id)
    {
        try {
            $data = $this->cashbook_transaction_service->whereFirst('id', $id);
            if ($data) {
                if ($data->status === 'confirmed') {
                    return $this->errorResponse('already confirmed. Cannot confirm again.', 409);
                }

                if ($data->status === 'cancelled') {
                    return $this->errorResponse('already cancelled. Cannot confirm.', 409);
                }

                if ($errorResponse = $this->ensureTransactionIsMutable($data)) {
                    return $errorResponse;
                }
                $result = $this->cashbook_transaction_service->confirm($id);
                return $this->successResponse($result, 200, 'Cashbook Transaction is confirmed successfully');
            } else {
                return $this->errorResponse('Cashbook Transaction not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    private function ensureTransactionIsMutable($transaction)
    {
        if ($transaction->status === 'confirmed') {
            return $this->errorResponse('already confirmed. Cannot edit.', 409);
        }

        if ($transaction->status === 'cancelled') {
            return $this->errorResponse('already cancelled. Cannot edit.', 409);
        }

        return null;
    }
}
