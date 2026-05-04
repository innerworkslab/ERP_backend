<?php

namespace Modules\Stakeholder\app\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Stakeholder\app\Http\Requests\Customer\CreateRequest;
use Modules\Stakeholder\app\Http\Requests\Customer\ListingRequest;
use Modules\Stakeholder\app\Http\Requests\Customer\UpdateRequest;
use Modules\Stakeholder\app\Http\Services\Customer\CustomerService;

class CustomerController extends Controller
{
    use ApiResponser;

    public function __construct(private CustomerService $customerService)
    {
    }

    public function index(ListingRequest $request)
    {
        try {
            $result = $this->customerService->list($request->validated());

            return $this->paginatedSuccessResponse($result, 200, 'Customer list');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->customerService->create($request->validated());

            return $this->successResponse($result, 201, 'Customer created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show(int $id)
    {
        try {
            $result = $this->customerService->findOrFail($id);

            return $this->successResponse($result, 200, 'Customer detail');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Customer not found.', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, int $id)
    {
        try {
            $result = $this->customerService->update($id, $request->validated());

            if (!$result) {
                return $this->errorResponse('Customer not found.', 404);
            }

            return $this->successResponse($result, 200, 'Customer updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $deleted = $this->customerService->delete($id);

            if (!$deleted) {
                return $this->errorResponse('Customer not found.', 404);
            }

            return $this->successResponse([], 200, 'Customer deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function toggleStatus(int $id)
    {
        try {
            $result = $this->customerService->toggleStatus($id);

            if (!$result) {
                return $this->errorResponse('Customer not found.', 404);
            }

            return $this->successResponse(true, 200, 'Customer status toggled successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
