<?php

declare(strict_types=1);

use IOL\Dashboard\v1\BitMasks\RequestMethod;
use IOL\Dashboard\v1\Entity\Seat;
use IOL\Dashboard\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::PATCH)
);
$response->needsAuth(true);
$userId = $response->check();
$input = $response->getRequestData([
    [
        'name' => 'seat',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 601101,
    ],
]);

try{
    $seat = new Seat($input['seat']);
} catch(\IOL\Dashboard\v1\Exceptions\IOLException){
    $response->addError(701001)->render();
}

$errorId = $seat->reserve($userId);

if(!is_null($errorId)){
    $response->addError($errorId)->render();
}