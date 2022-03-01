<?php

namespace IOL\Dashboard\v1\Entity;

use IOL\Dashboard\v1\DataSource\Database;
use IOL\Dashboard\v1\DataSource\Environment;
use IOL\Dashboard\v1\Exceptions\NotFoundException;

class Ticket
{
    public const DB_TABLE = 'tickets';

    public string $id;
    public string $userId;

    /**
     * @throws NotFoundException
     */
    private function loadData(array|false $values): void
    {

        if (!$values || count($values) === 0) {
            throw new NotFoundException('Ticket could not be loaded');
        }

        $this->id = $values['id'];
        $this->userId = $values['user_id'];
    }

    public function loadForUser(string $userId): void
    {
        $database = Database::getInstance();
        $database->where('user_id', $userId);
        $ticketData = $database->get(self::DB_TABLE);
        $this->loadData($ticketData[0]);
    }
    public function loadForHash(string $hash): void
    {
        $database = Database::getInstance();
        $ticketData = $database->query('SELECT * FROM tickets WHERE MD5(CONCAT(id, "TX", user_id)) = "' . $hash . '"');
        $this->loadData($ticketData[0]);
    }

    public function getTicketHash(): string
    {
        return md5($this->id . 'TX' . $this->userId);
    }

    public function getTicketPath(): string
    {
        return Environment::get('GENERATED_CONTENT_PATH') . '/tickets/ticket-'.$this->id.'.pdf';
    }

    public function downloadTicket(): never
    {
        $fileContent = file_get_contents($this->getTicketPath());
        header("Content-Type: application/pdf");
        header("Content-Length: " . filesize($this->getTicketPath()));
        echo $fileContent;

        die;
    }

    public function getQRBase64(): string
    {
        $qrPath = Environment::get('GENERATED_CONTENT_PATH') .'/qr/ticket-'.$this->id.'.png';
        $type = pathinfo($qrPath, PATHINFO_EXTENSION);
        $data = file_get_contents($qrPath);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
}