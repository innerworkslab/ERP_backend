<?php

namespace Modules\PriceGroup\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Modules\PriceGroup\app\Http\Requests\SellingPriceGroup\CreateRequest;
use Modules\PriceGroup\app\Http\Requests\SellingPriceGroup\ListingRequest;
use Modules\PriceGroup\app\Http\Requests\SellingPriceGroup\UpdateRequest;
use Modules\PriceGroup\app\Http\Services\SellingPriceGroup\SellingPriceGroupService;

class SellingPriceGroupController extends Controller
{
    use ApiResponser;

    public function __construct(private SellingPriceGroupService $sellingPriceGroupService)
    {
    }

    public function index(ListingRequest $request)
    {
        try {
            $result = $this->sellingPriceGroupService->list($request->validated());

            return $this->paginatedSuccessResponse($result, 200, 'Selling price group list');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->sellingPriceGroupService->create($request->validated());

            return $this->successResponse($result, 201, 'Selling price group created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function show(int $id)
    {
        try {
            $result = $this->sellingPriceGroupService->findOrFail($id);

            return $this->successResponse($result, 200, 'Selling price group detail');
        } catch (ModelNotFoundException $e) {
            return $this->errorResponse('Selling price group not found.', 404);
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, int $id)
    {
        try {
            $result = $this->sellingPriceGroupService->update($id, $request->validated());

            if (!$result) {
                return $this->errorResponse('Selling price group not found.', 404);
            }

            return $this->successResponse($result, 200, 'Selling price group updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function destroy(int $id)
    {
        try {
            $deleted = $this->sellingPriceGroupService->delete($id);

            if (!$deleted) {
                return $this->errorResponse('Selling price group not found.', 404);
            }

            return $this->successResponse([], 200, 'Selling price group deleted successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function toggleActive(int $id)
    {
        try {
            $result = $this->sellingPriceGroupService->toggleActive($id);

            if (!$result) {
                return $this->errorResponse('Selling price group not found.', 404);
            }

            return $this->successResponse($result, 200, 'Selling price group status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
