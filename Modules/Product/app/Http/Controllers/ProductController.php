<?php

namespace Modules\Product\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Modules\Product\app\Http\Requests\Product\CreateProduct;
use Modules\Product\app\Http\Requests\Product\ListingProduct;
use Modules\Product\app\Http\Requests\Product\UpdateProduct;
use Modules\Product\app\Http\Services\ProductService;

class ProductController extends Controller
{
    use ApiResponser;

    protected $product_service;

    public function __construct(ProductService $product_service)
    {
        $this->product_service = $product_service;
    }

    public function index(ListingProduct $request)
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
                    'sku' => $keyword,
                ];
            }

            if (!empty($validated['category_id'])) {
                $conditions['category_id'] = (int) $validated['category_id'];
            }

            if (!empty($validated['brand_id'])) {
                $conditions['brand_id'] = (int) $validated['brand_id'];
            }

            if (!empty($validated['status'])) {
                $conditions['status'] = $validated['status'];
            }

            $with = [
                'category',
                'brand',
                'origin_country',
                'stock_uom',
                'purchase_currency',
                'purchase_tax',
                'purchase_uom',
                'sale_currency',
                'sale_tax',
                'sale_uom',
                'created_by',
                'updated_by',
            ];

            $res_data = $this->product_service->getDataWithPagination(
                $per_page,
                $page,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Product lists');
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

            $data = $this->product_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'product');
            }

            return $this->errorResponse('Product not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function create(CreateProduct $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());
            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $validated = $this->prepareImagePayload($validated);
            $result = $this->product_service->create($validated);

            return $this->successResponse($result, 200, 'Product is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function update(UpdateProduct $request, $id)
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
            $existing = $this->product_service->whereFirst('id', $id);
            if (!$existing) {
                return $this->errorResponse('Product not found', 404);
            }

            $validated = $request->validated();
            $validated = $this->prepareImagePayload($validated);
            $result = $this->product_service->update($id, $validated);

            if (!empty($validated['image_path']) && !empty($existing->image_path) && $validated['image_path'] !== $existing->image_path) {
                $this->deleteFile($existing->image_path);
            }

            return $this->successResponse($result, 200, 'Product is updated successfully');
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

            $data = $this->product_service->whereFirst('id', (int) $id);
            if ($data) {
                $this->product_service->toggleProductStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            }

            return $this->errorResponse('Product not found', 404);
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
            $data = $this->product_service->whereFirst('id', $id);
            if (!$data) {
                return $this->errorResponse('Product not found!', 404);
            }

            if ($this->product_service->delete($id)) {
                $this->deleteFile($data->image_path);
                return $this->successResponse([], 200, 'Product deleted successfully!');
            }

            return $this->errorResponse('Product not found!', 404);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    private function prepareImagePayload(array $attributes): array
    {
        if (!empty($attributes['image']) && $attributes['image'] instanceof UploadedFile) {
            $image = $attributes['image'];
            $fileName = uniqid('product_', true) . '.' . $image->getClientOriginalExtension();
            $path = $image->storeAs('product/images', $fileName, 'public');

            $attributes['image'] = $fileName;
            $attributes['image_path'] = $path;
            $attributes['image_url'] = Storage::disk('public')->url($path);
        } elseif (array_key_exists('image', $attributes) && empty($attributes['image'])) {
            unset($attributes['image']);
        }

        return $attributes;
    }

    private function deleteFile(?string $path): void
    {
        if (empty($path)) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
