<?php

namespace Modules\Staff\app\Http\Repositories;

use App\Models\User;
use Modules\Staff\app\Http\Repositories\BaseRepo;

class StaffRepository extends BaseRepo
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function find($id)
    {
        return $this->model
            ->with([
                'role',
                'branch',
                'department',
                'staffPersonalInformation',
                'staffEmploymentInformation',
                'staffBankingInformation',
            ])
            ->find($id);
    }

    public function toggleActive(User $staff): void
    {
        $staff->status = $staff->status === 'active' ? 'inactive' : 'active';
        $staff->save();
    }
}
