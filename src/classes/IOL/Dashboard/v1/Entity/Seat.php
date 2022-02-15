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
use JetBrains\PhpStorm\ArrayShape;

class Seat
{
    public const DB_TABLE = 'seats';
    
    private string $seat;
    private ?string $userId;
    private ?string $squadId;

    /**
     * @throws NotFoundException
     * @throws InvalidValueException
     */
    public function __construct(?string $id = null)
    {
        if (!is_null($id)) {
            $this->loadData(Database::getRow('seat', $id, self::DB_TABLE));
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
    }

    #[ArrayShape(['userDetails' => "array|mixed", 'status' => "string"])]
    public function getAvailability(?string $userId, ?string $squadId, bool $squadReservation, array $userData): array
    {
        $returnData = ['userDetails' => []];
        if(!is_null($userId) && $userId === $this->userId){
            $returnData['status'] = SeatStatus::ME;
        } else if (!is_null($this->userId)) {
            $userData = $userData[$this->userId];
            if (!is_null($userData['squad']) && $userData['squad']['id'] === $squadId) {
                $returnData['status'] = SeatStatus::SQUAD; // seat is taken by a user of the same squad
            } else {
                $returnData['status'] = SeatStatus::TAKEN;
            }
            $returnData['userDetails'] = $userData;
        } else if ($squadReservation) { // user is part of a squad, that has a reservation
            if ($squadId === $this->squadId) { // this seat is part of the squads reservation
                $returnData['status'] = SeatStatus::AVAILABLE;
            } else {
                $returnData['status'] = SeatStatus::BLOCKED; // the seat is not part of the squad's reservation, so it is not available to the user
                $returnData['userDetails'] = [
                    'username' => 'Nicht in Squad-Reservation',
                    'avatar' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAQAAABIkb+zAAACM0lEQVR42u2aMU7DQBBFXwgUSKYJJS2i4AK5AhI99OESOQF0RgGSU0SigIpjQDgAiUQDlSuKSEuBLIHXJnZsdnbNzLSe5L/12rNfHtDQ0Gg6OvQZMuWRVwyGVx6ZMqRPx3/xe1ywwBTkggv2/BXfY8xHofg0PxjT81H+Ce8rxaf5zolf4jeZlBaf5oRNX+Rvc1dZvsFwx7Yfq3+/lnyD4d6HuzBZW/7XRhKO0wJhL9xwzD4REfscc8NLwZWnkvJ3ect92w/oWtd2GeR2iDd25QDGOYJuiQqvj7jNqRjLdV27bV2uOC50uMxpbULd+Txn9Vefdjo5d+FcQv4Gc2vvR6UqI+tZmLPhHqBvreNZ6dozq7bvHmBorWK3dG3XuntD9wDTjITrStXXmeqpe4CnjISjStVHmeon9wDZFnZQqfrAamfOI9sDdipV71i9wHlk3yOu6xVAARRAARTALcAhMc8ktSx8mUx4JuawWfFbXLH8c+nfc8kVW83Jf3AqPs2HphBGIvINhlEze38pBrBs4lmIxeQbDHF9gJkowKw+QCIKkMg3LPH/UwAFUAAFUAAFUAAFEPjB1ENX9bheANgeurzH9QCgyEOX87geAIxqeVxxgN88dBmPKw4Q1/S44gCzmh5XHCCp6XEV4N9voeAf4uBfo8E3shYcJYI/zLXgOK2OTAEUQAEUQAEUQAHaAhD8h+7gRw2CH/YIftwm+IGnFoycBT/099PjBjl2qaGhoaHR1vgEj5EY4FVkwfcAAAAASUVORK5CYII=',
                    'squad' => null
                ];
            }
        } else {
            if (!is_null($this->squadId)) { // this seat is part of the squads reservation
                $returnData['status'] = SeatStatus::BLOCKED;
                $returnData['userDetails'] = [
                    'username' => 'Reserviert',
                    'avatar' => 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAGAAAABgCAQAAABIkb+zAAACM0lEQVR42u2aMU7DQBBFXwgUSKYJJS2i4AK5AhI99OESOQF0RgGSU0SigIpjQDgAiUQDlSuKSEuBLIHXJnZsdnbNzLSe5L/12rNfHtDQ0Gg6OvQZMuWRVwyGVx6ZMqRPx3/xe1ywwBTkggv2/BXfY8xHofg0PxjT81H+Ce8rxaf5zolf4jeZlBaf5oRNX+Rvc1dZvsFwx7Yfq3+/lnyD4d6HuzBZW/7XRhKO0wJhL9xwzD4REfscc8NLwZWnkvJ3ect92w/oWtd2GeR2iDd25QDGOYJuiQqvj7jNqRjLdV27bV2uOC50uMxpbULd+Txn9Vefdjo5d+FcQv4Gc2vvR6UqI+tZmLPhHqBvreNZ6dozq7bvHmBorWK3dG3XuntD9wDTjITrStXXmeqpe4CnjISjStVHmeon9wDZFnZQqfrAamfOI9sDdipV71i9wHlk3yOu6xVAARRAARTALcAhMc8ktSx8mUx4JuawWfFbXLH8c+nfc8kVW83Jf3AqPs2HphBGIvINhlEze38pBrBs4lmIxeQbDHF9gJkowKw+QCIKkMg3LPH/UwAFUAAFUAAFUAAFEPjB1ENX9bheANgeurzH9QCgyEOX87geAIxqeVxxgN88dBmPKw4Q1/S44gCzmh5XHCCp6XEV4N9voeAf4uBfo8E3shYcJYI/zLXgOK2OTAEUQAEUQAEUQAHaAhD8h+7gRw2CH/YIftwm+IGnFoycBT/099PjBjl2qaGhoaHR1vgEj5EY4FVkwfcAAAAASUVORK5CYII=',
                    'squad' => null
                ];
            } else {
                $returnData['status'] = SeatStatus::AVAILABLE; // the seat is not part of the squad's reservation, so it is not available to the user
            }
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

    public function reserve(string $userId): ?int
    {
        $ssoClient = new Client(APIResponse::APP_TOKEN);
        $user = new User($ssoClient);
        $userInfo = $user->getUserInfo($userId);
        $squadId = is_null($userInfo['response']['data']['squad']) ? null : $userInfo['response']['data']['squad']['id'];

        $squadReservation = self::squadHasReservation($squadId);

        if($userId === $this->userId){
            // do nothing
            return null;
        }

        if (!is_null($this->userId)) {
            // seat is already taken
            return 701002;
        }

        if ($squadReservation) { // user is part of a squad, that has a reservation
            if ($squadId === $this->squadId) { // this seat is part of the squads reservation
                $this->doReserve($userId);
            } else {
                // the seat is not part of the squad's reservation, so it is not available to the user
                return 701003;
            }
        } else {
            $this->doReserve($userId);
        }
        return null;
    }

    private function doReserve(string $userId): void
    {
        $database = Database::getInstance();
        $database->where('user_id', $userId);
        $database->update(self::DB_TABLE, [
            'user_id' => null
        ]);

        $database->where('seat', $this->seat);
        $database->update(self::DB_TABLE, [
            'user_id' => $userId
        ]);
    }

    /**
     * @return string
     */
    public function getSeat(): string
    {
        return $this->seat;
    }
}