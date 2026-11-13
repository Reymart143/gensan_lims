<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class TextBlastController extends Controller
{
    private const RELEASE_NOTIFICATIONS_KEY = 'release_notifications';

    public function index()
    {
        $isSmsActive = (bool) DB::table('sms_settings')
            ->where('key', self::RELEASE_NOTIFICATIONS_KEY)
            ->value('is_active');

        return view('textblast.index', compact('isSmsActive'));
    }

    public function updateStatus(Request $request)
    {
        $data = $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $setting = DB::table('sms_settings')
            ->where('key', self::RELEASE_NOTIFICATIONS_KEY)
            ->first();

        $values = [
            'is_active'  => (bool) $data['is_active'],
            'updated_by' => Auth::id(),
            'updated_at' => now(),
        ];

        if ($setting) {
            DB::table('sms_settings')->where('id', $setting->id)->update($values);
        } else {
            DB::table('sms_settings')->insert(array_merge($values, [
                'key' => self::RELEASE_NOTIFICATIONS_KEY,
                'created_at' => now(),
            ]));
        }

        return redirect()
            ->route('text_blast')
            ->with('success', $data['is_active'] ? 'SMS release notifications are now active.' : 'SMS release notifications are now inactive.');
    }
    // private function getSmsSetting(){
    //     return DB::table('sms_settings')
    //         ->where('key', self::RELEASE_NOTIFICATIONS_KEY)
    //         ->first();
    // }
}
