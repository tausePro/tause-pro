<?php

namespace App\Extensions\UrlToVideo\System\Http\Controllers;

use App\Concerns\HasErrorResponse;
use App\Domains\Entity\Enums\EntityEnum;
use App\Domains\Entity\Facades\Entity;
use App\Enums\AiInfluencer\VideoStatusEnum;
use App\Helpers\Classes\ApiHelper;
use App\Helpers\Classes\Helper;
use App\Http\Controllers\Controller;
use App\Models\ExportedVideo;
use App\Packages\Topview\Enums\UploadFileFormat;
use App\Packages\Topview\TopviewService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class TopViewController extends Controller
{
    use HasErrorResponse;

    protected TopviewService $service;

    public function __construct()
    {
        $credential = ApiHelper::setTopviewKey();
        $this->service = new TopviewService($credential['topview_api_id'], $credential['topview_api_key']);
    }

    /**
     * ============================================================
     * Avatar Marketing Video
     */
    public function marketingVideoSubmitTask(Request $request): JsonResponse
    {
        $driver = Entity::driver(EntityEnum::AD_MARKETING_VIDEO_TOPVIEW)->inputVideoCount(1)->calculateCredit();
        $driver->redirectIfNoCreditBalance();

        try {
            return $this->service->avatarMarketingVideo()->submitTask($request->all());
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while render video from topview');
        }
    }

    public function marketingVideoQueryTask(Request $request): JsonResponse
    {
        return $this->service->avatarMarketingVideo()->queryTask($request->query('taskId'));
    }

    public function marketingVideoListScripts(Request $request): JsonResponse
    {
        return $this->service->avatarMarketingVideo()->listScriptContent($request->query('taskId'));
    }

    public function marketingVideoUpdateScriptContent(Request $request): JsonResponse
    {
        return $this->service->avatarMarketingVideo()->updateScriptContent($request->all());
    }

    public function marketingVideoExport(Request $request): JsonResponse
    {
        $driver = Entity::driver(EntityEnum::AD_MARKETING_VIDEO_TOPVIEW)->inputVideoCount(1)->calculateCredit();
        $driver->redirectIfNoCreditBalance();
        $driver->decreaseCredit();

        try {
            $res = $this->service->avatarMarketingVideo()->export($request->input('taskId'), $request->input('scriptId', 0));

            $taskId = $res->getData()?->resData?->taskId;

            if (! empty($taskId)) {
                ExportedVideo::create([
                    'task_id'      => $taskId . ',' . $request->input('scriptId', 0),
                    'status'       => VideoStatusEnum::IN_PROGRESS->value,
                    'used_ai_tool' => 'topview',
                ]);
            }

            return $res;
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while render video from topview');
        }
    }

    /**
     * ============================================================
     * General Query
     */
    public function captionList(): JsonResponse
    {
        return Cache::rememberForever('topview-captions', fn () => $this->service->generalQuery()->captionList());
    }

    public function voiceQuery(Request $request): JsonResponse
    {
        $pageNo = $request->query('pageNo', 1);
        $pageSize = $request->query('pageSize', 100);

        return Cache::rememberForever("topview-voice-$pageSize-$pageNo", fn () => $this->service->generalQuery()->voiceQuery($request->all()));
    }

    public function aiAvatarQuery(Request $request): JsonResponse
    {
        $pageNo = $request->query('pageNo', 1);
        $pageSize = $request->query('pageSize', 100);

        return Cache::rememberForever("topview-avatar-$pageSize-$pageNo", fn () => $this->service->generalQuery()->aiAvatarQuery($request->all()));
    }

    public function ethnicityQuery(Request $request): JsonResponse
    {
        return $this->service->generalQuery()->ethnicityQuery($request->all());
    }

    public function noticeUrlCheck()
    {
        return $this->service->generalQuery()->checkNoticeUrl(route('dashboard.user.url-to-video.topview.general.notice-url-handler'));
    }

    public function noticeUrlHandler(Request $reqeust)
    {
        Log::info('Notice request', $reqeust->all());
    }

    /**
     * ============================================================
     * Product Avatar
     */
    public function productCategoryQuery(): JsonResponse
    {
        return $this->service->productAvatar()->categoryQuery();
    }

    public function productPubicAvatar(Request $request): JsonResponse
    {
        return $this->service->productAvatar()->publicProductAvatarQuery($request->all());
    }

    /**
     * ============================================================
     * Scraper
     */
    // submit url scraper task
    public function submitScraperTask(Request $request): JsonResponse
    {
        // validate the request
        $request->validate([
            'productLink' => 'required|string',
        ]);

        return $this->service->scraper()->submitScraperTask($request->input('productLink'));
    }

    // query the scraper task
    public function queryScraperTask(Request $request): JsonResponse
    {
        // validate the request
        $request->validate([
            'taskId' => 'required|string',
        ]);

        return $this->service->scraper()->queryScraperTask($request->input('taskId'));
    }

    /**
     * ============================================================
     * Upload
     */
    // multi file uploads
    public function uploadFiles(Request $request): JsonResponse
    {
        $request->validate([
            'files'   => 'required',
            'files.*' => 'required|mimes:' . implode(',', UploadFileFormat::getLabels()),
        ]);

        try {
            $fileDisk = 'uploads';
            $rootDir = 'topview-assets';
            $uploadedFiles = [];

            foreach ($request->file('files') as $file) {
                $path = $file->store($rootDir, ['disk' => $fileDisk]);
                $res = $this->service->upload()->getUploadCredential(pathinfo($file->getClientOriginalName(), PATHINFO_EXTENSION));

                if ($res->getData()?->status == 'success') {
                    $uploadedFiles[] = [
                        'fileId'   => $res->getData()->resData?->fileId,
                        'fileUrl'  => '/uploads/' . $path,
                        'fileType' => UploadFileFormat::tryFrom($res->getData()->resData?->format)->getType(),
                    ];

                    $this->service->upload()->uploadToS3(Storage::disk($fileDisk)->path($path), $res->getData()->resData?->uploadUrl);
                } else {
                    throw new Exception($res->getData()->message ?? 'Error Processing File Upload Request');
                }
            }

            if (empty($uploadedFiles)) {
                throw new Exception('No files uploaded');
            }

            return response()->json([
                'status'        => 'success',
                'uploadedFiles' => $uploadedFiles,
            ]);
        } catch (Throwable $th) {
            Log::error('file upload failed for topview: ', [
                'errorMessage' => $th->getMessage(),
            ]);

            return response()->json([
                'status'       => 'error',
                'message'      => 'Unexpected issue happen',
                'errorMessage' => $th->getMessage(),
            ]);
        }
    }

    public function getCredential(Request $request): JsonResponse
    {
        return $this->service->upload()->getUploadCredential($request->input('format'));
    }

    public function uploadS3(Request $request): JsonResponse
    {
        return $this->service->upload()->uploadToS3(public_path($request->input('filePath')), $request->input('uploadUrl'));
    }

    /**
     * ============================================================
     * VideoAvatar
     */
    public function videoAvatarSubmit(Request $request): JsonResponse
    {
        return $this->service->videoAvatar()->submitTask($request->all());
    }

    public function videoAvatarQuery(Request $request): JsonResponse
    {
        return $this->service->videoAvatar()->queryTask($request->input('taskId'), $request->input('needCloudFrontUrl') ?? true);
    }

    /**
     * ============================================================
     * Exported voide handle
     */
    public function storeExportVideo(Request $request): JsonResponse
    {
        $data = $request->validate([
            'video_url'      => 'required|string',
            'title'          => 'required|string',
            'used_ai_tool'   => 'required|string',
            'cover_url'      => 'required|string',
            'video_duration' => 'required',
        ]);

        try {
            $exportedVideo = ExportedVideo::create($data);

            return response()->json([
                'status'  => 'success',
                'resData' => $exportedVideo,
            ]);
        } catch (Throwable $th) {
            Log::error('Error happen while store exported video', [
                'errorMessage' => $th->getMessage(),
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Unexpected issue happen',
            ]);
        }

    }

    public function deleteExportedVideo(Request $request): JsonResponse
    {
        if (Helper::appIsDemo()) {
            return response()->json([
                'status'  => 'error',
                'message' => trans('This feature is disabled in demo mode.'),
            ]);
        }

        try {
            $request->validate([
                'video_id' => 'required',
            ]);

            ExportedVideo::find($request->input('video_id'))->delete();

            return response()->json([
                'status' => 'success',
            ]);
        } catch (Throwable $th) {
            Log::error('Error happend while delete the exported video:', [
                'errorMessage' => $th->getMessage(),
            ]);

            return response()->json([
                'status'       => 'error',
                'message'      => 'Something went wrong',
                'errorMessage' => $th->getMessage(),
            ]);
        }

    }
}
