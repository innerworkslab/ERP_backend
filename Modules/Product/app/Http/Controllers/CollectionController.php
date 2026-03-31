<?php

namespace Modules\Product\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Product\app\Http\Requests\Collection\AddProductsRequest;
use Modules\Product\app\Http\Requests\Collection\CreateRequest;
use Modules\Product\app\Http\Requests\Collection\ListingRequest;
use Modules\Product\app\Http\Requests\Collection\UpdateRequest;
use Modules\Product\app\Http\Services\CollectionService;

class CollectionController extends Controller
{
    use ApiResponser;

    protected $collection_service;

    public function __construct(CollectionService $collection_service)
    {
        $this->collection_service = $collection_service;
    }

    public function index(ListingRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $per_page = (int) ($validated['per_page'] ?? 20);
            $page = (int) ($validated['page'] ?? 1);
            $searches = [];
            $conditions = [];
            $status = null;

            $keyword = $validated['keyword'] ?? ($validated['search'] ?? null);
            if (!empty($keyword)) {
                $searches = [
                    'name' => $keyword,
                ];
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            $with = [
                'purchase_currency',
                'purchase_tax',
                'purchase_uom',
                'sale_currency',
                'sale_tax',
                'sale_uom',
                'created_by',
                'updated_by',
            ];

            $res_data = $this->collection_service->getDataWithPagination(
                $per_page,
                $page,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Collection lists');
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

            $data = $this->collection_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'collection');
            }

            return $this->errorResponse('Collection not found', 404);
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
            $result = $this->collection_service->create($validated);

            return $this->successResponse($result, 200, 'Collection is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $id = (int) $id;
            $data = $this->collection_service->whereFirst('id', $id);
            if (!$data) {
                return $this->errorResponse('Collection not found', 404);
            }

            $validated = $request->validated();
            $result = $this->collection_service->update($id, $validated);

            return $this->successResponse($result, 200, 'Collection is updated successfully');
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

            $data = $this->collection_service->whereFirst('id', (int) $id);
            if ($data) {
                $this->collection_service->toggleCollectionStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            }

            return $this->errorResponse('Collection not found', 404);
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

            $id = (int) $id;
            $data = $this->collection_service->whereFirst('id', $id);
            if (!$data) {
                return $this->errorResponse('Collection not found!', 404);
            }

            if ($this->collection_service->delete($id)) {
                return $this->successResponse([], 200, 'Collection deleted successfully!');
            }

            return $this->errorResponse('Collection not found!', 404);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function getProducts($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $products = $this->collection_service->getProducts((int) $id);
            if ($products === null) {
                return $this->errorResponse('Collection not found', 404);
            }

            return $this->successResponse($products, 200, 'collection products');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function addProducts(AddProductsRequest $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $collection = $this->collection_service->whereFirst('id', (int) $id);
            if (!$collection) {
                return $this->errorResponse('Collection not found', 404);
            }

            $validated = $request->validated();
            $result = $this->collection_service->syncProducts((int) $id, $validated['products']);

            return $this->successResponse($result, 200, 'Collection products updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
