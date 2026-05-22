<?php

namespace Modules\Accounting\app\Http\Repositories;

use Modules\Accounting\app\Http\Repositories\BaseRepo;
use Modules\Accounting\app\Models\Account;


class AccountRepository extends BaseRepo
{
    public function __construct(Account $model)
    {
        parent::__construct($model);
    }

    public function generateAccountCode(int $parentAccountId): string
    {
        $parent = Account::findOrFail($parentAccountId);

        $prefix = $parent->code . '-';

        /**
         * Find latest child code
         */
        $lastChild = Account::where('parent_account_id', $parent->id)
            ->where('code', 'LIKE', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        /**
         * First child
         */
        if (!$lastChild) {
            return $prefix . '00001';
        }

        /**
         * Get last sequence
         *
         * Example:
         * 2-1000-00025
         */
        $parts = explode('-', $lastChild->code);

        $lastSequence = (int) end($parts);

        $nextSequence = $lastSequence + 1;

        /**
         * Keep leading zeros until 99999
         */
        $formattedSequence = str_pad(
            $nextSequence,
            5,
            '0',
            STR_PAD_LEFT
        );

        return $prefix . $formattedSequence;
    }
}
