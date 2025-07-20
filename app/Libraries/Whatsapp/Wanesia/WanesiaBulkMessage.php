<?php

namespace App\Libraries\Whatsapp\Wanesia;

class WanesiaBulkMessage
{
    private array $messageWhatsapp = [];

    public function __construct(public array $messages)
    {
        foreach ($messages as $message) {
            array_push(
                $this->messageWhatsapp,
                (new WanesiaMessage($message['destination'], $message['message'], $message['delay'] ?? 2))
                    ->toArray()
            );
        }
    }

    public function toArray()
    {
        return $this->messageWhatsapp;
    }

    public function toJson()
    {
        return json_encode($this->toArray());
    }
}
