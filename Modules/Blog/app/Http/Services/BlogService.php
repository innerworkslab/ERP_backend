<?php

namespace Modules\Blog\App\Http\Services;

use Exception;
use Modules\Blog\App\Http\Repositories\BlogRepository;

class BlogService
{
    protected $blog_repository;

    public function __construct(BlogRepository $blog_repository)
    {
        $this->blog_repository = $blog_repository;
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
        ?string $status = null)
    {
        try {
            $result = $this->blog_repository->getDataWithPagination(page: $page, perPage: $perPage, );
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch data with pagination: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            $result = $this->blog_repository->find($id);
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch data: ' . $e->getMessage());
            throw $e;
        }
    }

    public function whereFirst($column, $value)
    {
        try {
            $result = $this->blog_repository->whereFirst($column, $value);
            if(!$result) {
                return null;
            }
            return $result;
        } catch (Exception $e) {
            logger()->error('Error : Failed to find game with whereFirst: ' . $e->getMessage());
            throw $e;
        }
    }
}
