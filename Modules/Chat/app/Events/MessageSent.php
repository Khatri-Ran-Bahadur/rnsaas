<?php

namespace Modules\Chat\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Modules\Chat\Models\Message;

/**
 * Event broadcasted when a new chat message is created.
 * Uses ShouldBroadcastNow for immediate zero-latency dispatch via Reverb.
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message)
    {
        // Eager load sender and replyTo for serialized payload
        $this->message->loadMissing(['sender:id,name,avatar_path', 'replyTo.sender:id,name']);
    }

    /**
     * The channels the event should broadcast on.
     * Enforces Multi-tenant and Conversation-level security!
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->message->tenant_id}.conversation.{$this->message->conversation_id}"),
        ];
    }

    /**
     * The event's broadcast name (used in Echo: .listen('.message.sent'))
     */
    public function broadcastAs(): string
    {
        return 'message.sent';
    }

    /**
     * Data to broadcast over the WebSocket connection.
     * Keeps the payload lean and never exposes sensitive user data.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->message->id,
            'public_id' => $this->message->public_id,
            'tenant_id' => $this->message->tenant_id,
            'conversation_id' => $this->message->conversation_id,
            'body' => $this->message->body,
            'type' => $this->message->type,
            'attachments' => $this->message->attachments,
            'is_deleted' => $this->message->is_deleted,
            'edited_at' => $this->message->edited_at?->toISOString(),
            'reactions' => $this->message->reactions ?? [],
            'reply_to' => $this->message->replyTo ? [
                'id' => $this->message->replyTo->id,
                'body' => $this->message->replyTo->is_deleted ? 'This message was deleted' : $this->message->replyTo->body,
                'sender_name' => $this->message->replyTo->sender?->name ?? 'User',
            ] : null,
            'created_at' => $this->message->created_at?->toISOString(),
            'sender' => [
                'id' => $this->message->sender?->id,
                'name' => $this->message->sender?->name,
                'avatar_url' => $this->message->sender?->avatar_url,
            ],
        ];
    }
}
