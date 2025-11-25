<?php

namespace App\Extensions\Chatbot\System\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatbotLeadHistory extends Model
{
    protected $table = 'ext_chatbot_lead_history';

    protected $fillable = [
        'customer_id',
        'user_id',
        'event_type',
        'field_name',
        'old_value',
        'new_value',
        'description',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public const EVENT_PRIORITY_CHANGED = 'priority_changed';

    public const EVENT_VALUE_CHANGED = 'value_changed';

    public const EVENT_NOTE_ADDED = 'note_added';

    public const EVENT_TAG_ADDED = 'tag_added';

    public const EVENT_TAG_REMOVED = 'tag_removed';

    public const EVENT_STATUS_CHANGED = 'status_changed';

    public const EVENT_FOLLOW_UP_SCHEDULED = 'follow_up_scheduled';

    public const EVENT_QUOTE_SENT = 'quote_sent';

    public const EVENT_CONVERSATION_STARTED = 'conversation_started';

    public const EVENT_CONVERSATION_CLOSED = 'conversation_closed';

    public function customer(): BelongsTo
    {
        return $this->belongsTo(ChatbotCustomer::class, 'customer_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function log(
        int $customerId,
        string $eventType,
        ?string $fieldName = null,
        mixed $oldValue = null,
        mixed $newValue = null,
        ?string $description = null,
        ?array $metadata = null,
        ?int $userId = null
    ): self {
        return self::create([
            'customer_id' => $customerId,
            'user_id' => $userId ?? auth()->id(),
            'event_type' => $eventType,
            'field_name' => $fieldName,
            'old_value' => is_array($oldValue) ? json_encode($oldValue) : $oldValue,
            'new_value' => is_array($newValue) ? json_encode($newValue) : $newValue,
            'description' => $description,
            'metadata' => $metadata,
        ]);
    }

    public function getEventIcon(): string
    {
        return match ($this->event_type) {
            self::EVENT_PRIORITY_CHANGED => 'tabler-flag',
            self::EVENT_VALUE_CHANGED => 'tabler-currency-dollar',
            self::EVENT_NOTE_ADDED => 'tabler-note',
            self::EVENT_TAG_ADDED => 'tabler-tag',
            self::EVENT_TAG_REMOVED => 'tabler-tag-off',
            self::EVENT_STATUS_CHANGED => 'tabler-toggle-left',
            self::EVENT_FOLLOW_UP_SCHEDULED => 'tabler-calendar-event',
            self::EVENT_QUOTE_SENT => 'tabler-file-invoice',
            self::EVENT_CONVERSATION_STARTED => 'tabler-message-plus',
            self::EVENT_CONVERSATION_CLOSED => 'tabler-message-off',
            default => 'tabler-activity',
        };
    }

    public function getEventColor(): string
    {
        return match ($this->event_type) {
            self::EVENT_PRIORITY_CHANGED => 'orange',
            self::EVENT_VALUE_CHANGED => 'green',
            self::EVENT_NOTE_ADDED => 'blue',
            self::EVENT_TAG_ADDED => 'purple',
            self::EVENT_TAG_REMOVED => 'gray',
            self::EVENT_STATUS_CHANGED => 'cyan',
            self::EVENT_FOLLOW_UP_SCHEDULED => 'indigo',
            self::EVENT_QUOTE_SENT => 'emerald',
            self::EVENT_CONVERSATION_STARTED => 'sky',
            self::EVENT_CONVERSATION_CLOSED => 'slate',
            default => 'gray',
        };
    }

    public function getEventLabel(): string
    {
        return match ($this->event_type) {
            self::EVENT_PRIORITY_CHANGED => __('Priority changed'),
            self::EVENT_VALUE_CHANGED => __('Lead value updated'),
            self::EVENT_NOTE_ADDED => __('Note added'),
            self::EVENT_TAG_ADDED => __('Tag added'),
            self::EVENT_TAG_REMOVED => __('Tag removed'),
            self::EVENT_STATUS_CHANGED => __('Status changed'),
            self::EVENT_FOLLOW_UP_SCHEDULED => __('Follow-up scheduled'),
            self::EVENT_QUOTE_SENT => __('Quote sent'),
            self::EVENT_CONVERSATION_STARTED => __('Conversation started'),
            self::EVENT_CONVERSATION_CLOSED => __('Conversation closed'),
            default => __('Activity'),
        };
    }
}

