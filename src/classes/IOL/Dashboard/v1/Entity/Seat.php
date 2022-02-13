<?php

namespace IOL\Dashboard\v1\Entity;

use AndrewSvirin\Ebics\Models\Data;
use IOL\Dashboard\v1\DataSource\Database;
use IOL\Dashboard\v1\DataType\UUID;
use IOL\Dashboard\v1\Enums\SeatStatus;
use IOL\Dashboard\v1\Exceptions\InvalidValueException;
use IOL\Dashboard\v1\Exceptions\NotFoundException;
use IOL\Dashboard\v1\Request\APIResponse;
use IOL\SSO\SDK\Client;
use IOL\SSO\SDK\Service\User;

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

    public function getAvailability(?string $userId, ?string $squadId, bool $squadReservation = false): string
    {
        if(!is_null($userId) && $userId === $this->userId){ return SeatStatus::ME; }

        if(!is_null($this->userId)) {
            if(!is_null($userId)) {
                $ssoClient = new Client(APIResponse::APP_TOKEN);
                $ssoClient->setAccessToken(APIResponse::getAuthToken());
                $user = new User($ssoClient);
                $userData = $user->getUserInfo($this->userId);
                $userData = $userData['response']['data'];

                if (!is_null($userData['squad']) && $userData['squad']['id'] === $squadId) {
                    return SeatStatus::SQUAD; // seat is taken by a user of the same squad
                }
            }
            return SeatStatus::TAKEN;
        }

        if($squadReservation){ // user is part of a squad, that has a reservation
            if($squadId === $this->squadId) { // this seat is part of the squads reservation
                if(is_null($this->userId)){ // seat is in reservation of the users squad and not taken by any other user, so it's available
                    return SeatStatus::AVAILABLE;
                }

                return SeatStatus::TAKEN; // seat is taken, but from a user, that's not in the same squad
            }
            return SeatStatus::BLOCKED; // the seat is not part of the squad's reservation, so it is not available to the user
        }

        if(is_null($this->userId)){
            return SeatStatus::AVAILABLE; // no user has taken this seat, so it is available
        }

        return SeatStatus::TAKEN; // seat is taken, but from a user, that's not in the same squad
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