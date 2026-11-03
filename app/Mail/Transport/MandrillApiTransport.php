<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;

class MandrillApiTransport extends AbstractTransport
{
    public function __construct(
        private string $apiKey,
        private string $fromEmail = '',
        private string $fromName = ''
    ) {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        $email = MessageConverter::toEmail($message->getOriginalMessage());

        $to = [];
        foreach ($email->getTo() as $address) {
            $to[] = ['email' => $address->getAddress(), 'type' => 'to'];
        }

        $payload = [
            'key' => $this->apiKey,
            'message' => [
                'html'       => $email->getHtmlBody(),
                'text'       => $email->getTextBody(),
                'subject'    => $email->getSubject(),
                'from_email' => $this->fromEmail ?: ($email->getFrom()[0]?->getAddress() ?? ''),
                'from_name'  => $this->fromName,
                'to'         => $to,
                'attachments' => $this->buildAttachments($email),
            ],
        ];

        $response = Http::post('https://mandrillapp.com/api/1.0/messages/send', $payload);

        if (!$response->successful()) {
            throw new \RuntimeException('Mandrill API error (' . $response->status() . '): ' . $response->body());
        }

        $results = $response->json();
        foreach ((array) $results as $result) {
            if (isset($result['status']) && in_array($result['status'], ['rejected', 'invalid'])) {
                throw new \RuntimeException(
                    'Mandrill rejected email to ' . ($result['email'] ?? '?') . ': ' . ($result['reject_reason'] ?? 'unknown')
                );
            }
        }
    }

    private function buildAttachments($email): array
    {
        $attachments = [];
        foreach ($email->getAttachments() as $attachment) {
            $attachments[] = [
                'type'    => $attachment->getMediaType() . '/' . $attachment->getMediaSubtype(),
                'name'    => $attachment->getFilename(),
                'content' => base64_encode($attachment->getBody()),
            ];
        }
        return $attachments;
    }

    public function __toString(): string
    {
        return 'mandrill-api';
    }
}
