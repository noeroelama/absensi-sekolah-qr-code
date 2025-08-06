<?php

namespace App\Libraries\Whatsapp\Wanesia;

use App\Libraries\Whatsapp\Whatsapp;

class Wanesia implements Whatsapp
{
    private string $urlApi = 'https://wanesia.com/api/send_express';

    public function __construct(public ?string $token) {}

    public function getProvider(): string
    {
        return 'Wanesia';
    }

    public function getToken(): string
    {
        return $this->token ?? env('WHATSAPP_TOKEN');
    }

    public function generateRandomString($length = 6) {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }


    /**
     * Send message to Wanesia API
     * @param array|string $messages
     * @return string
     */
    public function sendMessage(array|string $messages): string
    {
        // Logika untuk menangani input, baik array maupun string (meski tidak dipakai)
        if (is_string($messages)) {
            $messageData = json_decode($messages, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \InvalidArgumentException('Input string harus berupa JSON yang valid.');
            }
        } else {
            $messageData = $messages;
        }

        // Buat objek WanesiaMessage dari data array
        $footer = "\n\n\n================\nMsgID: " . $this->generateRandomString;
        $wanesiaMessage = new WanesiaMessage($messageData['destination'], $messageData['message'] . $footer, $messageData['delay'] ?? 0);

        $curl = curl_init();

        $sendTime = new \DateTime();
        if ($wanesiaMessage->delay > 0) {
            $sendTime->add(new \DateInterval("PT{$wanesiaMessage->delay}S"));
        }

        $postData = http_build_query([
            'token'   => $this->getToken(),
            'number'  => $wanesiaMessage->target,
            'message' => $wanesiaMessage->message,
            'date'    => $sendTime->format('Y-m-d'),
            'time'    => $sendTime->format('H-i-s'),
        ]);

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://wanesia.com/api/send_message',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $postData,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/x-www-form-urlencoded'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        try {
            $responseBody = json_decode($response, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($responseBody['result'])) {
                return $responseBody['message'] ?? 'Tidak ada pesan detail dari API.';
            }
            return "Respons tidak valid: " . $response;
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }
}
