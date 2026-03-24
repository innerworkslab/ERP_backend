<?php

namespace Modules\Organization\app\Http\Services;

use Exception;
use Illuminate\Support\Facades\DB;
use Modules\Organization\app\Http\Repositories\CurrencyRepository;

class CurrencyService
{
	protected $currency_repository;

	public function __construct(CurrencyRepository $currency_repository)
	{
		$this->currency_repository = $currency_repository;
	}

	public function getDataWithPagination(
		int $perPage = 10,
		int $page = 1,
		string $orderBy = 'created_at',
		array $searches = null,
		array $conditions = [],
		array $orConditions = [],
		array $with = [],
		?array $whereHas = null,
		?string $status = null
	) {
		try {
			$result = $this->currency_repository->getDataWithPagination(
				page: $page,
				perPage: $perPage,
				orderBy: $orderBy,
				status: $status,
				searches: $searches,
				with: $with,
				whereHas: $whereHas,
				conditions: $conditions,
				orConditions: $orConditions
			);

			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to fetch currency data with pagination: ' . $e->getMessage());
			throw $e;
		}
	}

	public function find(int $id)
	{
		try {
			$result = $this->currency_repository->find($id);
			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to fetch currency: ' . $e->getMessage());
			throw $e;
		}
	}

	public function create(array $attributes)
	{
		try {
			$result = DB::transaction(function () use ($attributes) {
				$currency = $this->currency_repository->create($attributes);

				$this->currency_repository->createRateHistory(
					(int) $currency->id,
					(float) $currency->exchange_rate,
					now()->toDateString()
				);

				return $currency;
			});

			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to create currency: ' . $e->getMessage());
			throw $e;
		}
	}

	public function update(int $id, array $attributes)
	{
		try {
			$result = DB::transaction(function () use ($id, $attributes) {
				$existingCurrency = $this->currency_repository->find($id);

				if (!$existingCurrency) {
					return null;
				}

				$updatedCurrency = $this->currency_repository->update($id, $attributes);

				if (!$updatedCurrency) {
					return null;
				}

				if (array_key_exists('exchange_rate', $attributes)) {
					$this->currency_repository->createRateHistory(
						(int) $updatedCurrency->id,
						(float) $updatedCurrency->exchange_rate,
						now()->toDateString()
					);
				}

				return $updatedCurrency;
			});

			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to update currency: ' . $e->getMessage());
			throw $e;
		}
	}

	public function delete(int $id)
	{
		try {
			$result = $this->currency_repository->delete($id);
			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to delete currency: ' . $e->getMessage());
			throw $e;
		}
	}

	public function whereFirst($column, $value)
	{
		try {
			$result = $this->currency_repository->whereFirst($column, $value);
			if (!$result) {
				return null;
			}
			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to find currency with whereFirst: ' . $e->getMessage());
			throw $e;
		}
	}

	public function updateExchangeRate(int $id, array $attributes)
	{
		try {
			$result = DB::transaction(function () use ($id, $attributes) {
				$existingCurrency = $this->currency_repository->find($id);

				if (!$existingCurrency) {
					return null;
				}

				$newRate = (float) $attributes['exchange_rate'];

				$updatePayload = [
					'exchange_rate' => $newRate,
					'last_exchange_rate_update' => now()->toDateString(),
				];

				$updatedCurrency = $this->currency_repository->update($id, $updatePayload);

				if (!$updatedCurrency) {
					return null;
				}

				$this->currency_repository->createRateHistory(
					(int) $updatedCurrency->id,
					$newRate,
					$updatePayload['last_exchange_rate_update']
				);

				return $updatedCurrency;
			});

			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to update currency exchange rate: ' . $e->getMessage());
			throw $e;
		}
	}

	public function exchangeRateHistory(int $currencyId, array $filters = []): ?array
	{
		try {
			$currency = $this->currency_repository->find($currencyId);

			if (!$currency) {
				return null;
			}

			return $this->currency_repository->getExchangeRateHistoryByCurrencyId($currencyId, $filters);
		} catch (Exception $e) {
			logger()->error('Error : Failed to fetch currency exchange rate history: ' . $e->getMessage());
			throw $e;
		}
	}

	public function setBaseCurrency(int $id)
	{
		try {
			$result = DB::transaction(function () use ($id) {
				$currency = $this->currency_repository->find($id);

				if (!$currency) {
					return null;
				}

				$this->currency_repository->clearBaseCurrencyFlags();
				$this->currency_repository->setAsBaseCurrency($id);

				return $this->currency_repository->find($id);
			});

			return $result;
		} catch (Exception $e) {
			logger()->error('Error : Failed to set base currency: ' . $e->getMessage());
			throw $e;
		}
	}
}
