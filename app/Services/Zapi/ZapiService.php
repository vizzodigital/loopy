<?php

declare(strict_types = 1);

namespace App\Services\Zapi;

use Illuminate\Support\Facades\Http;

class ZapiService
{
    protected $baseUrl;

    protected $clientToken;

    public function __construct()
    {
        $this->baseUrl = config('services.zapi.base_url');
        $this->clientToken = config('services.zapi.secure');
    }

    /**
     * Sends a text message using the Z-API service.
     *
     * @param string $phone
     * @param string $message
     * @return array
     */
    public function sendText(
        string $phone,
        string $message
    ): array {
        $url = $this->baseUrl . 'send-text';

        $payload = [
            'phone' => $phone,
            'message' => $message,
            'delayMessage' => random_int(3, 9),
            'delayTyping' => random_int(3, 9),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Sends an audio message using the Z-API service.
     *
     * @param string $phone
     * @param string $audio
     * @return array
     */
    public function sendAudio(
        string $phone,
        string $audio
    ): array {
        $url = $this->baseUrl . 'send-audio';

        $payload = [
            'phone' => $phone,
            'audio' => $audio,
            'viewOnce' => false,
            'waveform' => true,
            'async' => true,
            'delayMessage' => random_int(3, 9),
            'delayTyping' => random_int(3, 9),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Sends an image message using the Z-API service.
     *
     * @param string $phone
     * @param string $image
     * @param string|null $caption
     * @param string|null $messageId
     * @return array
     */
    public function sendImage(
        string $phone,
        string $image,
        ?string $caption = null,
        ?string $messageId = null
    ): array {
        $url = $this->baseUrl . 'send-image';

        $payload = [
            'phone' => $phone,
            'image' => $image,
            'caption' => $caption,
            'messageId' => $messageId,
            'viewOnce' => false,
            'delayMessage' => random_int(3, 9),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Sends a video message using the Z-API service.
     *
     * @param string $phone
     * @param string $video
     * @param string|null $caption
     * @return array
     */
    public function sendVideo(
        string $phone,
        string $video,
        ?string $caption = null
    ): array {
        $url = $this->baseUrl . 'send-image';

        $payload = [
            'phone' => $phone,
            'video' => $video,
            'caption' => $caption,
            'viewOnce' => false,
            'async' => true,
            'delayMessage' => random_int(3, 9),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Sends a link message using the Z-API service.
     *
     * @param string $phone
     * @param string $message
     * @param string $image
     * @param string $linkUrl
     * @param string $title
     * @param string $linkDescription
     * @return array
     */
    public function sendLink(
        string $phone,
        string $message,
        string $image,
        string $linkUrl,
        string $title,
        string $linkDescription
    ): array {
        $url = $this->baseUrl . 'send-link';

        $payload = [
            'phone' => $phone,
            'message' => $message,
            'image' => $image,
            'linkUrl' => $linkUrl,
            'title' => $title,
            'linkDescription' => $linkDescription,
            'linkType' => 'LARGE',
            'delayMessage' => random_int(3, 9),
            'delayTyping' => random_int(3, 9),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Sends a reaction message using the Z-API service.
     *
     * @param string $phone
     * @param string $reaction
     * @param string $messageId
     * @return array
     */
    public function sendReaction(
        string $phone,
        string $reaction,
        string $messageId
    ): array {
        $url = $this->baseUrl . 'send-reaction';

        $payload = [
            'phone' => $phone, //120363327259048891-group disparador //120363345102304526-group radar
            'reaction' => $reaction, //✔
            'messageId' => $messageId,
            'delayMessage' => random_int(3, 9),
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Retrieve a list of all chats.
     *
     * @return array
     */
    public function getChats(): array
    {
        $url = $this->baseUrl . 'chats?page=1&pageSize=200';

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->get($url);

        return $response->json();
    }

    /**
     * Updates the description of a group.
     *
     * @param string $phone
     * @param string $description
     * @return array
     */
    public function updateGroupDescription(string $phone, string $description): array
    {
        $url = $this->baseUrl . 'update-group-description';

        $payload = [
            'groupId' => $phone,
            'groupDescription' => $description,
        ];

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->post($url, $payload);

        return $response->json();
    }

    /**
     * Deletes a message using the Z-API service.
     *
     * @param string $messageId The ID of the message to be deleted.
     * @param string $phone The phone number associated with the message.
     * @return void
     */
    public function deleteMessage(string $messageId, string $phone): void
    {
        $url = $this->baseUrl . 'messages?messageId=' . $messageId . '&phone=' . $phone . '&owner=true';

        Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->delete($url);
    }

    /**
     * Gets the metadata of a group.
     *
     * @param string $group The phone number associated with the group.
     * @return array The group metadata.
     */
    public function getGroupsMetadata(string $group)
    {
        $url = $this->baseUrl . 'group-metadata/' . $group;

        $response = Http::withHeaders([
            'Content-Type' => 'application/json',
            'Client-Token' => $this->clientToken,
        ])->get($url);

        return $response->json();
    }
}
