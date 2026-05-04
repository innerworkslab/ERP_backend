<?php

namespace Modules\Stakeholder\app\Http\Controllers\SupplierType;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Modules\Stakeholder\app\Http\Requests\SupplierType\CreateRequest;
use Modules\Stakeholder\app\Http\Services\SupplierType\SupplierTypeService;

class SupplierTypeController extends Controller
{
    use ApiResponser;

    public function __construct(private SupplierTypeService $supplierTypeService)
    {
    }

    public function index()
    {
        try {
            $result = $this->supplierTypeService->list();

            return $this->successResponse($result, 200, 'Supplier type list');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function store(CreateRequest $request)
    {
        try {
            $result = $this->supplierTypeService->create($request->validated());

            return $this->successResponse($result, 201, 'Supplier type created successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
