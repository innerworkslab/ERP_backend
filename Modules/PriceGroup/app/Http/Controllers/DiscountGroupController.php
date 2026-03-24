<?php

namespace Modules\PriceGroup\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\PriceGroup\app\Http\Requests\DiscountGroup\CreateRequest;
use Modules\PriceGroup\app\Http\Requests\DiscountGroup\ListingRequest;
use Modules\PriceGroup\app\Http\Requests\DiscountGroup\UpdateRequest;
use Modules\PriceGroup\app\Http\Services\DiscountGroup\DiscountGroupService;

class DiscountGroupController extends Controller
{
    use ApiResponser;

    public function __construct(private DiscountGroupService $discountGroupService)
    {
    }

    public function index(ListingRequest $request)
    {
        try {
            $result = $this->discountGroupService->list($request->validated());

            return $this->paginatedSuccessResponse($result, 200, 'Discount group list');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->discountGroupService->create($request->validated());

            return $this->successResponse($result, 201, 'Discount group created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show(int $id)
    {
        try {
            $result = $this->discountGroupService->findOrFail($id);

            return $this->successResponse($result, 200, 'Discount group detail');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Discount group not found.', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, int $id)
    {
        try {
            $result = $this->discountGroupService->update($id, $request->validated());

            if (!$result) {
                return $this->errorResponse('Discount group not found.', 404);
            }

            return $this->successResponse($result, 200, 'Discount group updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $deleted = $this->discountGroupService->delete($id);

            if (!$deleted) {
                return $this->errorResponse('Discount group not found.', 404);
            }

            return $this->successResponse([], 200, 'Discount group deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function toggleActive(int $id)
    {
        try {
            $result = $this->discountGroupService->toggleActive($id);

            if (!$result) {
                return $this->errorResponse('Discount group not found.', 404);
            }

            return $this->successResponse($result, 200, 'Discount group status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
