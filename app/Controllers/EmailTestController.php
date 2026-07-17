<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\ResponseInterface;

final class EmailTestController extends BaseController
{
    public function send(): ResponseInterface
    {
        $email = service('email');

        $email->setFrom(
            (string) env('email.fromEmail'),
            (string) env('email.fromName', 'Sikh Anand Karaj')
        );

        $email->setTo('anantsinghkota@gmail.com');
        $email->setSubject('Sikh Anand Karaj Email Test');

        $email->setMessage(
            view('Emails/TestEmail', [
                'name' => 'Anant',
            ])
        );

        if (!$email->send()) {
            log_message(
                'error',
                'Email test failed: {debug}',
                [
                    'debug' => $email->printDebugger([
                        'headers',
                        'subject',
                    ]),
                ]
            );

            return $this->response
                ->setStatusCode(500)
                ->setBody('Email could not be sent. Check the application logs.');
        }

        return $this->response->setBody('Test email sent successfully.');
    }
}
