<?php

namespace IOL\Dashboard\v1\Entity;

use AndrewSvirin\Ebics\Models\Data;
use IOL\Dashboard\v1\DataSource\Database;
use IOL\Dashboard\v1\Enums\SeatStatus;
use IOL\Dashboard\v1\Exceptions\NotFoundException;
use IOL\Dashboard\v1\Request\APIResponse;
use JetBrains\PhpStorm\ArrayShape;

class Onboarding
{
    #[ArrayShape(['hasOrder' => "bool", 'ticketPayed' => "bool", 'hasSeat' => "bool"])]
    public static function getOnboarding(string $userId): array
    {
        $hasOrder = $hasTicket = $hasSeat = false;

        $database = Database::getInstance();

        $database->where('user_id', $userId);
        $orderData = $database->get('orders');

        if (isset($orderData[0])) {
            $hasOrder = true;
            if ($orderData[0]['status'] === 'FINISHED') {
                $hasTicket = true;

                if (Seat::userHasReservedSeat($userId)) {
                    $hasSeat = true;
                }
            }
        }

        return [
            'hasOrder' => $hasOrder,
            'ticketPayed' => $hasTicket,
            'hasSeat' => $hasSeat,
        ];
    }
}