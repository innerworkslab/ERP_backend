<?php

namespace Modules\Sale\app\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Traits\ApiResponser;
use Modules\Sale\app\Http\Requests\DeliverNote\CreateRequest;
use Modules\Sale\app\Http\Requests\DeliverNote\ListingRequest;
use Modules\Sale\app\Http\Requests\DeliverNote\UpdateRequest;
use Modules\Sale\app\Http\Services\DeliverNoteService;

class DeliverNoteController extends Controller
{
    use ApiResponser;

    public function __construct(
        protected DeliverNoteService $deliver_note_service
    ) {
    }

    public function index(ListingRequest $request)
    {
        try {
            $validated = $request->validated();
            $perPage = $validated['per_page'] ?? 20;
            $page = $validated['page'] ?? 1;
            $searches = !empty($validated['search']) ? [$validated['search']] : [];
            $conditions = [
                'sale_invoice_id' => $validated['sale_invoice_id'] ?? null,
                'branch_id' => $validated['branch_id'] ?? null,
                'source_inventory_id' => $validated['source_inventory_id'] ?? null,
                'customer_id' => $validated['customer_id'] ?? null,
                'delivery_provider_id' => $validated['delivery_provider_id'] ?? null,
                'delivery_date_from' => $validated['delivery_date_from'] ?? null,
                'delivery_date_to' => $validated['delivery_date_to'] ?? null,
            ];
            $status = $validated['status'] ?? null;

            $result = $this->deliver_note_service->getDataWithPagination($perPage, $page, $searches, $conditions, $status);

            return $this->paginatedSuccessResponse($result, 200, 'Deliver Note Lists');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function findOrFail($id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $data = $this->deliver_note_service->find((int) $id);
            if ($data) {
                return $this->successResponse($data, 200, 'deliver note');
            }

            return $this->errorResponse('Deliver note not found', 404);
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse('Something went wrong!', 500);
        }
    }

    public function create(CreateRequest $request)
    {
        try {
            $result = $this->deliver_note_service->create($request->validated());

            return $this->successResponse($result, 200, 'Deliver note is created successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function update(UpdateRequest $request, $id)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->deliver_note_service->update((int) $id, $request->validated());

            if (($result['status'] ?? null) === 'not_found') {
                return $this->errorResponse('Deliver note not found', 404);
            }

            if (($result['status'] ?? null) === 'invalid_status') {
                return $this->errorResponse('Deliver note cannot be edited in its current status', 422);
            }

            return $this->successResponse($result['data'] ?? [], 200, 'Deliver note is updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function pending($id)
    {
        return $this->changeStatus($id, 'pending');
    }

    public function confirmed($id)
    {
        return $this->changeStatus($id, 'confirmed');
    }

    public function rejected($id)
    {
        return $this->changeStatus($id, 'rejected');
    }

    public function cancelled($id)
    {
        return $this->changeStatus($id, 'cancelled');
    }

    private function changeStatus($id, string $status)
    {
        try {
            if (!is_numeric($id)) {
                return $this->errorResponse('ID must be an integer!', 422);
            }

            $result = $this->deliver_note_service->changeStatus((int) $id, $status);

            if (($result['status'] ?? null) === 'not_found') {
                return $this->errorResponse('Deliver note not found', 404);
            }

            if (($result['status'] ?? null) === 'invalid_transition') {
                $currentStatus = $result['current_status'] ?? 'unknown';
                $targetStatus = $result['target_status'] ?? $status;
                $allowedStatuses = $result['allowed_statuses'] ?? [];
                $allowedText = !empty($allowedStatuses) ? implode(', ', $allowedStatuses) : 'no further statuses';

                return $this->errorResponse(
                    "Cannot change deliver note status from {$currentStatus} to {$targetStatus}. Allowed next status: {$allowedText}.",
                    422
                );
            }

            return $this->successResponse($result['data'] ?? [], 200, 'Deliver note status updated successfully');
        } catch (\Exception $e) {
            logger()->error($e);
            return $this->errorResponse($e->getMessage(), 422);
        }
    }
}
