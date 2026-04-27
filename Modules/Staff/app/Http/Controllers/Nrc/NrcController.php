<?php

namespace Modules\Staff\app\Http\Controllers\Nrc;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Support\Facades\Validator;
use Modules\Staff\app\Http\Services\Nrc\NrcService;

class NrcController extends Controller
{
    use ApiResponser;

    public function __construct(private NrcService $nrcService)
    {
    }

    public function getTownshipCodesByNrcCode($nrc_code)
    {
        try {
            $validator = Validator::make(
                ['nrc_code' => $nrc_code],
                ['nrc_code' => 'required|integer|between:1,14']
            );

            if ($validator->fails()) {
                return $this->validationErrorResponse($validator);
            }

            $townships = $this->nrcService->getTownshipsByNrcCode((int) $nrc_code);

            return $this->successResponse($townships, 200, 'NRC township codes fetched successfully');
        } catch (\Exception $e) {
            logger()->error($e);

            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
