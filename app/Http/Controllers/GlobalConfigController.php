<?php

namespace App\Http\Controllers;

use App\CoreService\CallService;
use App\Models\GlobalConfig;
use Illuminate\Http\Request;

class GlobalConfigController extends Controller
{
    // GET Global Config detail
    public function show()
    {
        $config = GlobalConfig::first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi global belum tersedia.',
            ], 404);
        }

        return CallService::run('Find', [
            'id'    => $config->id,
            'model' => 'global_config',
        ]);
    }

    // PUT Update Global Config
    public function update(Request $request)
    {
        $config = GlobalConfig::first();

        if (!$config) {
            return response()->json([
                'success' => false,
                'message' => 'Konfigurasi global belum tersedia.',
            ], 404);
        }

        $input = array_merge($request->all(), [
            'id'            => $config->id,
            'model'         => 'global_config',
            'img_profile_2' => $request->input('img_profile_2') ?: null,
        ]);

        return CallService::run('Edit', $input);
    }
}
