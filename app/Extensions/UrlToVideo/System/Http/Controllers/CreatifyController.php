<?php

namespace App\Extensions\UrlToVideo\System\Http\Controllers;

use App\Concerns\HasErrorResponse;
use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Enums\AiInfluencer\VideoStatusEnum;
use App\Extensions\UrlToVideo\System\Http\Requests\GeneratePreviewVideoRequest;
use App\Helpers\Classes\ApiHelper;
use App\Helpers\Classes\Helper;
use App\Models\ExportedVideo;
use App\Packages\Creatify\CreatifyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

class CreatifyController
{
    use HasErrorResponse;

    public CreatifyService $service;

    public function __construct()
    {
        $credential = ApiHelper::setCreatifyAIKey();
        $this->service = new CreatifyService(
            $credential['creatify_api_id'],
            $credential['creatify_api_key']
        );

        ini_set('max_execution_time', 900);
        set_time_limit(900);

    }

    /**
     * ==========================================================
     * Link
     */
    // Generate link on creatify by url
    public function generateLinkByUrl(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        $validated = $request->validate(['url' => 'required|string']);

        $driver = Entity::driver(EntityEnum::AD_MARKETING_VIDEO)->inputVideoCount(1)->calculateCredit();
        $driver->redirectIfNoCreditBalance();

        try {
            return $this->service->links()->createLink($validated['url']);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while generate link by url on creatify');
        }
    }

    // Generate the link on creatify by assets
    public function generateLinkByParams(Request $request)
    {
        /**
         * @todo change image_urls with file upload and convert it url to use api
         */
        $validated = $request->validate([
            'image_urls'  => 'sometimes|array',
            'video_urls'  => 'sometimes|array',
            'title'       => 'sometimes|string',
            'description' => 'sometimes|string',
        ]);

        $driver = Entity::driver(EntityEnum::AD_MARKETING_VIDEO)->inputVideoCount(1)->calculateCredit();
        $driver->redirectIfNoCreditBalance();

        try {
            return $this->service->links()->createLinkWithParams($validated);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while generate link by params on creatify');
        }
    }

    /**
     * ==========================================================
     * Script
     */
    // generate script
    public function generateScript(Request $request)
    {
        try {
            $validated = $request->validate([
                'url'         => 'sometimes|string',
                'title'       => 'sometimes|string',
                'description' => 'sometimes|string',
            ]);

            return $this->service->aiScripts()->generateAIScripts($validated);
        } catch (Throwable $th) {

            return $this->exceptionRes($th, 'Error happen while generate the script on creatify');
        }
    }

    // get script items
    public function getScripts(Request $request)
    {
        try {
            $validated = $request->validate([
                'ids' => 'required|string',
            ]);

            return $this->service->aiScripts()->getAIScriptItems($validated);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while get scripts from creatify');
        }
    }

    // get script
    public function getScript(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'sometimes|string',
            ]);

            return $this->service->aiScripts()->getAIScriptItemById($validated['id']);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while get script from creatify');
        }
    }

    /**
     * ==========================================================
     * General Query
     */
    // get avatars
    public function getAvatars(): JsonResponse
    {
        try {
            return Cache::rememberForever('creatify-avatars', fn () => $this->service->avatar()->getAllAvailableAvatars());
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while get avatars from creatify');
        }
    }

    // get voices
    public function getVoices(): JsonResponse
    {
        try {
            return Cache::rememberForever('creatify-voices', fn () => $this->service->voices()->getVoices());
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while get voices from creatify');
        }
    }

    // get musics
    public function getMusics(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'page'      => 'sometimes|integer',
                'page_size' => 'sometimes|integer',
            ]);

            return Cache::rememberForever('creatify-musics-' . $validated['page_size'] . '-' . $validated['page'], fn () => $this->service->musics()->getMusics($validated));
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while get musics from creatify');
        }
    }

    /**
     * ==========================================================
     * Generate Video
     */
    // generate preview video
    public function generatePreviewVideos(GeneratePreviewVideoRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $driver = Entity::driver(EntityEnum::AD_MARKETING_VIDEO)->inputVideoCount(1)->calculateCredit();
        $driver->redirectIfNoCreditBalance();

        try {

            if (! isset($validated['visual_styles']) || empty($validated['visual_styles'])) {
                $validated['visual_styles'] = [
                    'FullScreenV2Template',
                    'LegoTemplate',
                    'LegoScriptLongLineReasonHook',
                    'LegoScriptStillFullPriceHook',
                    'LegoScriptVoucherSavingsAlertHook',
                ];
            }

            $validated['no_background_music'] = $validated['no_background_music'] ?? false;
            $validated['no_caption'] = $validated['no_caption'] ?? false;

            return $this->service->linkToVideos()->generateListOfPreviews($validated);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while generate preview video from creatify');
        }
    }

    // get video result
    public function getVideoResult(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|string',
            ]);

            return $this->service->linkToVideos()->getVideoResult($validated['id']);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while get video result from creatify');
        }
    }

    // render video using preview video
    public function renderFinalVideo(Request $request)
    {
        $validated = $request->validate([
            'id'        => 'required|string',
            'media_job' => 'required|string',
        ]);

        $driver = Entity::driver(EntityEnum::AD_MARKETING_VIDEO)->inputVideoCount(1)->calculateCredit();
        $driver->redirectIfNoCreditBalance();
        $driver->decreaseCredit();

        try {

            $res = $this->service->linkToVideos()->renderVideoFromListOfPreviews($validated['id'], $validated['media_job']);

            $requestId = $res->getData()?->resData?->id;

            if (! empty($requestId)) {
                ExportedVideo::create([
                    'task_id'      => $requestId,
                    'status'       => VideoStatusEnum::IN_PROGRESS->value,
                    'used_ai_tool' => 'creatify',
                ]);
            }

            return $res;
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while render video from creatify');
        }
    }
}
