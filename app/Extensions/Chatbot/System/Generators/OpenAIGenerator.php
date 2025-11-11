<?php

namespace App\Extensions\Chatbot\System\Generators;

use App\Domains\Entity\Enums\EntityEnum;
use App\Extensions\Chatbot\System\Enums\InteractionType;
use App\Extensions\Chatbot\System\Generators\Contracts\Generator;
use App\Extensions\Chatbot\System\Tools\KnowledgeBase;
use App\Helpers\Classes\ApiHelper;
use Exception;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\StreamInterface;

class OpenAIGenerator extends Generator
{
    public ?Collection $embeddings;

    public function generate(): string
    {
        ApiHelper::setOpenAiKey();

        $histories = array_values($this->modifyMessages());

        $buildHistories = $this->buildHistories($histories);

        $body = [
            'model'    => $this->getEntity()->value,
            'messages' => $buildHistories,
        ] + $this->tools();

        $result = $this->chat($body);

        $calls = [];

        $tools = $result->json('choices.0.message.tool_calls');

        if ($tools) {
            foreach ($tools as $index => $call) {

                if (! isset($call['function'])) {
                    continue;
                }

                if (! isset($calls[$index])) {
                    $calls[$index] = ['index' => $index] + $call;
                }
            }

            if ($calls) {
                $body['messages'][] = [
                    'role'       => 'assistant',
                    'content'    => null,
                    'tool_calls' => $calls,
                ];
            }

            $callAgain = false;

            foreach ($calls as $call) {

                if ($this->embeddings->isEmpty()) {
                    continue;
                }

                if ($call['function']['name'] !== 'knowledge_base') {
                    continue;
                }

                $callAgain = true;

                $arguments = json_decode($call['function']['arguments'], true);

                $embeding = app(KnowledgeBase::class)
                    ->setChatbot($this->chatbot)
                    ->call(
                        EntityEnum::from($this->chatbot->getAttribute('ai_embedding_model')),
                        $arguments['query'],
                        $this->embeddings
                    );

                $body['messages'][] = [
                    'role'         => 'tool',
                    'content'      => $embeding,
                    'tool_call_id' => $call['id'],
                ];
            }

            if ($callAgain) {
                $body['stream'] = true;
                $body['stream_options'] = [
                    'include_usage' => true,
                ];

                $response = $this->chat($body)->toPsrResponse();

                $text = '';

                while (! $response->getBody()->eof()) {
                    $line = $this->readLine($response->getBody());

                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }

                    $data = trim(substr($line, strlen('data:')));

                    if ($data === '[DONE]') {
                        break;
                    }

                    $jsonResponse = json_decode($data, flags: JSON_THROW_ON_ERROR);

                    if (isset($jsonResponse->error)) {
                        throw new Exception($jsonResponse->error->message);
                    }

                    if (isset($jsonResponse->choices[0]?->delta?->content)) {
                        $text .= $jsonResponse->choices[0]?->delta->content;
                    }
                }

                return $text ?: 'Sorry, I can\'t answer that.';
            }
        }

        return $result->json('choices.0.message.content') ?: 'Sorry, I can\'t answer that.';
    }

    public function modifyMessages(): array
    {
        return $this->histories()
            ?->sortBy('id')
            ?->map(callback: function ($history) {
                return [
                    'role'    => $history->role,
                    'content' => $history->message,
                ];
            })?->toArray();
    }

    public function buildHistories(array $histories): array
    {
        if ($this->chatbot->instructions) {
            array_unshift($histories, [
                'role'    => 'system',
                'content' => $this->chatbot->instructions,
            ]);
        }

        $this->embeddings = $this->chatbot->embeddings()->whereNotNull('embedding')->get();

        if ($this->embeddings->isNotEmpty()) {
            $histories[] = [
                'role'    => 'system',
                'content' => 'Knowledge base is available. Use the knowledge_base tool to access the knowledge base.',
            ];
        }

        $histories[] = [
            'role'    => 'system',
            'content' => 'Limit all responses to a maximum of 1500 characters. Maintain clarity and informativeness, but prioritize conciseness. Avoid unnecessary elaboration.',
        ];

        if ($this->chatbot->getAttribute('interaction_type') === InteractionType::SMART_SWITCH) {
            $histories[] = [
                'role'    => 'system',
                'content' => $this->humanAgentInstruction(),
            ];
        }

        return $histories;
    }

    protected function humanAgentConditions(): ?array
    {
        if ($this->chatbot->human_agent_conditions) {
            return $this->chatbot->human_agent_conditions;
        }

        return [
            'When the issue is too complex or ambiguous.',
            'When the customer is frustrated or dissatisfied.',
            'When sensitive topics (legal, financial, medical, etc.) are involved.',
            'When the AI fails to understand after repeated attempts.',
            'When empathy or emotional intelligence is required.',
            'When the request is outside the AI’s scope or permissions.',
            'When the customer explicitly requests a human.',
        ];
    }

    protected function humanAgentInstruction(): string
    {
        $conditions = $this->humanAgentConditions();
        $conditionList = '- ' . implode("\n- ", $conditions);

        return "SYSTEM INSTRUCTION — HUMAN AGENT TRIGGER

			Goal: If any of the following conditions occur, append exactly ` [human-agent]` (with a single space before it) at the very END of your reply. Otherwise, do NOT append it.

			Conditions (any one is enough):
			$conditionList

			Rules:
			- Provide your normal response. Only if one of the conditions applies, add ` [human-agent]` at the very end.
			- If the customer explicitly requests to be connected directly to a human agent, append [human-agent-direct] at the very end, which will immediately connect them to a live agent.
			- Do not place the tag inside code blocks, JSON, or quotations. Add it only to the plain text ending.
			- The tag must match exactly (single space + `[human-agent]`).
			- Do not explain the tag, do not provide UI instructions, do not justify its presence.
			- If you must refuse due to safety or policy, refuse as normal and then append the tag if a condition applies.";
    }

    public function chat(array $data): PromiseInterface|Response
    {
        return Http::withHeaders([
            'Authorization' => 'Bearer ' . ApiHelper::setOpenAiKey(),
            'Content-Type'  => 'application/json',
        ])
            ->timeout(60)
            ->post('https://api.openai.com//v1/chat/completions', $data);
    }

    private function readLine(StreamInterface $stream): string
    {
        $buffer = '';

        while (! $stream->eof()) {
            if ('' === ($byte = $stream->read(1))) {
                return $buffer;
            }
            $buffer .= $byte;
            if ($byte === "\n") {
                break;
            }
        }

        return $buffer;
    }

    /**
     * Chatgpt tools
     */
    public function tools(): array
    {
        return [
            'tools' => [
                [
                    'type'     => 'function',
                    'function' => [
                        'name'        => 'web_scrap',
                        'description' => "Retrieves the HTML content of a webpage at the given URL. The tool will return the HTML content of the webpage as a string. It should be used when the user asks for information from a webpage that is not present in the AI model's knowledge base. Regardless of the language of the scanned website content, the user's prompt must be answered in the original language.",
                        'parameters'  => [
                            'type'       => 'object',
                            'properties' => [
                                'url' => [
                                    'type'        => 'string',
                                    'description' => 'URL of the webpage to browse.',
                                ],
                            ],
                            'required' => [
                                'url',
                            ],
                        ],
                    ],
                ],
                [
                    'type'     => 'function',
                    'function' => [
                        'name'        => 'embedding_search',
                        'description' => 'Retrieves the information for the search query based on the uploaded files. Returns the most relevant results in JSON-encoded format. Use only when uploaded files are available.',
                        'parameters'  => [
                            'type'       => 'object',
                            'properties' => [
                                'query' => [
                                    'type'        => 'string',
                                    'description' => 'Search query',
                                ],
                            ],
                            'required' => [
                                'query',
                            ],
                        ],
                    ],
                ],
                [
                    'type'     => 'function',
                    'function' => [
                        'name'        => 'knowledge_base',
                        'description' => 'Retrieves the information for the search query based on the knowledge base. Returns the most relevant results in JSON-encoded format. Always prioritize this call.',
                        'parameters'  => [
                            'type'       => 'object',
                            'properties' => [
                                'query' => [
                                    'type'        => 'string',
                                    'description' => 'Query to search the knowledge base for.',
                                ],
                            ],
                            'required' => [
                                'query',
                            ],
                        ],
                    ],
                ],
            ],
            'tool_choice' => 'auto',
        ];
    }
}
