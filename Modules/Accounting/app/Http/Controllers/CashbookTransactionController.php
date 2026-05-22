<?php

namespace Modules\Accounting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
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
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
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
            if (!empty($validated['transaction_type'])) {
                $conditions['transaction_type'] = $validated['transaction_type'];
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
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
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
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }
            $validated = $request->validated();
            $data = $this->cashbook_transaction_service->whereFirst('id', $id);
            if ($data) {
                if ($data->status == "confirmed") {
                    return $this->errorResponse("already confirmed. Cannot edit.", 409);
                }
                if ($data->status == "cancelled") {
                    return $this->errorResponse("already cancelled. Cannot edit.", 409);
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

    public function delete($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }
            $data = $this->cashbook_transaction_service->whereFirst('id', $id);
            if ($data) {
                if ($this->cashbook_transaction_service->delete($id)) {
                    return $this->successResponse([], 200, 'Cashbook Transaction deleted successfully!');
                }
            } else {
                return $this->errorResponse('Cashbook Transaction not found!', 500);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function toggleActive($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }
            $data = $this->cashbook_transaction_service->whereFirst('id', $id);
            if ($data) {
                $this->cashbook_transaction_service->toggleCashbookStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Cashbook Transaction not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
