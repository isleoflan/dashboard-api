<?php

namespace IOL\Dashboard\v1\Entity;

use IOL\Dashboard\v1\DataSource\Database;
use IOL\Dashboard\v1\DataType\UUID;
use IOL\Dashboard\v1\Enums\SeatStatus;
use IOL\Dashboard\v1\Exceptions\InvalidValueException;
use IOL\Dashboard\v1\Exceptions\NotFoundException;
use IOL\Dashboard\v1\Request\APIResponse;
use IOL\SSO\SDK\Client;
use IOL\SSO\SDK\Service\User;
use JetBrains\PhpStorm\ArrayShape;

class Seat
{
    public const DB_TABLE = 'seats';
    
    private string $seat;
    private ?string $userId;
    private ?string $squadId;
    private int $eventId;

    /**
     * @throws NotFoundException
     * @throws InvalidValueException
     */
    public function __construct(?string $id = null)
    {
        if (!is_null($id)) {
            if (!UUID::isValid($id)) {
                throw new InvalidValueException('Invalid Order ID');
            }
            $this->loadData(Database::getRow('id', $id, self::DB_TABLE));
        }
    }

    /**
     * @throws NotFoundException
     */
    public function loadData(array|false $values): void
    {

        if (!$values || count($values) === 0) {
            throw new NotFoundException('Seat could not be loaded');
        }

        $this->seat = $values['seat'];
        $this->userId = $values['user_id'];
        $this->squadId = $values['squad_id'];
        $this->eventId = $values['event_id'];
    }

    #[ArrayShape(['userDetails' => "array|mixed", 'status' => "string"])]
    public function getAvailability(?string $userId, ?string $squadId, bool $squadReservation, array $userData): array
    {
        $returnData = ['userDetails' => []];
        if(!is_null($userId) && $userId === $this->userId){
            $returnData['status'] = SeatStatus::ME;
        } else if (!is_null($this->userId)) {
            $userData = $userData[$this->userId];
            if (!is_null($userId)) {
                if (!is_null($userData['squad']) && $userData['squad']['id'] === $squadId) {
                    $returnData['status'] = SeatStatus::SQUAD; // seat is taken by a user of the same squad
                } else {
                    $returnData['status'] = SeatStatus::TAKEN;
                }
                $returnData['userDetails'] = $userData;
            } else {
                $returnData['status'] = SeatStatus::AVAILABLE;
            }
        } else if ($squadReservation) { // user is part of a squad, that has a reservation
            if ($squadId === $this->squadId) { // this seat is part of the squads reservation
                $returnData['status'] = SeatStatus::AVAILABLE;
            } else {
                $returnData['status'] = SeatStatus::BLOCKED; // the seat is not part of the squad's reservation, so it is not available to the user
            }
        } else {
            $returnData['status'] = SeatStatus::AVAILABLE;
        }
        return $returnData;
    }

    public static function squadHasReservation(?string $squadId): bool
    {
        if(is_null($squadId)){ return false; }
        $database = Database::getInstance();
        $database->where('squad_id', $squadId);
        $data = $database->get(self::DB_TABLE);

        return isset($data[0]['squad_id']);
    }

    /**
     * @return string
     */
    public function getSeat(): string
    {
        return $this->seat;
    }
}