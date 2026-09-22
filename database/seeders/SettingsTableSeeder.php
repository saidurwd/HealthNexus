<?php

namespace Database\Seeders;

use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Database\Seeder;

class SettingsTableSeeder extends Seeder
{
    public function run(): void
    {
        $service = app(SettingsService::class);

        foreach ($service->defaults() as $default) {
            Setting::updateOrCreate(
                ['key' => $default['key']],
                [
                    'group' => $default['group'],
                    'value' => $default['value'] ?? null,
                    'type' => $default['type'] ?? 'string',
                    'description' => $default['description'] ?? null,
                    'is_sensitive' => $default['is_sensitive'] ?? false,
                    'is_locked' => $default['is_locked'] ?? false,
                    'sort_order' => $default['sort_order'] ?? 0,
                ]
            );
        }

        $service->flush();
    }
}
