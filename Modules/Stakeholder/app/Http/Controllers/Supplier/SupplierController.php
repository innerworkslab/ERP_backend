<?php

namespace Modules\Stakeholder\app\Http\Controllers\Supplier;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\Stakeholder\app\Http\Requests\Supplier\CreateRequest;
use Modules\Stakeholder\app\Http\Requests\Supplier\ListingRequest;
use Modules\Stakeholder\app\Http\Requests\Supplier\UpdateRequest;
use Modules\Stakeholder\app\Http\Services\Supplier\SupplierService;

class SupplierController extends Controller
{
    use ApiResponser;

    public function __construct(private SupplierService $supplierService)
    {
    }

    public function index(ListingRequest $request)
    {
        try {
            $result = $this->supplierService->list($request->validated());

            return $this->paginatedSuccessResponse($result, 200, 'Supplier list');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->supplierService->create($request->validated());

            return $this->successResponse($result, 201, 'Supplier created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show(int $id)
    {
        try {
            $result = $this->supplierService->findOrFail($id);

            return $this->successResponse($result, 200, 'Supplier detail');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Supplier not found.', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, int $id)
    {
        try {
            $result = $this->supplierService->update($id, $request->validated());

            if (!$result) {
                return $this->errorResponse('Supplier not found.', 404);
            }

            return $this->successResponse($result, 200, 'Supplier updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $deleted = $this->supplierService->delete($id);

            if (!$deleted) {
                return $this->errorResponse('Supplier not found.', 404);
            }

            return $this->successResponse([], 200, 'Supplier deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}