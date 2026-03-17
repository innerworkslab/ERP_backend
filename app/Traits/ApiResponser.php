<?php

namespace App\Traits;

use Illuminate\Http\Response;

trait ApiResponser
{
    private $pagedJSON = [
        'response' => [
            'status' => '',
            'message' => '',
        ],
        'data' => [],
        'meta' => [],
    ];

    private $simpleJSON = [
        'response' => [
            'status' => '',
            'message' => '',
        ],
        'data' => [],
    ];

    /**
     * Building success response with normal data
     *
     * @param  int  $statusCode
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function successResponse($data = [], $statusCode = Response::HTTP_OK, $message = '')
    {
        $this->simpleJSON['response']['status'] = 'success';
        $this->simpleJSON['response']['message'] = $message;
        $this->simpleJSON['data'] = $data;

        return response()->json($this->simpleJSON, $statusCode);
    }

    /**
     * Building success response with custom paginated data
     *
     * @param  int  $statusCode
     * @param  string  $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginatedSuccessResponse($data = [], $statusCode = Response::HTTP_OK, $message = '')
    {
        $this->pagedJSON['response']['status'] = 'success';
        $this->pagedJSON['response']['message'] = $message;
        $this->pagedJSON['data'] = array_key_exists('data', $data) ? $data['data'] : $data;
        $this->pagedJSON['meta'] = array_key_exists('meta', $data) ? $data['meta'] : [];

        return response()->json($this->pagedJSON, $statusCode);
    }

    /**
     * Building success response with laravel default paginated data
     * @param $data
     * @param int $statusCode
     * @param string $message
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginateSuccessResponse($data, $statusCode = Response::HTTP_OK, $message = '')
    {
        $this->pagedJSON['response']['status'] = 'success';
        $this->pagedJSON['response']['message'] = '';
        $this->pagedJSON['data'] = $data->items();
        $this->pagedJSON['meta']['has_next_page'] = $data->nextPageUrl() != null;
        $this->pagedJSON['meta']['count'] = count($data);
        $this->pagedJSON['meta']['per_page'] = $data->perPage();
        $this->pagedJSON['meta']['total'] = $data->total();
        $this->pagedJSON['meta']['message'] = $message;
        $this->pagedJSON['links']['first'] = $data->url(1);
        $this->pagedJSON['links']['last'] = $data->url($data->lastPage());
        $this->pagedJSON['links']['prev'] = $data->previousPageUrl();
        $this->pagedJSON['links']['next'] = $data->nextPageUrl();
        return response()->json($this->pagedJSON, $statusCode);
    }

    /**
     * Building success response with  laravel default paginated data and additonal data
     * @param $paginate_data
     * @param $data
     * @param int $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function paginateSuccessResponseWithArrayData($paginate_data, $data = [], $statusCode = Response::HTTP_OK, $message = '')
    {
        $this->pagedJSON['response']['status'] = 'success';
        $this->pagedJSON['response']['message'] = '';
        $this->pagedJSON['data'] = $data;
        $this->pagedJSON['meta']['has_next_page'] = $paginate_data->nextPageUrl() != null;
        $this->pagedJSON['meta']['count'] = count($paginate_data);
        $this->pagedJSON['meta']['per_page'] = $paginate_data->perPage();
        $this->pagedJSON['meta']['total'] = $paginate_data->total();
        $this->pagedJSON['meta']['message'] = $message;
        $this->pagedJSON['links']['first'] = $paginate_data->url(1);
        $this->pagedJSON['links']['last'] = $paginate_data->url($paginate_data->lastPage());
        $this->pagedJSON['links']['prev'] = $paginate_data->previousPageUrl();
        $this->pagedJSON['links']['next'] = $paginate_data->nextPageUrl();
        return response()->json($this->pagedJSON, $statusCode);
    }

    /**
     * Building error response
     *
     * @param  string  $message
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function errorResponse($message, $statusCode)
    {
        $this->simpleJSON['response']['status'] = 'error';
        $this->simpleJSON['response']['message'] = $message;

        return response()->json($this->simpleJSON, $statusCode);
    }

    /**
     * Building error response for validation errors
     *
     * @param  \Illuminate\Contracts\Validation\Validator  $validator
     * @param  int  $statusCode
     * @return \Illuminate\Http\JsonResponse
     */
    public function validationErrorResponse($validator, $statusCode = Response::HTTP_UNPROCESSABLE_ENTITY)
    {
        $this->simpleJSON['response']['status'] = 'error';
        $this->simpleJSON['response']['message'] = 'Validation error';
        $this->simpleJSON['errors'] = $validator->errors()->toArray();

        return response()->json($this->simpleJSON, $statusCode);
    }

    public function paginateResponse($collection, $message = '')
    {
        $class = $collection->response()->getData();

        $this->simpleJSON['response']['status'] = 'success';
        $this->simpleJSON['response']['message'] = $message;

        $this->simpleJSON['data'] = $class->data;

        $this->simpleJSON['meta'] = $class->pagination;

        return response()->json($this->simpleJSON);
    }
}