<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Symfony\Component\Mime\Email;

class SparkPostHttpTransport extends AbstractTransport
{
    protected $apiKey;
    protected $fromEmail;
    protected $fromName;

    public function __construct($apiKey, $fromEmail, $fromName)
    {
        parent::__construct();
        $this->apiKey = $apiKey;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
    }

    protected function doSend(SentMessage $message): void
    {
        // Verify API key is set
        if (empty($this->apiKey)) {
            Log::error('SparkPost API key is empty');
            throw new \Exception('SparkPost API key is not configured');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        
        // Get recipients - SparkPost format
        $recipients = [];
        foreach ($email->getTo() as $address) {
            $recipients[] = [
                'address' => [
                    'email' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ]
            ];
        }

        // Get CC recipients
        foreach ($email->getCc() as $address) {
            $recipients[] = [
                'address' => [
                    'email' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ]
            ];
        }

        // Get BCC recipients
        foreach ($email->getBcc() as $address) {
            $recipients[] = [
                'address' => [
                    'email' => $address->getAddress(),
                    'name' => $address->getName() ?: null,
                ]
            ];
        }

        // Get reply-to
        $replyTo = null;
        if ($email->getReplyTo()) {
            $replyToAddress = $email->getReplyTo()[0];
            $replyTo = $replyToAddress->getAddress();
        }

        // Prepare payload
        $content = [
            'from' => [
                'email' => $this->fromEmail,
                'name' => $this->fromName,
            ],
            'subject' => $email->getSubject(),
        ];

        // Add HTML body if exists
        if ($email->getHtmlBody()) {
            $content['html'] = $email->getHtmlBody();
        }

        // Add text body if exists
        if ($email->getTextBody()) {
            $content['text'] = $email->getTextBody();
        }

        // Build payload according to SparkPost API format
        $payload = [
            'content' => $content,
            'recipients' => $recipients,
        ];

        // Add reply-to if exists
        if ($replyTo) {
            $payload['content']['reply_to'] = $replyTo;
        }

        // Log the payload for debugging (without API key)
        Log::info('SparkPost HTTP API Request', [
            'from' => $this->fromEmail,
            'to_count' => count($recipients),
            'subject' => $email->getSubject(),
            'has_html' => !empty($content['html']),
            'has_text' => !empty($content['text']),
        ]);

        // Send via SparkPost HTTP API
        // SparkPost API key should be sent as Authorization header (just the key, no prefix)
        $apiUrl = 'https://api.sparkpost.com/api/v1/transmissions';
        
        $response = Http::withHeaders([
            'Authorization' => $this->apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post($apiUrl, $payload);

        // Log response for debugging
        Log::info('SparkPost HTTP API Response', [
            'status' => $response->status(),
            'successful' => $response->successful(),
            'body' => $response->body(),
        ]);

        if (!$response->successful()) {
            $errorBody = $response->json();
            $errorMessage = 'Unknown error';
            
            if (isset($errorBody['errors']) && is_array($errorBody['errors']) && count($errorBody['errors']) > 0) {
                $errorMessage = $errorBody['errors'][0]['message'] ?? $errorBody['errors'][0]['description'] ?? 'Unknown error';
            } else {
                $errorMessage = $response->body();
            }
            
            Log::error('SparkPost API error', [
                'status' => $response->status(),
                'error' => $errorMessage,
                'full_response' => $response->body(),
            ]);
            
            throw new \Exception('SparkPost API error: ' . $errorMessage . ' (Status: ' . $response->status() . ')');
        }

        // Log success
        $responseBody = $response->json();
        Log::info('SparkPost email sent successfully', [
            'transmission_id' => $responseBody['results']['id'] ?? 'unknown',
            'total_accepted_recipients' => $responseBody['results']['total_accepted_recipients'] ?? 0,
        ]);
    }

    public function __toString(): string
    {
        return 'sparkpost-http';
    }
}

