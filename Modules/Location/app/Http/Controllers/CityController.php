<?php

namespace Modules\Location\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Location\app\Http\Services\CityService;
use Modules\Location\app\Http\Requests\City\CreateRequest;
use Modules\Location\app\Http\Requests\City\ListingRequest;
use Modules\Location\app\Http\Requests\City\UpdateRequest;

class CityController extends Controller
{
    use ApiResponser;

    private $city_service;

    public function __construct(CityService $city_service)
    {
        $this->city_service = $city_service;
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
            $status = null;

            if (!empty($validated['search'])) {
                $search = $validated['search'];

                $searches = [
                    'name' => $search,
                ];

                // if (in_array(strtolower($search), ['active', 'inactive'])) {
                //     $searches = [];
                //     $status = $search;
                // }
            }

            $conditions['state_id'] = $validated['state_id'];

            $with = [];
            $res_data = $this->city_service->getDataWithPagination($per_page, $page, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $this->paginatedSuccessResponse($res_data, 200, 'City Lists By State');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
