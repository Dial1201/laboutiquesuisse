<?php

namespace App\Classes;

use Mailjet\Client;
use Mailjet\Resources;

class Mail
{

    public function send(string $to_email, string $to_name, string $subject, string $content)
    {
        $mj = new Client($_ENV["API_KEY"], $_ENV["API_KEY_SECRET"], true, ['version' => 'v3.1']);

        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "issa.djehon@gmail.com",
                        'Name' => "La boutique suisse"
                    ],
                    'To' => [
                        [
                            'Email' => "$to_email",
                            'Name' => "$to_name"
                        ]
                    ],
                    'TemplateID' => 2872660,
                    'TemplateLanguage' => true,
                    'Subject' => $subject,
                    'Variables' => [
                        'content' => $content
                    ]
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success();
    }
}
