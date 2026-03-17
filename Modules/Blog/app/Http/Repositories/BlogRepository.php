<?php

namespace Modules\Blog\App\Http\Repositories;


use Modules\Blog\App\Models\Post;
use Modules\Blog\App\Http\Repositories\BaseRepo;


class BlogRepository extends BaseRepo
{
    public function __construct(Post $model)
    {
        parent::__construct($model);
    }
}
