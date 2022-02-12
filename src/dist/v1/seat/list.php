<?php

declare(strict_types=1);

use IOL\Dashboard\v1\BitMasks\RequestMethod;
use IOL\Dashboard\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
);
$response->needsAuth(false);
$userId = $response->check(); // may be null, if user is not logged in

$seats = new \IOL\Dashboard\v1\Collections\Seats();
$seats->fetchAllForEvent(1001);
$response->setData($seats->getList($userId));