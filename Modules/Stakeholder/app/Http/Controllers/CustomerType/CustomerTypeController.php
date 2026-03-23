<?php

namespace Modules\Stakeholder\app\Http\Controllers\CustomerType;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Modules\Stakeholder\app\Http\Requests\CustomerType\CreateRequest;
use Modules\Stakeholder\app\Http\Services\CustomerType\CustomerTypeService;

class CustomerTypeController extends Controller
{
    use ApiResponser;

    public function __construct(private CustomerTypeService $customerTypeService)
    {
    }

    public function index()
    {
        try {
            $result = $this->customerTypeService->list();

            return $this->successResponse($result, 200, 'Customer type list');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->customerTypeService->create($request->validated());

            return $this->successResponse($result, 201, 'Customer type created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
