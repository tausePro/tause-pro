<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use NeuronAI\Providers\OpenAI\OpenAI;
use NeuronAI\Chat\Messages\UserMessage;

class TestController extends Controller
{
    public function testNeuron(Request $request)
    {
        try {
            $provider = new OpenAI(
                key: config('neuron.providers.openai.key', 'test-key'),
                model: config('neuron.providers.openai.model', 'gpt-3.5-turbo'),
            );

            $response = $provider->chat([
                new UserMessage('Hello, how are you?')
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Neuron AI is working!',
                'response' => $response->getContent(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
