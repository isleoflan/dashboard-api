<?php

declare(strict_types=1);

use IOL\Dashboard\v1\BitMasks\RequestMethod;
use IOL\Dashboard\v1\DataSource\Environment;
use IOL\Dashboard\v1\Entity\Onboarding;
use IOL\Dashboard\v1\Request\APIResponse;

$response = APIResponse::getInstance();

$response->setAllowedRequestMethods(
    new RequestMethod(RequestMethod::GET)
);
$response->needsAuth(true);
$userId = $response->check();

$onboarding = Onboarding::getOnboarding($userId);
$response->addData('steps', $onboarding);

if ($onboarding['ticketPayed']) {
    $ticket = new \IOL\Dashboard\v1\Entity\Ticket();
    $ticket->loadForUser($userId);

    $response->addData('ticket', [
        'download' => Environment::get('APP_URL') . 'ticket/download?tid=' . $ticket->getTicketHash(),
        'qr' => $ticket->getQRBase64()
    ]);
} else {
    $response->addData('ticket', null);
}