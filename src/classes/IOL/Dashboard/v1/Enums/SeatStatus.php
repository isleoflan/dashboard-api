<?php

declare(strict_types=1);

namespace IOL\Dashboard\v1\Enums;

class SeatStatus extends Enum
{
    public const AVAILABLE = 'AVAILABLE'; // seat is available
    public const TAKEN = 'TAKEN'; // seat is taken and cannot be reserved
    public const ME = 'ME'; // the seat is currently taken by the user
    public const BLOCKED = 'BLOCKED'; // blocked seats (e.g. active reservation for other squads)
    public const SQUAD = 'SQUAD'; // members of users squad
}
