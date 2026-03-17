<?php

namespace Modules\Blog\App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Illuminate\Http\Request;
use Modules\Blog\App\Http\Services\BlogService;

class BlogController extends Controller
{
    use ApiResponser;

    private $blog_service;

    public function __construct(BlogService $blog_service)
    {
        $this->blog_service = $blog_service;
    }
    public function index()
    {
        try {
            $res_data = $this->blog_service->getDataWithPagination();
            return $this->paginatedSuccessResponse($res_data, 200, 'Blog Lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }
}
