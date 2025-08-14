<?php

namespace App\Services\User;

use App\Http\Requests\User\Brand\BrandRequest;
use App\Models\Company;
use App\Models\Product;
use GuzzleHttp\Client;

class BrandService
{
    public function create(BrandRequest $request): Company
    {
        $company = Company::query()->create($this->data($request));

        $this->image($company, $request);

        $this->product($company, $request);

        return $company;
    }

    public function update(BrandRequest $request, Company $company): Company
    {
        $company->update($this->data($request));

        $this->product($company, $request);

        $this->image($company, $request);

        return $company;
    }

    public function image(Company $company, BrandRequest $request): void
    {
        if ($request->hasFile('c_logo')) {
            $request->validate(['c_logo' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:4096']);

            $logo = $request->file('c_logo')->store('', ['disk' => 'public']);

            $company->update(['logo' => $logo]);
        }
    }

    public function product(Company $company, BrandRequest $request): void
    {
        $inputNames = array_filter($request['inputNames']);
        $inputFeatures = $request['inputFeatures'];
        $inputTypes = $request['inputTypes'];

        foreach ($inputNames as $key => $inputName) {
            Product::query()
                ->updateOrCreate([
                    'name'       => $inputName,
                    'user_id'    => $company->user_id,
                    'company_id' => $company->id,
                ], [
                    'type'         => $inputTypes[$key] ?? 3,
                    'key_features' => $inputFeatures[$key] ?? null,
                ]);
        }

        Product::query()
            ->where('user_id', $company->user_id)
            ->where('company_id', $company->id)
            ->whereNotIn('name', $inputNames)
            ->delete();
    }

    public function data(BrandRequest $request)
    {
        return $request->only([
            'name',
            'industry',
            'description',
            'website',
            'tagline',
            'brand_color',
            'user_id',
            'tone_of_voice',
            'target_audience',
        ]);
    }

    /**
     * Construye un prefill de Brand Voice a partir de una URL usando señales públicas.
     * No persiste; devuelve arreglo para completar el formulario en frontend.
     */
    public function prefillFromWebsite(string $website): array
    {
        $normalized = $this->normalizeUrl($website);

        $summaryBlocks = [];
        $toneCandidates = [];
        $audienceCandidates = [];
        $uvps = [];
        $competitors = [];
        $pillars = [];
        $ctas = [];
        $brandColor = null;
        $tagline = null;
        $name = parse_url($normalized, PHP_URL_HOST) ?? $normalized;

        // 1) Intentar leer homepage para meta title/description y color
        try {
            $html = @file_get_contents($normalized);
            if ($html) {
                if (preg_match('/<title>(.*?)<\\/title>/is', $html, $m)) {
                    $tagline = trim(html_entity_decode($m[1]));
                }
                if (preg_match('/meta[^>]+name=["\']description["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
                    $summaryBlocks[] = 'About: ' . trim(html_entity_decode($m[1]));
                }
                if (preg_match('/meta[^>]+property=["\']og:title["\'][^>]+content=["\']([^"\']+)/i', $html, $m)) {
                    $name = trim(html_entity_decode($m[1]));
                }
                if (preg_match('/var\s*--[^:]+color[^:]*:\s*(#[0-9a-fA-F]{3,6})/i', $html, $m)) {
                    $brandColor = $m[1];
                }
            }
        } catch (\Throwable $e) {
            // silencioso
        }

        // 2) Serper: SERP rápido del dominio (si hay API key)
        try {
            $settingsTwo = \App\Models\SettingTwo::getCache();
            $serperKey = $settingsTwo?->serper_api_key;
            if (! empty($serperKey)) {
                $domain = parse_url($normalized, PHP_URL_HOST) ?: $normalized;
                $client = new Client(['base_uri' => 'https://google.serper.dev', 'timeout' => 10]);
                $resp = $client->post('/search', [
                    'headers' => ['X-API-KEY' => $serperKey, 'Content-Type' => 'application/json'],
                    'json'    => ['q' => 'site:' . $domain . ' about OR servicios OR productos', 'num' => 10],
                ]);
                $body = json_decode((string) $resp->getBody(), true);
                foreach (($body['organic'] ?? []) as $r) {
                    $title = $r['title'] ?? '';
                    $snippet = $r['snippet'] ?? '';
                    if ($title || $snippet) { $summaryBlocks[] = $title . ' — ' . $snippet; }
                }
                foreach (($body['peopleAlsoAsk'] ?? []) as $qa) {
                    if (! empty($qa['question'])) { $pillars[] = $qa['question']; }
                }
            }
        } catch (\Throwable $e) {
        }

        // 3) Tavily: respuesta/summary (si hay API key)
        try {
            $tavilyKey = (string) setting('tavily_api_key');
            if (! empty($tavilyKey)) {
                $client = new Client(['base_uri' => 'https://api.tavily.com', 'timeout' => 15]);
                $queries = [
                    $normalized,
                    $normalized . ' brand positioning',
                    $normalized . ' competitors',
                ];
                foreach ($queries as $q) {
                    $resp = $client->post('/search', [
                        'headers' => ['Content-Type' => 'application/json'],
                        'json'    => [
                            'api_key'        => $tavilyKey,
                            'query'          => $q,
                            'search_depth'   => 'basic',
                            'include_answer' => true,
                            'max_results'    => 8,
                        ],
                    ]);
                    $data = json_decode((string) $resp->getBody(), true);
                    if (! empty($data['answer'])) { $summaryBlocks[] = $data['answer']; }
                    foreach (($data['results'] ?? []) as $r) {
                        if (! empty($r['content'])) { $summaryBlocks[] = mb_strimwidth($r['content'], 0, 220, '…'); }
                    }
                }
            }
        } catch (\Throwable $e) {
        }

        // Heurísticas simples para tono/audiencia/ctas a partir de textos
        $blob = mb_strtolower(implode("\n", $summaryBlocks));
        if (str_contains($blob, 'b2b') || str_contains($blob, 'empresas')) { $audienceCandidates[] = 'B2B / Empresas'; }
        if (str_contains($blob, 'familias') || str_contains($blob, 'hogares')) { $audienceCandidates[] = 'Familias / Hogares'; }
        if (str_contains($blob, 'profesional')) { $toneCandidates[] = 'Professional'; }
        if (str_contains($blob, 'divertido') || str_contains($blob, 'amigable')) { $toneCandidates[] = 'Casual'; }

        if (empty($pillars)) { $pillars = array_values(array_unique(array_filter([$tagline, 'Quienes somos', 'Productos/Servicios', 'Testimonios']))); }
        $ctas = ['Contactarnos', 'Solicitar demo', 'Cotizar', 'Comprar'];

        return [
            'name'             => $name,
            'website'          => $normalized,
            'tagline'          => $tagline,
            'description'      => implode("\n", array_slice(array_filter($summaryBlocks), 0, 12)),
            'brand_color'      => $brandColor,
            'tone_of_voice'    => $toneCandidates[0] ?? null,
            'target_audience'  => $audienceCandidates[0] ?? null,
            'uvps'             => implode("\n", array_slice($uvps, 0, 6)),
            'competitors'      => implode(', ', array_slice($competitors, 0, 6)),
            'content_pillars'  => implode("\n", array_slice(array_filter($pillars), 0, 6)),
            'preferred_ctas'   => implode(', ', array_slice($ctas, 0, 6)),
        ];
    }

    private function normalizeUrl(string $url): string
    {
        $u = trim($url);
        if (! preg_match('#^https?://#i', $u)) { $u = 'https://' . $u; }
        return $u;
    }
}
