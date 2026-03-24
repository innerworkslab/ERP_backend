<?php

namespace Modules\Organization\app\Http\Controllers;

use App\Traits\ApiResponser;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Modules\Organization\app\Http\Requests\Currency\CreateRequest;
use Modules\Organization\app\Http\Requests\Currency\ExchangeRateHistoryRequest;
use Modules\Organization\app\Http\Requests\Currency\ListingRequest;
use Modules\Organization\app\Http\Requests\Currency\UpdateExchangeRateRequest;
use Modules\Organization\app\Http\Requests\Currency\UpdateRequest;
use Modules\Organization\app\Http\Services\CurrencyService;

class CurrencyController extends Controller
{
    use ApiResponser;

    private $currency_service;

    public function __construct(CurrencyService $currency_service)
    {
        $this->currency_service = $currency_service;
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

            if (!empty($validated['search'])) {
                $search = $validated['search'];

                $searches = [
                    'name' => $search,
                    'code' => $search,
                    'symbol' => $search,
                ];
            }

            if (array_key_exists('is_base_currency', $validated)) {
                $conditions['is_base_currency'] = (bool) $validated['is_base_currency'];
            }

            $res_data = $this->currency_service->getDataWithPagination(
                $per_page,
                $page,
                searches: $searches,
                conditions: $conditions
            );

            return $this->paginatedSuccessResponse($res_data, 200, 'Currency Lists');
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

            $data = $this->currency_service->find($id);
            if ($data) {
                return $this->successResponse($data, 200, 'currency');
            }

            return $this->errorResponse('Currency not found', 404);
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
            $result = $this->currency_service->create($validated);

            return $this->successResponse($result, 200, 'Currency is created successfully');
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
            $data = $this->currency_service->whereFirst('id', $id);
            if ($data) {
                $result = $this->currency_service->update($id, $validated);

                return $this->successResponse($result, 200, 'Currency is updated successfully');
            }

            return $this->errorResponse('Currency not found', 404);
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

            $data = $this->currency_service->whereFirst('id', $id);
            if ($data) {
                if ($this->currency_service->delete($id)) {
                    return $this->successResponse([], 200, 'Currency deleted successfully!');
                }
            } else {
                return $this->errorResponse('Currency not found!', 404);
            }
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function updateExchangeRate(UpdateExchangeRateRequest $request, $id)
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
            $data = $this->currency_service->updateExchangeRate((int) $id, $validated);

            if (!$data) {
                return $this->errorResponse('Currency not found', 404);
            }

            return $this->successResponse($data, 200, 'Currency exchange rate updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function exchangeRateHistory(ExchangeRateHistoryRequest $request, $id)
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
            $result = $this->currency_service->exchangeRateHistory((int) $id, $validated);

            if (!$result) {
                return $this->errorResponse('Currency not found', 404);
            }

            return $this->paginatedSuccessResponse($result, 200, 'Currency exchange rate history');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function setBaseCurrency($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->currency_service->setBaseCurrency((int) $id);

            if (!$result) {
                return $this->errorResponse('Currency not found', 404);
            }

            return $this->successResponse($result, 200, 'Base currency updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
