<?php

namespace Modules\Staff\app\Http\Services\Nrc;

use Exception;
use Illuminate\Support\Collection;
use Modules\Staff\app\Http\Repositories\Nrc\NrcRepository;

class NrcService
{
    public function __construct(private NrcRepository $nrcRepository)
    {
    }

    public function getTownshipsByNrcCode(int $nrcCode): Collection
    {
        try {
            return $this->nrcRepository->getTownshipsByNrcCode($nrcCode);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch NRC townships by code: ' . $e->getMessage());
            throw $e;
        }
    }
}
