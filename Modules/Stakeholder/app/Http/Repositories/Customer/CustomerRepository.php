<?php

namespace Modules\Stakeholder\app\Http\Repositories\Customer;

use Modules\Stakeholder\app\Http\Repositories\BaseRepo;
use Modules\Stakeholder\app\Models\Customer;

class CustomerRepository extends BaseRepo
{
    private const RELATIONS = ['customer_type', 'bank_accounts', 'state', 'city', 'branches', 'created_by', 'updated_by'];

    public function __construct(Customer $model)
    {
        parent::__construct($model);
    }

    public function queryWithRelations()
    {
        return $this->model->query()->with(self::RELATIONS);
    }

    public function syncBranches(Customer $customer, array $branchIds): void
    {
        $customer->branches()->sync($branchIds);
    }

    public function getNextRunningNumberByBranch(int $branchId, string $prefix): int
    {
        $baseCodePattern = '/^\\d+-' . preg_quote((string) $branchId, '/') . '-' . preg_quote($prefix, '/') . '-(\\d+)$/';

        $latestCode = (string) $this->model->query()
            ->select('customers.code')
            ->join('customer_branches', 'customer_branches.customer_id', '=', 'customers.id')
            ->where('customer_branches.branch_id', $branchId)
            ->latest('customers.id')
            ->lockForUpdate()
            ->value('customers.code');

        if ($latestCode !== '' && preg_match($baseCodePattern, $latestCode, $matches)) {
            return ((int) $matches[1]) + 1;
        }

        return 1;
    }
}
