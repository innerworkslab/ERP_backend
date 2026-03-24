<?php

namespace Modules\Organization\app\Http\Repositories;

use Illuminate\Support\Facades\DB;
use Modules\Organization\app\Models\Currency;

class CurrencyRepository extends BaseRepo
{
	public function __construct(Currency $model)
	{
		parent::__construct($model);
	}

	public function find($id)
	{
		return $this->model->find($id);
	}

	public function createRateHistory(int $currencyId, float $exchangeRate, ?string $rateDate = null): void
	{
		DB::table('currency_rate_histories')->insert([
			'currency_id' => $currencyId,
			'exchange_rate' => $exchangeRate,
			'rate_date' => $rateDate ?? now()->toDateString(),
			'created_at' => now(),
			'updated_at' => now(),
		]);
	}

	public function clearBaseCurrencyFlags(): void
	{
		$this->model->newQuery()->update([
			'is_base_currency' => false,
		]);
	}

	public function setAsBaseCurrency(int $id): bool
	{
		return (bool) $this->model->newQuery()->where('id', $id)->update([
			'is_base_currency' => true,
			'updated_at' => now(),
		]);
	}

	public function getExchangeRateHistoryByCurrencyId(int $currencyId, array $filters = []): array
	{
		$query = DB::table('currency_rate_histories')
			->where('currency_id', $currencyId)
			->orderByDesc('rate_date')
			->orderByDesc('id');

		if (!empty($filters['from_date'])) {
			$query->whereDate('rate_date', '>=', $filters['from_date']);
		}

		if (!empty($filters['to_date'])) {
			$query->whereDate('rate_date', '<=', $filters['to_date']);
		}

		$perPage = (int) ($filters['per_page'] ?? 20);
		$page = (int) ($filters['page'] ?? 1);
		$totalCount = (clone $query)->count();

		$results = $query
			->skip(($page - 1) * $perPage)
			->take($perPage)
			->get();

		return [
			'data' => $results,
			'meta' => [
				'total' => $totalCount,
				'per_page' => $perPage,
				'current_page' => $page,
				'total_pages' => (int) ceil($totalCount / $perPage),
			],
		];
	}
}
