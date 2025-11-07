<?php

namespace App\Extensions\Chatbot\System\Services;

use App\Extensions\Chatbot\System\Models\Chatbot;
use App\Extensions\Chatbot\System\Models\ChatbotAvatar;
use App\Extensions\Chatbot\System\Models\ChatbotConversation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ChatbotService
{
    public function agentConversations(array $chatbots, ?string $orderBy = null): Collection|array
    {
        $agentFilter = filter_var(request()->get('agentFilter', false), FILTER_VALIDATE_BOOLEAN);

        return ChatbotConversation::query()
            ->where('is_showed_on_history', true)
            ->with('chatbot:id,uuid,avatar,title')
            ->with(['histories.user:id,avatar', 'lastMessage'])
            ->when(($agentFilter == false), function (Builder $query) {
                $query->whereNull('connect_agent_at');
            }, function (Builder $query) {
                $query->whereNotNull('connect_agent_at');
            })
            ->whereIn('chatbot_id', $chatbots)
            ->orderBy('pinned', 'desc')
            ->when($orderBy, function (Builder $query) use ($orderBy) {
                $query->orderBy($orderBy ?: 'id', 'desc');
            })
            ->get();
    }

    public function unreadAgentMessagesCount(array $chatbots): int
    {
        return ChatbotConversation::query()
            ->whereNotNull('connect_agent_at')
            ->whereIn('chatbot_id', $chatbots)
            ->whereHas('histories', function (Builder $query) {
                $query->where('role', 'user')->where('read_at', null);
            })
            ->count();
    }

    public function unreadAiBotMessagesCount(array $chatbots): int
    {
        return ChatbotConversation::query()
            ->whereNull('connect_agent_at')
            ->whereIn('chatbot_id', $chatbots)
            ->whereHas('histories', function (Builder $query) {
                $query->where('role', 'user')->where('read_at', null);
            })
            ->count();
    }

    public function allMessagesCount(array $chatbots): int
    {
        return ChatbotConversation::query()
            ->whereIn('chatbot_id', $chatbots)
            ->whereHas('histories', function (Builder $query) {
                $query->where('role', 'user');
            })
            ->count();
    }

    public function historyConversationsWithPaginate(
        ?string $sessionId = null,
    ): LengthAwarePaginator {

        $sessionId = $sessionId ?: 0;

        return ChatbotConversation::query()
            ->where('session_id', $sessionId)
            ->where('is_showed_on_history', true)
            ->with('chatbot:id,uuid,avatar')
            ->with(['histories.user:id,avatar', 'lastMessage'])
            ->whereNotNull('connect_agent_at')
            ->orderBy('last_activity_at', 'desc')
            ->paginate(request('per_page', request('perPage', 30)));
    }

    public function agentConversationsWithQuery(
        array $chatbots,
        ?string $orderBy = null,
    ): Builder|\Illuminate\Support\HigherOrderWhenProxy {
        $filterAgent = request('agentFilter');

        return ChatbotConversation::query()
            ->when(request('chatbot_channel') && request('chatbot_channel') !== 'all', function (Builder $query) {
                $query->where('chatbot_channel', request('chatbot_channel'));
            })
            ->where('is_showed_on_history', true)
            ->with('chatbot:id,uuid,avatar,title')
            ->with(['histories.user:id,avatar', 'lastMessage'])
            ->when($filterAgent === 'ai', function (Builder $query) {
                $query->whereNotNull('connect_agent_at');
            })
            ->when($filterAgent === 'human', function (Builder $query) {
                $query->whereNull('connect_agent_at');
            })
            ->whereIn('chatbot_id', $chatbots);
    }

    public function agentConversationsWithPaginate(
        array $chatbots,
        ?string $orderBy = null,
    ): LengthAwarePaginator {
        $filterAgent = request('agentFilter');

        $ticketStatus = request('status');

        $unread = request('unread', false);

        $sort = request('sort', 'desc');

        return $this->agentConversationsWithQuery($chatbots, $orderBy)
            ->when($ticketStatus !== 'all' && in_array($ticketStatus, ['new', 'closed']), function (Builder $query) use ($ticketStatus) {
                $query->where('ticket_status', $ticketStatus);
            })
            ->orderBy('pinned', 'desc')
            ->when($unread === 'true', function (Builder $query) {
                $query->whereHas('histories', function (Builder $query) {
                    $query->where('role', 'user')
                        ->whereNull('read_at');
                });
            })
            ->when($sort === 'newest', function (Builder $query) {
                $query->orderBy(
                    function ($query) {
                        $query->select('created_at')
                            ->from('ext_chatbot_histories')
                            ->whereColumn('ext_chatbot_histories.conversation_id', 'ext_chatbot_conversations.id')
                            ->where('ext_chatbot_histories.role', 'user')
                            ->latest()
                            ->limit(1);
                    },
                    'desc'
                );
            })
            ->when($sort === 'oldest', function (Builder $query) {
                $query->orderBy(
                    function ($query) {
                        $query->select('created_at')
                            ->from('ext_chatbot_histories')
                            ->whereColumn('ext_chatbot_histories.conversation_id', 'ext_chatbot_conversations.id')
                            ->where('ext_chatbot_histories.role', 'user')
                            ->latest()
                            ->limit(1);
                    },
                    'asc'
                );
            })
            ->paginate(request('per_page', request('perPage', 30)));
    }

    public function agentConversationsBySearch(array $chatbots, string $search)
    {
        return ChatbotConversation::query()
            ->with('chatbot:id,uuid,avatar,title')
            ->with(['histories.user:id,avatar', 'lastMessage'])
            ->whereNotNull('connect_agent_at')
            ->whereIn('chatbot_id', $chatbots)
            ->whereHas('histories', function (Builder $query) use ($search) {
                $query->where('message', 'like', "%$search%");
            })
            ->orderBy('pinned', 'desc')
            ->get();
    }

    public function conversations(array $chatbots, ?string $orderBy = null): Collection|array
    {
        return ChatbotConversation::query()
            ->where('is_showed_on_history', true)
            ->with('chatbot:id,uuid,avatar,title')
            ->with(['histories', 'lastMessage'])
            ->whereIn('chatbot_id', $chatbots)
            ->when($orderBy, function (Builder $query) use ($orderBy) {
                $query->orderBy($orderBy ?: 'id', 'desc');
            })
            ->get();
    }

    public function conversationsWithPaginate(array $chatbots, ?string $orderBy = null): LengthAwarePaginator
    {
        $filterAgent = request('agentFilter');

        return ChatbotConversation::query()
            ->when(
                request('chatbot_channel') !== 'all',
                function (Builder $query) {
                    $query->where('chatbot_channel', request('chatbot_channel'));
                }
            )
            ->where('is_showed_on_history', true)
            ->with('chatbot:id,uuid,avatar,title')
            ->with(['histories', 'lastMessage'])
            ->when($filterAgent === 'ai', function (Builder $query) {
                $query->whereNotNull('connect_agent_at');
            })
            ->when($filterAgent === 'human', function (Builder $query) {
                $query->whereNull('connect_agent_at');
            })
            ->whereIn('chatbot_id', $chatbots)

            ->when(request('unread') === 'true' || request('unread') === true, function (Builder $query) {
                $query->whereHas('lastMessage', function ($q) {
                    $q->whereNull('read_at');
                });
            })

            ->when(request('sort'), function (Builder $query) {
                $direction = request('sort') === 'oldest' ? 'asc' : 'desc';
                $query->orderBy('id', $direction);
            }, function (Builder $query) use ($orderBy) {
                // fallback if no "sort" is provided
                $query->orderBy($orderBy ?: 'id', 'desc');
            })

            ->paginate(request('per_page', request('perPage', 30)));
    }

    public function update(Model|int $model, array $data): Model
    {
        if (is_int($model)) {
            $model = $this->query()->findOrFail($model);
        }

        $model->update($data);

        return $model;
    }

    public function avatars(): Collection|array
    {
        return ChatbotAvatar::query()
            ->where(function (Builder $query) {
                return $query->where('user_id', Auth::id())->orWhereNull('user_id');
            })
            ->get();
    }

    public function query(): \Illuminate\Database\Eloquent\Builder
    {
        return Chatbot::query();
    }
}
