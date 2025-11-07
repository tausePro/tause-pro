<?php

namespace App\Extensions\UrlToVideo\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TopviewSettingController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        return view('url-to-video::settings.topview');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'topview_api_id'    => 'required|string',
            'topview_api_key'   => 'required|string',
        ]);

        setting($data)->save();

        return back()->with([
            'type'    => 'success',
            'message' => trans('Settings updated successfully.'),
        ]);
    }
}
