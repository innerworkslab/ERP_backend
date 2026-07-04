<?php

namespace Modules\Sale\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Sale\app\Http\Requests\DeliveryProvider\CreateRequest;
use Modules\Sale\app\Http\Requests\DeliveryProvider\ListingRequest;
use Modules\Sale\app\Http\Requests\DeliveryProvider\UpdateRequest;
use Modules\Sale\app\Http\Services\DeliveryProviderService;

class DeliveryProviderController extends Controller
{
    use ApiResponser;

    protected $delivery_provider_service;

    public function __construct(DeliveryProviderService $delivery_provider_service)
    {
        $this->delivery_provider_service = $delivery_provider_service;
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
            $status = $validated['status'] ?? null;

            if (!empty($validated['search'])) {
                $search = $validated['search'];
                $searches = [
                    'name' => $search,
                ];
            }

            $with = ['created_by', 'updated_by'];
            $res_data = $this->delivery_provider_service->getDataWithPagination(
                perPage: $per_page,
                page: $page,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Delivery Provider Lists');
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

            $data = $this->delivery_provider_service->find($id);

            if ($data) {
                return $this->successResponse($data, 200, 'delivery provider');
            }

            return $this->errorResponse('Delivery Provider not found', 404);
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
            $result = $this->delivery_provider_service->create($validated);

            return $this->successResponse($result, 200, 'Delivery Provider is created successfully');
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
            $data = $this->delivery_provider_service->find($id);

            if ($data) {
                $result = $this->delivery_provider_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Delivery Provider is updated successfully');
            }

            return $this->errorResponse('Delivery Provider not found', 404);
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

            $data = $this->delivery_provider_service->find($id);

            if ($data) {
                $this->delivery_provider_service->toggleDeliveryProviderStatus($data);
                return $this->successResponse([], 200, 'Toggle status successfully');
            }

            return $this->errorResponse('Delivery Provider not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
