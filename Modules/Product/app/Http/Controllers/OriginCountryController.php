<?php

namespace Modules\Product\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Product\app\Http\Requests\OriginCountry\CreateRequest;
use Modules\Product\app\Http\Requests\OriginCountry\ListingRequest;
use Modules\Product\app\Http\Requests\OriginCountry\UpdateRequest;
use Modules\Product\app\Http\Services\OriginCountryService;

class OriginCountryController extends Controller
{
    use ApiResponser;

    protected $origin_country_service;

    public function __construct(OriginCountryService $origin_country_service)
    {
        $this->origin_country_service = $origin_country_service;
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

            if (!empty($validated['search'])) {
                $searches = [
                    'name' => $validated['search'],
                ];
            }

            $res_data = $this->origin_country_service->getDataWithPagination(
                $per_page,
                $page,
                searches: $searches
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Origin country lists');
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

            $data = $this->origin_country_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'origin country');
            }

            return $this->errorResponse('Origin country not found', 404);
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
            $result = $this->origin_country_service->create($validated);

            return $this->successResponse($result, 200, 'Origin country is created successfully');
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

            $validated = $request->validated();
            $data = $this->origin_country_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->origin_country_service->update($id, $validated);
                return $this->successResponse($result, 200, 'Origin country is updated successfully');
            }

            return $this->errorResponse('Origin country not found', 404);
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

            $data = $this->origin_country_service->whereFirst('id', $id);
            if ($data) {
                if ($this->origin_country_service->delete($id)) {
                    return $this->successResponse([], 200, 'Origin country deleted successfully!');
                }
            }

            return $this->errorResponse('Origin country not found!', 404);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
