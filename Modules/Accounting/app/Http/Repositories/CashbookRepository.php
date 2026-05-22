<?php

namespace Modules\Accounting\app\Http\Repositories;

use Modules\Accounting\app\Http\Repositories\BaseRepo;
use Modules\Accounting\app\Models\Account;
use Modules\Accounting\app\Models\Cashbook;


class CashbookRepository extends BaseRepo
{
    protected $account_repository;

    public function __construct(Cashbook $model, AccountRepository $account_repository)
    {
        parent::__construct($model);
        $this->account_repository = $account_repository;
    }

    public function toggleActive(Cashbook $cashbook)
    {
        $cashbook->updated_by = auth()->user()->id;
        if ($cashbook->status == 'active') {
            $cashbook->status = 'inactive';
        } else {
            $cashbook->status = 'active';
        }
        $cashbook->save();
    }

    public function find($id)
    {
        $data = $this->model->find($id);
        if ($data) {
            $data->load(['created_by', 'updated_by', 'branch', 'account', 'currency']);
        }
        return $data;
    }

    public function create($data)
    {
        $parent_account = Account::where('code', '2-1001')->first();
        $code = $this->account_repository->generateAccountCode($parent_account->id);

        $account = $this->account_repository->create([
            'parent_account_id' => $parent_account->id,
            'code' => $code,
            'name' => $data['name'],
            'type' => 'Cash',
            'division' => 'SOFP',
            'description' => 'Auto generated from cashbook',
            'is_active' => true,
        ]);
        $data['account_id'] = $account->id;

        return $this->model->create($data);
    }

    public function update($id, $data)
    {
        $cashbook = $this->model->find($id);
        $cashbook->update($data);
        return $cashbook;
    }
}
