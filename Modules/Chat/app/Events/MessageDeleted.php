<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $tenantId,
        public int $conversationId,
        public int $messageId
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->tenantId}.conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.deleted';
    }

    public function broadcastWith(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'conversation_id' => $this->conversationId,
            'id' => $this->messageId,
            'is_deleted' => true,
            'body' => '🚫 This message was deleted',
            'attachments' => null,
        ];
    }
}
