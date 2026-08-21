<?php

declare(strict_types=1);

namespace Slotera\Application\Services;

use Slotera\Application\Services\BookingModes\DateRangeInventoryBookingModeHandler;
use Slotera\Application\Services\BookingModes\FixedBookingModeHandler;
use Slotera\Application\Services\BookingModes\SimpleBookingModeHandler;

if (!defined('ABSPATH')) { exit; }

final class BookingModeRouter
{
    private FixedBookingModeHandler $fixed_handler;
    private SimpleBookingModeHandler $simple_handler;
    private DateRangeInventoryBookingModeHandler $date_range_inventory_handler;

    public function __construct(BookingCreationContext $context)
    {
        $this->fixed_handler = new FixedBookingModeHandler($context);
        $this->simple_handler = new SimpleBookingModeHandler($context);
        $this->date_range_inventory_handler = new DateRangeInventoryBookingModeHandler($context);
    }

    public function create(string $booking_mode, array $package, array $customer, array $data)
    {
        if ($booking_mode === 'simple') {
            return $this->simple_handler->create($package, $customer, $data);
        }
        if ($booking_mode === 'date_range_inventory') {
            return $this->date_range_inventory_handler->create($package, $customer, $data);
        }
        return $this->fixed_handler->create($package, $customer, $data);
    }
}
