<?php

namespace Modules\PriceGroup\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\PriceGroup\app\Http\Requests\PricingGroup\CreateRequest;
use Modules\PriceGroup\app\Http\Requests\PricingGroup\ListingRequest;
use Modules\PriceGroup\app\Http\Requests\PricingGroup\UpdateRequest;
use Modules\PriceGroup\app\Http\Services\PricingGroup\PricingGroupService;

class PriceGroupController extends Controller
{
    use ApiResponser;

    public function __construct(private PricingGroupService $pricingGroupService)
    {
    }

    public function index(ListingRequest $request)
    {
        try {
            $result = $this->pricingGroupService->list($request->validated());

            return $this->paginatedSuccessResponse($result, 200, 'Price group list');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->pricingGroupService->create($request->validated());

            return $this->successResponse($result, 201, 'Price group created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show(int $id)
    {
        try {
            $result = $this->pricingGroupService->findOrFail($id);

            return $this->successResponse($result, 200, 'Price group detail');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Price group not found.', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, int $id)
    {
        try {
            $result = $this->pricingGroupService->update($id, $request->validated());

            if (!$result) {
                return $this->errorResponse('Price group not found.', 404);
            }

            return $this->successResponse($result, 200, 'Price group updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $deleted = $this->pricingGroupService->delete($id);

            if (!$deleted) {
                return $this->errorResponse('Price group not found.', 404);
            }

            return $this->successResponse([], 200, 'Price group deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function toggleActive(int $id)
    {
        try {
            $result = $this->pricingGroupService->toggleActive($id);

            if (!$result) {
                return $this->errorResponse('Price group not found.', 404);
            }

            return $this->successResponse($result, 200, 'Price group status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
