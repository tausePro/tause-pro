<?php

namespace App\Extensions\UrlToVideo\System\Http\Controllers;

use App\Concerns\HasErrorResponse;
use App\Enums\AiInfluencer\VideoStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\ExportedVideo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class UrlToVideoController extends Controller
{
    use HasErrorResponse;

    // index
    public function index()
    {
        return view('url-to-video::index');
    }

    // store final video result from creatify
    public function storeCreatifyFinalVideo(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'task_id'   => 'required|string',
                'video_url' => 'sometimes|string',
                'title'     => 'sometimes|string',
                'status'    => 'required|string',
            ]);
            $validated['status'] = $validated['status'] == 'done' ? VideoStatusEnum::COMPLETED->value : VideoStatusEnum::FAILED->value;

            $video = ExportedVideo::where('task_id', $validated['task_id'])->first();
            unset($validated['task_id']);
            $video->update($validated);

            return response()->json([
                'status'  => 'success',
                'resData' => $video,
            ]);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while store final result video from creatify');
        }
    }

    // store final video result from topview
    public function storeTopviewFinalVideo(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'task_id'   => 'required|string',
                'video_url' => 'sometimes',
                'title'     => 'sometimes',
                'status'    => 'required|string',
            ]);
            $validated['status'] = $validated['status'] == 'success' ? VideoStatusEnum::COMPLETED->value : VideoStatusEnum::FAILED->value;

            $video = ExportedVideo::where('task_id', $validated['task_id'])->first();
            unset($validated['task_id']);
            $video->update($validated);

            return response()->json([
                'status'  => 'success',
                'resData' => $video,
            ]);
        } catch (Throwable $th) {
            return $this->exceptionRes($th, 'Error happen while store final result video from topview');
        }
    }
}
