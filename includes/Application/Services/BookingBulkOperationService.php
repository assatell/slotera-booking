<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use WP_Error;

if (!defined('ABSPATH')) { exit; }

/**
 * Runs booking status operations over multiple IDs and returns a compact summary.
 */
final class BookingBulkOperationService
{
    private BookingService $bookings;

    public function __construct(?BookingService $bookings = null)
    {
        $this->bookings = $bookings ?: new BookingService();
    }

    /**
     * @param string $operation confirm|cancel|complete
     * @param int[] $ids
     * @return array{operation:string,total:int,success:int,failed:int,results:array<int,array{id:int,success:bool,error:string}>}
     */
    public function apply(string $operation, array $ids): array
    {
        $operation = sanitize_key($operation);
        $ids = $this->normalize_ids($ids);

        $summary = [
            'operation' => $operation,
            'total' => count($ids),
            'success' => 0,
            'failed' => 0,
            'results' => [],
        ];

        if (!in_array($operation, ['confirm', 'cancel', 'complete'], true)) {
            foreach ($ids as $id) {
                $summary['failed']++;
                $summary['results'][] = ['id' => $id, 'success' => false, 'error' => 'sltr_invalid_bulk_operation'];
            }
            return $summary;
        }

        foreach ($ids as $id) {
            $result = $this->run_one($operation, $id);
            if ($result === true) {
                $summary['success']++;
                $summary['results'][] = ['id' => $id, 'success' => true, 'error' => ''];
                continue;
            }

            $summary['failed']++;
            $summary['results'][] = [
                'id' => $id,
                'success' => false,
                'error' => is_wp_error($result) ? $result->get_error_code() : 'sltr_bulk_operation_failed',
            ];
        }

        return $summary;
    }

    /** @param int[] $ids */
    private function normalize_ids(array $ids): array
    {
        $ids = array_map('absint', $ids);
        $ids = array_values(array_unique(array_filter($ids, static fn (int $id): bool => $id > 0)));
        return $ids;
    }

    /** @return true|WP_Error|false */
    private function run_one(string $operation, int $id)
    {
        if ($operation === 'confirm') {
            return $this->bookings->confirm_booking($id);
        }
        if ($operation === 'cancel') {
            return $this->bookings->cancel_booking($id);
        }
        return $this->bookings->complete_booking($id);
    }
}
