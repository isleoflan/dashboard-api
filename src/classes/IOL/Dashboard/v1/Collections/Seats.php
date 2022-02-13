<?php

namespace IOL\Dashboard\v1\Collections;

use IOL\Dashboard\v1\DataSource\Database;
use IOL\Dashboard\v1\Entity\Seat;
use IOL\Dashboard\v1\Exceptions\NotFoundException;
use IOL\Dashboard\v1\Request\APIResponse;
use IOL\SSO\SDK\Client;
use IOL\SSO\SDK\Service\User;

class Seats extends Collection
{

    /** @var array<Seat> $contents */

    /**
     * @param Seat $address
     */
    public function add(Seat $address): void
    {
        $this->contents[$this->key()] = $address;
        $this->next();
    }

    /**
     * @param int $eventId
     * @return void
     */
    public function fetchAllForEvent(int $eventId): void
    {
        $db = Database::getInstance();
        $db->where('event_id', $eventId);
        $data = $db->get(Seat::DB_TABLE);
        foreach ($data as $seatData){
            $seat = new Seat();
            try {
                $seat->loadData($seatData);
                $this->add($seat);
            } catch (NotFoundException) {}

        }
    }

    public function getList(?string $userId): array
    {
        $ssoClient = new Client(APIResponse::APP_TOKEN);
        $user = new User($ssoClient);
        $allUsers = $user->getList();
        $allUsers = $allUsers['response']['data'];
        if(!is_null($userId)) {
            $squadId = is_null($allUsers[$userId]['squad']) ? null : $allUsers[$userId]['squad']['id'];
        } else {
            $squadId = null;
        }



        $squadReservation = Seat::squadHasReservation($squadId);

        $return = [];

        foreach($this->contents as $seat){
            /** @var Seat $seat */

            $seatData = $seat->getAvailability($userId, $squadId, $squadReservation, $allUsers);

            $return[] = [
                'seat' => $seat->getSeat(),
                'availability' => $seatData['status'],
                'userData' => $seatData['userDetails']
            ];
        }

        return $return;
    }
}