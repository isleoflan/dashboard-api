<?php

declare(strict_types=1);

use IOL\Dashboard\v1\BitMasks\RequestMethod;
use IOL\Dashboard\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
);
$response->needsAuth(false);
$userId = $response->check();
$input = $response->getRequestData([
    [
        'name' => 'tid',
        'types' => ['string'],
        'required' => true,
        'errorCode' => 601101,
    ],
]);

$ticket = new \IOL\Dashboard\v1\Entity\Ticket();
try {
    $ticket->loadForHash($input['tid']);
} catch (\IOL\Dashboard\v1\Exceptions\IOLException){
    $response->addError(702001)->render();
}

$ticket->downloadTicket();