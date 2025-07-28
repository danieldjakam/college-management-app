<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Get all settings
     */
    public function getAllSettings()
    {
        $settings = Setting::all()->keyBy('key');
        return response()->json($settings);
    }

    /**
     * Get setting by key
     */
    public function getSetting($key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre non trouvé'
            ], 404);
        }

        return response()->json($setting);
    }

    /**
     * Update or create setting
     */
    public function updateSetting(Request $request, $key)
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required',
            'description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $setting = Setting::updateOrCreate(
            ['key' => $key],
            [
                'value' => $request->value,
                'description' => $request->description,
                'school_id' => $request->school_id ?? 'GSBPL_001',
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Paramètre mis à jour avec succès',
            'data' => $setting
        ]);
    }

    /**
     * Update multiple settings
     */
    public function updateMultipleSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'settings' => 'required|array',
            'settings.*.key' => 'required|string',
            'settings.*.value' => 'required',
            'settings.*.description' => 'sometimes|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $updatedSettings = [];

        foreach ($request->settings as $settingData) {
            $setting = Setting::updateOrCreate(
                ['key' => $settingData['key']],
                [
                    'value' => $settingData['value'],
                    'description' => $settingData['description'] ?? null,
                    'school_id' => $request->school_id ?? 'GSBPL_001',
                ]
            );
            $updatedSettings[] = $setting;
        }

        return response()->json([
            'success' => true,
            'message' => 'Paramètres mis à jour avec succès',
            'data' => $updatedSettings
        ]);
    }

    /**
     * Delete setting
     */
    public function deleteSetting($key)
    {
        $setting = Setting::where('key', $key)->first();

        if (!$setting) {
            return response()->json([
                'success' => false,
                'message' => 'Paramètre non trouvé'
            ], 404);
        }

        $setting->delete();

        return response()->json([
            'success' => true,
            'message' => 'Paramètre supprimé avec succès'
        ]);
    }

    /**
     * Get school information settings
     */
    public function getSchoolSettings()
    {
        $schoolSettings = Setting::whereIn('key', [
            'school_name',
            'school_address',
            'school_phone',
            'school_email',
            'school_logo',
            'principal_name',
            'current_year'
        ])->get()->keyBy('key');

        return response()->json($schoolSettings);
    }

    /**
     * Update school information
     */
    public function updateSchoolSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'school_name' => 'sometimes|string|max:255',
            'school_address' => 'sometimes|string|max:500',
            'school_phone' => 'sometimes|string|max:20',
            'school_email' => 'sometimes|email|max:255',
            'school_logo' => 'sometimes|string|max:255',
            'principal_name' => 'sometimes|string|max:255',
            'current_year' => 'sometimes|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first()
            ], 422);
        }

        $updatedSettings = [];
        $allowedKeys = [
            'school_name', 'school_address', 'school_phone', 
            'school_email', 'school_logo', 'principal_name', 'current_year'
        ];

        foreach ($allowedKeys as $key) {
            if ($request->has($key)) {
                $setting = Setting::updateOrCreate(
                    ['key' => $key],
                    [
                        'value' => $request->$key,
                        'school_id' => $request->school_id ?? 'GSBPL_001',
                    ]
                );
                $updatedSettings[$key] = $setting;
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Informations de l\'école mises à jour avec succès',
            'data' => $updatedSettings
        ]);
    }
}