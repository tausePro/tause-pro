<?php

namespace App\Extensions\MarketingBot\System\Services\Whatsapp;

use App\Extensions\MarketingBot\System\Enums\CampaignStatus;
use App\Extensions\MarketingBot\System\Models\MarketingConversation;
use App\Extensions\MarketingBot\System\Models\MarketingMessageHistory;
use App\Extensions\MarketingBot\System\Models\Whatsapp\ContactList;
use App\Extensions\MarketingBot\System\Models\Whatsapp\WhatsappChannel;
use App\Extensions\MarketingBot\System\Services\Common\Traits\HasMarketingCampaign;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Twilio\Rest\Client;

class WhatsappSenderService
{
    use HasMarketingCampaign;

    public ?WhatsappChannel $whatsappChannel = null;

    public function setWhatsappChannel($id): self
    {
        $this->whatsappChannel = WhatsappChannel::query()
            ->where('user_id', $id)
            ->first();

        return $this;
    }

    public function send(): void
    {
        $marketingCampaign = $this->getMarketingCampaign()->refresh();

        if ($marketingCampaign === CampaignStatus::running) {
            return;
        }

        $marketingCampaign->update(['status' =>  CampaignStatus::running->value]);

        $this->setWhatsappChannel($marketingCampaign->getAttribute('user_id'));

        if (! $this->whatsappChannel) {
            throw new Exception('Whatsapp channel not found for user ID: ' . $marketingCampaign->getAttribute('user_id'));
        }

        $contacts = $marketingCampaign->getAttribute('contacts');

        $segments = $marketingCampaign->getAttribute('segments');

        $contactList = ContactList::query()
            ->whereHas('contacts', function ($query) use ($contacts) {
                $query->whereIn('contact_id', $contacts);
            })
            ->when($segments, function ($query) use ($segments) {
                $query->whereHas('segments', function ($query) use ($segments) {
                    $query->whereIn('segment_id', $segments);
                });
            })
            ->select('phone')
            ->get();

        // Here you would implement the logic to send the campaign to the contacts
        // For example, sending messages via Whatsapp API

        if ($contactList->count()) {
            foreach ($contactList as $contact) {
                // Send message to each contact
                $this->sendMessageToContact($contact, $marketingCampaign->getAttribute('content'), $marketingCampaign);
            }
        }

        // After sending, you might want to update the status of the campaign
        $marketingCampaign->update(['status' => CampaignStatus::published]);
    }

    public function sendMessageToContact(ContactList $contactList, string $content, $marketingCampaign): void
    {
        try {

            $this->sendText($contactList->phone, $content, $marketingCampaign->getAttribute('image') ?: null);

            $conversation = $this->updateOrCreateMarketingConversation($contactList->phone);

            MarketingMessageHistory::query()->create([
                'conversation_id' => $conversation->getKey(),
                'message_id'      => random_int(100000000, 999999999),
                'model'           => null,
                'role'            => 'user',
                'message'         => $content,
                'media_url'       => $marketingCampaign->getAttribute('image'),
                'type'            => 'default',
                'message_type'    => 'text',
                'content_type'    => 'text',
                'created_at'      => now(),
            ]);
        } catch (Exception $exception) {
        }
    }

    public function updateOrCreateMarketingConversation($phone): Model|Builder
    {
        $from = $this->whatsappChannel->isSandbox()
            ? $this->whatsappChannel->whatsapp_sandbox_phone
            : $this->whatsappChannel->whatsapp_phone;

        return MarketingConversation::query()
            ->firstOrCreate([
                'user_id'             => $this->whatsappChannel->getAttribute('user_id'),
                'type'                => 'whatsapp',
                'session_id'          => Str::replaceFirst('+', '', $phone),
                'whatsapp_channel_id' => $this->whatsappChannel->getKey(),
            ], [
                'conversation_name' => Str::replaceFirst('+', '', $phone),
                'customer_payload'  => [
                    'AccountSid' => Str::replaceFirst('+', '', $phone),
                    'From'       => $from,
                ],
            ]);
    }

    public function sendText($receiver, $message, $mediaUrl = null): array
    {
        $client = $this->client();

        $from = $this->whatsappChannel->isSandbox()
            ? $this->whatsappChannel->whatsapp_sandbox_phone
            : $this->whatsappChannel->whatsapp_phone;

        try {

            $receiver = $this->receiverCheck($receiver);

            $data = [
                'from' => 'whatsapp:' . $from,
                'body' => $message,
            ];

            if ($mediaUrl) {
                $data['mediaUrl'] = url($mediaUrl);
            }

            $message = $client->messages->create(
                $receiver,
                $data
            );

            return [
                'properties' => $this->properties($message),
                'message'    => trans('Message sent'),
                'status'     => true,
            ];
        } catch (Exception $exception) {
            return [
                'message' => $exception->getMessage(),
                'status'  => false,
            ];
        }
    }

    public function receiverCheck($receiver)
    {
        $cleaned = preg_replace('/[\s-]+/', '', $receiver);

        if (strpos($cleaned, '+') === 0) {
            return 'whatsapp:' . $cleaned;
        }

        return 'whatsapp:+' . $cleaned;
    }

    public function properties($message): array
    {
        return [
            'body'                => $message->__get('body'),
            'numSegments'         => $message->__get('numSegments'),
            'direction'           => $message->__get('direction'),
            'from'                => $message->__get('from'),
            'to'                  => $message->__get('to'),
            'dateUpdated'         => $message->__get('dateUpdated'),
            'price'               => $message->__get('price'),
            'errorMessage'        => $message->__get('errorMessage'),
            'uri'                 => $message->__get('uri'),
            'accountSid'          => $message->__get('accountSid'),
            'numMedia'            => $message->__get('numMedia'),
            'status'              => $message->__get('status'),
            'messagingServiceSid' => $message->__get('messagingServiceSid'),
            'sid'                 => $message->__get('sid'),
            'dateSent'            => $message->__get('dateSent'),
            'dateCreated'         => $message->__get('dateCreated'),
            'errorCode'           => $message->__get('errorCode'),
            'priceUnit'           => $message->__get('priceUnit'),
            'apiVersion'          => $message->__get('apiVersion'),
            'subresourceUris'     => $message->__get('subresourceUris'),
        ];
    }

    public function client(): Client
    {
        $username = $this->whatsappChannel->whatsapp_sid;
        $password = $this->whatsappChannel->whatsapp_token;

        return new Client($username, $password);
    }
}
