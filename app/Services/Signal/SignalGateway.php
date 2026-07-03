<?php

namespace App\Services\Signal;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class SignalGateway
{
    public function __construct(
        protected string $apiUrl,
        protected string $botNumber,
    ) {}

    /**
     * Send a text message, optionally with attachments, to one or more recipients or a group.
     *
     * @param  array<int, string>  $recipients  Phone numbers or a single group id
     * @param  array<int, string>  $base64Attachments
     */
    public function sendMessage(array $recipients, string $message, array $base64Attachments = []): string
    {
        $response = $this->client()
            ->post('/v2/send', [
                'number' => $this->botNumber,
                'recipients' => $recipients,
                'message' => $message,
                'base64_attachments' => $base64Attachments,
            ])
            ->throw();

        return (string) $response->json('timestamp');
    }

    /**
     * @return array<int, array{id: string, name: string}>
     */
    public function listGroups(): array
    {
        $response = $this->client()->get("/v1/groups/{$this->botNumber}")->throw();

        return $response->json() ?? [];
    }

    /**
     * @param  array<int, string>  $members  Phone numbers to add, the bot number is added automatically.
     */
    public function createGroup(string $name, array $members, string $description = ''): string
    {
        // Groepen aanmaken kan, zeker de eerste keer, langer duren dan een
        // gewoon bericht versturen — ruimer timeout dan de client-default.
        $response = $this->client()
            ->timeout(60)
            ->post("/v1/groups/{$this->botNumber}", [
                'name' => $name,
                'description' => $description,
                'members' => array_values(array_unique([...$members, $this->botNumber])),
                'expiration_time' => 0,
                'group_link' => 'disabled',
                'permissions' => [
                    'add_members' => 'only-admins',
                    'edit_group' => 'only-admins',
                    'send_messages' => 'every-member',
                ],
            ])
            ->throw();

        return (string) $response->json('id');
    }

    /**
     * @param  array<int, string>  $members
     */
    public function addGroupMembers(string $groupId, array $members): void
    {
        $this->client()
            ->post("/v1/groups/{$this->botNumber}/{$groupId}/members", [
                'members' => $members,
            ])
            ->throw();
    }

    /**
     * The signal-cli-rest-api swagger spec marks every UpdateGroupRequest field as
     * required, so name/description/permissions are always sent alongside the
     * avatar rather than relying on the API accepting a partial body.
     */
    public function updateGroupAvatar(string $groupId, string $base64Avatar, string $name, string $description = ''): void
    {
        $this->client()
            ->put("/v1/groups/{$this->botNumber}/{$groupId}", [
                'base64_avatar' => $base64Avatar,
                'name' => $name,
                'description' => $description,
                'expiration_time' => 0,
                'group_link' => 'disabled',
                'permissions' => [
                    'add_members' => 'only-admins',
                    'edit_group' => 'only-admins',
                    'send_messages' => 'every-member',
                ],
            ])
            ->throw();
    }

    /**
     * Long-poll for new messages. Blocks on the server for up to $timeout seconds.
     * Safe to call in a tight loop from signal:listen.
     *
     * @return array<int, array<string, mixed>>
     */
    public function receive(int $timeout = 10): array
    {
        $response = $this->client()
            ->timeout($timeout + 10)
            ->retry(2, 500)
            ->get("/v1/receive/{$this->botNumber}", [
                'timeout' => $timeout,
            ])
            ->throw();

        return $response->json() ?? [];
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl($this->apiUrl)
            ->acceptJson()
            ->timeout(15)
            ->connectTimeout(10);
    }
}
