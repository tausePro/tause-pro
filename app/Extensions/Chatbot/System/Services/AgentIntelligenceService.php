<?php

declare(strict_types=1);

namespace App\Extensions\Chatbot\System\Services;

/**
 * Servicio de Inteligencia para Análisis de Intención
 *
 * Analiza la intención del usuario usando NLP mejorado y embeddings
 */
class AgentIntelligenceService
{
    /**
     * Analizar intención del usuario
     *
     * @param  string  $userQuery  Query del usuario
     * @param  string  $aiResponse  Respuesta del AI
     * @param  array  $context  Contexto adicional de la conversación
     *
     * @return array Análisis de intención con scores y tipos detectados
     */
    public function analyzeIntent(string $userQuery, string $aiResponse, array $context = []): array
    {
        $text = strtolower($userQuery . ' ' . $aiResponse);

        $intent = [
            'type'             => 'general',
            'confidence'       => 0,
            'detected_intents' => [],
            'keywords_found'   => [],
        ];

        // Detectar intención comercial
        $commercialScore = $this->detectCommercialIntent($text);
        if ($commercialScore > 0) {
            $intent['detected_intents'][] = [
                'type'  => 'commercial',
                'score' => $commercialScore,
            ];
            $intent['confidence'] = max($intent['confidence'], $commercialScore);
        }

        // Detectar intención de soporte
        $supportScore = $this->detectSupportIntent($text);
        if ($supportScore > 0) {
            $intent['detected_intents'][] = [
                'type'  => 'support',
                'score' => $supportScore,
            ];
            $intent['confidence'] = max($intent['confidence'], $supportScore);
        }

        // Detectar intención de información
        $infoScore = $this->detectInfoIntent($text);
        if ($infoScore > 0) {
            $intent['detected_intents'][] = [
                'type'  => 'information',
                'score' => $infoScore,
            ];
        }

        // Determinar tipo principal
        if (! empty($intent['detected_intents'])) {
            $primaryIntent = collect($intent['detected_intents'])
                ->sortByDesc('score')
                ->first();
            $intent['type'] = $primaryIntent['type'];
            $intent['confidence'] = $primaryIntent['score'];
        }

        return $intent;
    }

    /**
     * Detectar intención comercial
     */
    protected function detectCommercialIntent(string $text): int
    {
        $commercialPhrases = [
            'producto'   => 30,
            'productos'  => 30,
            'comprar'    => 40,
            'precio'     => 35,
            'precios'    => 35,
            'costo'      => 30,
            'costos'     => 30,
            'vender'     => 35,
            'venta'      => 30,
            'disponible' => 25,
            'stock'      => 25,
            'inventario' => 20,
            'catálogo'   => 25,
            'tienda'     => 20,
            'adquirir'   => 35,
            'pagar'      => 30,
            'pago'       => 30,
            'pedido'     => 30,
            'orden'      => 30,
            'carrito'    => 35,
            'checkout'   => 40,
            'oferta'     => 25,
            'descuento'  => 25,
            'promoción'  => 25,
        ];

        $score = 0;
        $foundKeywords = [];

        foreach ($commercialPhrases as $phrase => $weight) {
            if (str_contains($text, $phrase)) {
                $score += $weight;
                $foundKeywords[] = $phrase;
            }
        }

        // Bonus por frases comerciales en respuesta del AI
        $commercialResponsePhrases = [
            'te recomiendo'      => 20,
            'tenemos disponible' => 25,
            'contamos con'       => 20,
            'puedes adquirir'    => 30,
            'puedes comprar'     => 30,
            'está en'            => 15,
            'cuesta'             => 20,
            'precio de'          => 25,
            'valor de'           => 20,
            'te ofrecemos'       => 25,
            'ideal para'         => 15,
        ];

        foreach ($commercialResponsePhrases as $phrase => $weight) {
            if (str_contains($text, $phrase)) {
                $score += $weight;
            }
        }

        return min($score, 100); // Cap a 100
    }

    /**
     * Detectar intención de soporte
     */
    protected function detectSupportIntent(string $text): int
    {
        $supportPhrases = [
            'ayuda'       => 30,
            'problema'    => 35,
            'error'       => 40,
            'no funciona' => 45,
            'no entiendo' => 30,
            'cómo'        => 20,
            'explicar'    => 25,
            'soporte'     => 40,
            'asistencia'  => 35,
            'técnico'     => 30,
            'falla'       => 35,
            'bug'         => 40,
            'issue'       => 35,
        ];

        $score = 0;

        foreach ($supportPhrases as $phrase => $weight) {
            if (str_contains($text, $phrase)) {
                $score += $weight;
            }
        }

        return min($score, 100);
    }

    /**
     * Detectar intención de información
     */
    protected function detectInfoIntent(string $text): int
    {
        $infoPhrases = [
            'qué es'           => 30,
            'qué son'          => 30,
            'información'      => 25,
            'detalles'         => 20,
            'características'  => 25,
            'especificaciones' => 25,
            'cuéntame'         => 20,
            'dime'             => 15,
        ];

        $score = 0;

        foreach ($infoPhrases as $phrase => $weight) {
            if (str_contains($text, $phrase)) {
                $score += $weight;
            }
        }

        return min($score, 100);
    }

    /**
     * Extraer keywords relevantes del texto
     */
    public function extractKeywords(string $text, array $customKeywords = []): array
    {
        $text = strtolower($text);
        $foundKeywords = [];

        $defaultKeywords = [
            'producto', 'productos', 'comprar', 'precio', 'precios',
            'disponible', 'stock', 'venta', 'catálogo', 'tienda',
        ];

        $allKeywords = array_merge($defaultKeywords, $customKeywords);

        foreach ($allKeywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                $foundKeywords[] = $keyword;
            }
        }

        return array_unique($foundKeywords);
    }
}
