<?php

declare(strict_types=1);

use IOL\Shop\v1\BitMasks\RequestMethod;
use IOL\Shop\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::PATCH)
);
$response->needsAuth(true);
$userID = $response->check();

$order = new \IOL\Shop\v1\Entity\Order();


$response->setData($order->getCounts());