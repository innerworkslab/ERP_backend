<?php

namespace Modules\AccessControl\app\Http\Services;

use Exception;
use Modules\AccessControl\app\Http\Repositories\RoleRepository;

class RoleService
{
    protected $role_repository;

    public function __construct(RoleRepository $role_repository)
    {
        $this->role_repository = $role_repository;
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
            $result = $this->role_repository->getDataWithPagination(page: $page, perPage: $perPage, status: $status, searches: $searches, with: $with, conditions: $conditions);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch role data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->role_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch role: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        try {
            $attributes['created_by'] = auth()->user()->id;
            $result = $this->role_repository->create($attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to create role: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        try {
            $attributes['updated_by'] = auth()->user()->id;
            $result = $this->role_repository->update($id, $attributes);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to update role: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        try {
            $result = $this->role_repository->delete($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to delete role: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleRoleStatus($data)
    {
        $this->role_repository->toggleActive($data);
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->role_repository->whereFirst($column, $value);
            if (!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find role with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
