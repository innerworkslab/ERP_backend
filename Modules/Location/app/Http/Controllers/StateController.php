<?php

namespace Modules\Location\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Modules\Location\app\Http\Services\StateService;
use Modules\Location\app\Http\Requests\State\CreateRequest;
use Modules\Location\app\Http\Requests\State\ListingRequest;
use Modules\Location\app\Http\Requests\State\UpdateRequest;

class StateController extends Controller
{
    use ApiResponser;

    private $state_service;

    public function __construct(StateService $state_service)
    {
        $this->state_service = $state_service;
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
            $res_data = $this->state_service->getDataWithPagination($per_page, $page, searches: $searches);
            return $this->paginatedSuccessResponse($res_data, 200, 'State Lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
