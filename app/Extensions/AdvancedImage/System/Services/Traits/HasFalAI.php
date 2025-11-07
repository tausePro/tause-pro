<?php

namespace App\Extensions\AdvancedImage\System\Services\Traits;

use App\Domains\Entity\Enums\EntityEnum;
use App\Helpers\Classes\ApiHelper;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use RuntimeException;

trait HasFalAI
{
    private static string $baseApiUrl = 'https://queue.fal.run/fal-ai/';

    private static string $uploadPath = 'uploads/';

    public static function generateImage(
        string $prompt,
        EntityEnum $entity = EntityEnum::FLUX_PRO_KONTEXT,
        array $images = []
    ): Response {
        $request = self::buildRequest($prompt, $entity, $images);
        $response = self::makeApiRequest($entity, $request);

        return self::handleResponse($response);
    }

    private static function buildRequest(string $prompt, EntityEnum $entity, array $images): array
    {
        $request = ['prompt' => $prompt];

        if (empty($images)) {
            return $request;
        }

        //        $imageUrls = self::createImageUrls($images);

        // FLUX_PRO_KONTEXT with single image uses different parameter
        if ($entity === EntityEnum::FLUX_PRO_KONTEXT && count($images) === 1) {
            $request['image_url'] = Arr::first($images);
        } else {
            $request['image_urls'] = $images;
        }

        return $request;
    }

    private static function makeApiRequest(EntityEnum $entity, array $request): Response
    {
        $url = self::$baseApiUrl . $entity->value . '/max';

        return Http::withHeaders([
            'Content-Type'  => 'application/json',
            'Accept'        => 'application/json',
            'Authorization' => 'Key ' . ApiHelper::setFalAIKey(),
        ])->post($url, $request);
    }

    private static function handleResponse(Response $response): Response
    {
        if ($response->successful() && $requestId = $response->json('request_id')) {
            return $response;
        }

        $errorMessage = $response->json('detail') ?: 'Check your FAL API key.';

        throw new RuntimeException(__($errorMessage));
    }

    public static function createImageUrls(array $images): array
    {
        if (empty($images)) {
            return [];
        }

        $baseUrl = url(self::$uploadPath);

        return array_map(
            fn ($image) => $baseUrl . '/' . $image->store('falai', 'public'),
            $images
        );
    }
}
