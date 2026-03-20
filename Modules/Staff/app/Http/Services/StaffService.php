<?php

namespace Modules\Staff\app\Http\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Modules\Staff\app\Http\Repositories\StaffRepository;
use Modules\Staff\app\Models\StaffAuthorizedFeature;
use Modules\Staff\app\Models\StaffBankingInformation;
use Modules\Staff\app\Models\StaffEmploymentInformation;
use Modules\Staff\app\Models\StaffFeatureRecommendationRule;
use Modules\Staff\app\Models\StaffPersonalInformation;

class StaffService
{
    protected $staff_repository;

    public function __construct(StaffRepository $staff_repository)
    {
        $this->staff_repository = $staff_repository;
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
            return $this->staff_repository->getDataWithPagination(
                page: $page,
                perPage: $perPage,
                orderBy: $orderBy,
                status: $status,
                searches: $searches,
                with: $with,
                conditions: $conditions,
                orConditions: $orConditions,
                whereHas: $whereHas,
            );
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch staff list: ' . $e->getMessage());
            throw $e;
        }
    }

    public function find(int $id)
    {
        try {
            return $this->staff_repository->find($id);
        } catch (Exception $e) {
            logger()->error('Error : Failed to fetch staff: ' . $e->getMessage());
            throw $e;
        }
    }

    public function create(array $attributes)
    {
        DB::beginTransaction();
        try {
            $personalInformation = Arr::pull($attributes, 'personal_information', []);
            $personalInformation = $this->preparePersonalInformationImages($personalInformation);
            $employmentInformation = Arr::pull($attributes, 'employment_information', []);
            $bankingInformation = Arr::pull($attributes, 'banking_information', []);
            $authorizedFeatures = Arr::pull($attributes, 'authorized_features', []);

            $staff = $this->staff_repository->create($attributes);

            $this->syncAuthorizedFeatures(
                $staff->id,
                (int) $staff->role_id,
                $staff->department_id ? (int) $staff->department_id : null,
                $authorizedFeatures
            );

            $personalInformation['user_id'] = $staff->id;
            StaffPersonalInformation::updateOrCreate(
                ['user_id' => $staff->id],
                $personalInformation
            );

            if (!empty($employmentInformation)) {
                $employmentInformation['user_id'] = $staff->id;
                StaffEmploymentInformation::updateOrCreate(
                    ['user_id' => $staff->id],
                    $employmentInformation
                );
            }

            if (!empty($bankingInformation)) {
                $bankingInformation['user_id'] = $staff->id;
                StaffBankingInformation::updateOrCreate(
                    ['user_id' => $staff->id],
                    $bankingInformation
                );
            }

            DB::commit();

            return $this->staff_repository->find($staff->id);
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to create staff: ' . $e->getMessage());
            throw $e;
        }
    }

    public function update(int $id, array $attributes)
    {
        DB::beginTransaction();
        try {
            $staff = $this->staff_repository->find($id);
            if (!$staff) {
                DB::rollBack();
                return null;
            }

            $filesToDelete = [];
            $existingPersonalInformation = $staff->staffPersonalInformation;

            $personalInformation = Arr::pull($attributes, 'personal_information', []);
            $personalInformation = $this->preparePersonalInformationImages($personalInformation);

            if ($existingPersonalInformation) {
                if (
                    !empty($personalInformation['nrc_image_path']) &&
                    !empty($existingPersonalInformation->nrc_image_path) &&
                    $personalInformation['nrc_image_path'] !== $existingPersonalInformation->nrc_image_path
                ) {
                    $filesToDelete[] = $existingPersonalInformation->nrc_image_path;
                }

                if (
                    !empty($personalInformation['house_hold_information_image_path']) &&
                    !empty($existingPersonalInformation->house_hold_information_image_path) &&
                    $personalInformation['house_hold_information_image_path'] !== $existingPersonalInformation->house_hold_information_image_path
                ) {
                    $filesToDelete[] = $existingPersonalInformation->house_hold_information_image_path;
                }
            }

            $employmentInformation = Arr::pull($attributes, 'employment_information', []);
            $bankingInformation = Arr::pull($attributes, 'banking_information', []);
            $authorizedFeatures = Arr::pull($attributes, 'authorized_features', []);

            unset($attributes['password']);

            $this->staff_repository->update($id, $attributes);

            $roleId = (int) ($attributes['role_id'] ?? $staff->role_id);
            $departmentId = $attributes['department_id'] ?? $staff->department_id;

            $this->syncAuthorizedFeatures(
                $id,
                $roleId,
                $departmentId ? (int) $departmentId : null,
                $authorizedFeatures
            );

            $personalInformation['user_id'] = $id;
            StaffPersonalInformation::updateOrCreate(
                ['user_id' => $id],
                $personalInformation
            );

            if (!empty($employmentInformation)) {
                $employmentInformation['user_id'] = $id;
                StaffEmploymentInformation::updateOrCreate(
                    ['user_id' => $id],
                    $employmentInformation
                );
            }

            if (!empty($bankingInformation)) {
                $bankingInformation['user_id'] = $id;
                StaffBankingInformation::updateOrCreate(
                    ['user_id' => $id],
                    $bankingInformation
                );
            }

            DB::commit();

            $this->deleteFiles($filesToDelete);

            return $this->staff_repository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to update staff: ' . $e->getMessage());
            throw $e;
        }
    }

    public function delete(int $id)
    {
        DB::beginTransaction();
        try {
            $staff = $this->staff_repository->find($id);
            if (!$staff) {
                DB::rollBack();
                return false;
            }

            $filesToDelete = [];
            if (!empty($staff->staffPersonalInformation)) {
                if (!empty($staff->staffPersonalInformation->nrc_image_path)) {
                    $filesToDelete[] = $staff->staffPersonalInformation->nrc_image_path;
                }

                if (!empty($staff->staffPersonalInformation->house_hold_information_image_path)) {
                    $filesToDelete[] = $staff->staffPersonalInformation->house_hold_information_image_path;
                }
            }

            $deleted = $this->staff_repository->delete($id);
            DB::commit();

            if ($deleted) {
                $this->deleteFiles($filesToDelete);
            }

            return $deleted;
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to delete staff: ' . $e->getMessage());
            throw $e;
        }
    }

    public function changePassword(int $id, string $oldPassword, string $password)
    {
        DB::beginTransaction();
        try {
            $staff = $this->staff_repository->find($id);
            if (!$staff) {
                DB::rollBack();
                return null;
            }

            if (!Hash::check($oldPassword, $staff->password)) {
                DB::rollBack();
                return false;
            }

            $this->staff_repository->update($id, [
                'password' => $password,
            ]);

            DB::commit();

            return $this->staff_repository->find($id);
        } catch (Exception $e) {
            DB::rollBack();
            logger()->error('Error : Failed to change staff password: ' . $e->getMessage());
            throw $e;
        }
    }

    public function toggleStaffStatus($staff): void
    {
        $this->staff_repository->toggleActive($staff);
    }

    private function preparePersonalInformationImages(array $personalInformation): array
    {
        if (
            array_key_exists('nrc_image_path', $personalInformation) ||
            array_key_exists('nrc_image_url', $personalInformation) ||
            array_key_exists('house_hold_information_image_path', $personalInformation) ||
            array_key_exists('house_hold_information_image_url', $personalInformation)
        ) {
            throw ValidationException::withMessages([
                'personal_information' => [
                    'Do not send image path/url. Send image files only.',
                ],
            ]);
        }

        unset(
            $personalInformation['nrc_image_path'],
            $personalInformation['nrc_image_url'],
            $personalInformation['house_hold_information_image_path'],
            $personalInformation['house_hold_information_image_url']
        );

        if (!empty($personalInformation['nrc_image']) && $personalInformation['nrc_image'] instanceof UploadedFile) {
            $nrcFile = $personalInformation['nrc_image'];
            $nrcFileName = uniqid('nrc_', true) . '.' . $nrcFile->getClientOriginalExtension();
            $nrcPath = $nrcFile->storeAs('staff/nrc', $nrcFileName, 'public');

            $personalInformation['nrc_image'] = $nrcFileName;
            $personalInformation['nrc_image_path'] = $nrcPath;
            $personalInformation['nrc_image_url'] = Storage::disk('public')->url($nrcPath);
        } elseif (!empty($personalInformation['nrc_image'])) {
            throw ValidationException::withMessages([
                'personal_information.nrc_image' => ['The nrc image must be a file upload.'],
            ]);
        } elseif (array_key_exists('nrc_image', $personalInformation)) {
            unset($personalInformation['nrc_image']);
        }

        if (!empty($personalInformation['house_hold_information_image']) && $personalInformation['house_hold_information_image'] instanceof UploadedFile) {
            $householdFile = $personalInformation['house_hold_information_image'];
            $householdFileName = uniqid('household_', true) . '.' . $householdFile->getClientOriginalExtension();
            $householdPath = $householdFile->storeAs('staff/household', $householdFileName, 'public');

            $personalInformation['house_hold_information_image'] = $householdFileName;
            $personalInformation['house_hold_information_image_path'] = $householdPath;
            $personalInformation['house_hold_information_image_url'] = Storage::disk('public')->url($householdPath);
        } elseif (!empty($personalInformation['house_hold_information_image'])) {
            throw ValidationException::withMessages([
                'personal_information.house_hold_information_image' => ['The household information image must be a file upload.'],
            ]);
        } elseif (array_key_exists('house_hold_information_image', $personalInformation)) {
            unset($personalInformation['house_hold_information_image']);
        }
        return $personalInformation;
    }

    private function deleteFiles(array $paths): void
    {
        $uniquePaths = array_unique(array_filter($paths));

        foreach ($uniquePaths as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (Exception $e) {
                logger()->error('Error : Failed to delete file: ' . $path . ' - ' . $e->getMessage());
            }
        }
    }

    private function syncAuthorizedFeatures(int $staffId, int $roleId, ?int $departmentId = null, array $manualAuthorizedFeatures = []): void
    {
        $roleFeatureIds = DB::table('feature_role')
            ->where('role_id', $roleId)
            ->pluck('feature_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        $ruleQuery = StaffFeatureRecommendationRule::query()
            ->where('role_id', $roleId)
            ->where('status', 'active')
            ->where('is_default_recommended', true);

        if ($departmentId) {
            $ruleQuery->where('department_id', $departmentId);
        }

        $recommendationRules = $ruleQuery->get(['id', 'feature_id']);

        $recommendedFeatureIds = $recommendationRules
            ->pluck('feature_id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->toArray();

        $ruleByFeature = [];
        foreach ($recommendationRules as $rule) {
            $ruleByFeature[(int) $rule->feature_id] = (string) $rule->id;
        }

        $manualMap = [];
        foreach ($manualAuthorizedFeatures as $item) {
            if (!is_array($item) || !array_key_exists('feature_id', $item)) {
                continue;
            }

            $manualMap[(int) $item['feature_id']] = $item;
        }

        $manualFeatureIds = array_keys($manualMap);

        $featureIds = array_values(array_unique(array_merge($roleFeatureIds, $recommendedFeatureIds, $manualFeatureIds)));

        StaffAuthorizedFeature::where('staff_id', $staffId)->delete();

        foreach ($featureIds as $featureId) {
            $isRoleFeature = in_array($featureId, $roleFeatureIds);
            $manual = $manualMap[$featureId] ?? null;

            $isRecommended = !array_key_exists($featureId, $manualMap);
            $recommendedByRule = $isRoleFeature ? null : ($ruleByFeature[$featureId] ?? null);
            $accessType = $isRoleFeature ? 'role_auto' : 'recommended';
            $permissionLevel = $isRoleFeature ? 'full' : 'limited';

            if ($manual) {
                if (array_key_exists('recommended_by_rule', $manual)) {
                    $recommendedByRule = $manual['recommended_by_rule'];
                }

                if (!empty($manual['access_type'])) {
                    $accessType = $manual['access_type'];
                }

                if (!empty($manual['permission_level'])) {
                    $permissionLevel = $manual['permission_level'];
                }
            }

            StaffAuthorizedFeature::create([
                'staff_id' => $staffId,
                'feature_id' => $featureId,
                'is_recommended' => $isRecommended,
                'recommended_by_rule' => $recommendedByRule,
                'access_type' => $accessType,
                'permission_level' => $permissionLevel,
                'assigned_by' => auth()->id(),
                'assigned_date' => now(),
            ]);
        }
    }
}   
