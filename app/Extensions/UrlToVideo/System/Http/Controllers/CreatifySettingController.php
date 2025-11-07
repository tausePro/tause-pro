<?php

namespace App\Extensions\UrlToVideo\System\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CreatifySettingController extends Controller
{
    public function index(Request $request): RedirectResponse|View
    {
        return view('url-to-video::settings.creatify');
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'creatify_api_id'    => 'required|string',
            'creatify_api_key'   => 'required|string',
        ]);

        setting($data)->save();

        return back()->with([
            'type'    => 'success',
            'message' => trans('Settings updated successfully.'),
        ]);
    }
}
