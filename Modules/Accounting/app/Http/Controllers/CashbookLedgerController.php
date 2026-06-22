<?php

namespace Modules\Accounting\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Accounting\app\Http\Requests\CashbookLedger\ListingRequest;
use Modules\Accounting\app\Http\Services\CashbookLedgerService;

class CashbookLedgerController extends Controller
{
    use ApiResponser;

    private CashbookLedgerService $cashbook_ledger_service;

    public function __construct(CashbookLedgerService $cashbook_ledger_service)
    {
        $this->cashbook_ledger_service = $cashbook_ledger_service;
    }

    public function index(ListingRequest $request)
    {
        try {
            $validator = Validator::make($request->all(), $request->rules(), $request->messages());

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $validated = $request->validated();
            $result = $this->cashbook_ledger_service->getDailyStatement($validated);

            return $this->successResponse($result, 200, 'Cashbook Ledger Statement');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
