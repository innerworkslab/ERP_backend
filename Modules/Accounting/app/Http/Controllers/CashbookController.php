<?php

namespace Modules\Accounting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Accounting\app\Http\Requests\Cashbook\CreateRequest;
use Modules\Accounting\app\Http\Requests\Cashbook\ListingRequest;
use Modules\Accounting\app\Http\Requests\Cashbook\UpdateRequest;
use Modules\Accounting\app\Http\Services\CashbookService;


class CashbookController extends Controller
{
    use ApiResponser;

    private $cashbook_service;

    public function __construct(CashbookService $cashbook_service)
    {
        $this->cashbook_service = $cashbook_service;
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
                    'name' => $search,
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }
            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            $with = ['branch', 'currency', 'account', 'created_by', 'updated_by'];
            $res_data = $this->cashbook_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'Account Lists');
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
            $data = $this->cashbook_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'cashbook');
            } else {
                return $this->errorResponse('Cashbook not found', 404);
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
            $result = $this->cashbook_service->create($validated);
            return $this->successResponse($result, 200, 'Cashbook is created successfully');
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
            $data = $this->cashbook_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->cashbook_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Cashbook is updated successfully');
            } else {
                return $this->errorResponse('Cashbook not found', 404);
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
            $data = $this->cashbook_service->whereFirst('id', $id);
            if ($data) {
                if ($this->cashbook_service->delete($id)) {
                    return $this->successResponse([], 200, 'Cashbook deleted successfully!');
                }
            } else {
                return $this->errorResponse('Cashbook not found!', 500);
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
            $data = $this->cashbook_service->whereFirst('id', $id);
            if ($data) {
                $this->cashbook_service->toggleCashbookStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            } else {
                return $this->errorResponse('Cashbook not found', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
