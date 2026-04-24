<?php

namespace Modules\Inventory\app\Http\Services;

use Exception;
use Modules\Inventory\app\Models\UnitOfMeasurementConversion;

class UOMConversionService
{
    /**
     * Find the conversion rule between two UOMs where baseUnitId is the base unit
     * 
     * @param int $baseUnitId The base unit ID
     * @param int $conversionUnitId The conversion unit ID
     * @return UnitOfMeasurementConversion|null
     */
    public function findConversionRule(int $baseUnitId, int $conversionUnitId): ?UnitOfMeasurementConversion
    {
        try {
            return UnitOfMeasurementConversion::where('base_unit_id', $baseUnitId)
                ->where('conversion_unit_id', $conversionUnitId)
                ->where('status', 'active')
                ->first();
        } catch (Exception $e) {
            logger()->error('Error finding UOM conversion rule: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Convert a quantity from one UOM to another (base UOM)
     * 
     * Example: Product stock UOM is Liter (L)
     *          Conversion: 1L = 1000ML (conversion_rate = 1000)
     *          Input: quantity=500, fromUomId=ML, baseUomId=L
     *          Output: 500 / 1000 = 0.5 (in Liter)
     * 
     * @param float $quantity The quantity to convert
     * @param int $fromUomId The source UOM ID
     * @param int $baseUomId The base UOM ID (product's stock UOM)
     * @return float The converted quantity in base UOM
     * @throws Exception If conversion rule not found
     */
    public function convertToBaseUom(float $quantity, int $fromUomId, int $baseUomId): float
    {
        try {
            // If the source UOM is the same as base UOM, no conversion needed
            if ($fromUomId === $baseUomId) {
                return $quantity;
            }

            // Find the conversion rule
            $conversion = $this->findConversionRule($baseUomId, $fromUomId);

            if (!$conversion) {
                throw new Exception(
                    "No conversion rule found between UOM ID {$baseUomId} (base) and UOM ID {$fromUomId}"
                );
            }

            // Convert quantity: quantity_in_base = quantity / conversion_rate
            // Example: 500ML / 1000 = 0.5L
            $convertedQuantity = $quantity / (float) $conversion->conversion_rate;

            return $convertedQuantity;
        } catch (Exception $e) {
            logger()->error('Error converting quantity to base UOM: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Check if a conversion rule exists between a conversion UOM and base UOM
     * 
     * @param int $baseUomId The base UOM ID
     * @param int $conversionUomId The conversion UOM ID
     * @return bool
     */
    public function hasConversionRule(int $baseUomId, int $conversionUomId): bool
    {
        try {
            if ($baseUomId === $conversionUomId) {
                return true; // Same UOM, no conversion needed
            }

            return UnitOfMeasurementConversion::where('base_unit_id', $baseUomId)
                ->where('conversion_unit_id', $conversionUomId)
                ->where('status', 'active')
                ->exists();
        } catch (Exception $e) {
            logger()->error('Error checking UOM conversion rule existence: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Get the conversion rate between two UOMs (base to conversion)
     * 
     * @param int $baseUomId The base UOM ID
     * @param int $conversionUomId The conversion UOM ID
     * @return float|null The conversion rate or null if no rule found
     */
    public function getConversionRate(int $baseUomId, int $conversionUomId): ?float
    {
        try {
            if ($baseUomId === $conversionUomId) {
                return 1.0;
            }

            $conversion = $this->findConversionRule($baseUomId, $conversionUomId);
            
            return $conversion ? (float) $conversion->conversion_rate : null;
        } catch (Exception $e) {
            logger()->error('Error getting conversion rate: ' . $e->getMessage());
            throw $e;
        }
    }
}
