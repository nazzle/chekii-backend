<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class SeederController extends Controller
{
    /**
     * Run seeders in sequence: PermissionSeeder → EmployeeSeeder → DatabaseSeeder.
     * Each seeder runs only after the previous one completes successfully.
     */
    public function runSeeders(Request $request)
    {
        if (!app()->environment('local', 'staging')) {
            abort(403, 'Seeders can only be run in non-production environments');
        }

        if ($request->header('deployment_key') !== config('app.deployment_key')) {
            abort(401, 'Unauthorized');
        }

        $order = [
            \Database\Seeders\PermissionSeeder::class,
            \Database\Seeders\EmployeeSeeder::class,
            \Database\Seeders\DatabaseSeeder::class,
        ];

        $results = [];
        $lastOutput = '';
        $currentSeeder = null;

        try {
            foreach ($order as $seederClass) {
                $currentSeeder = $seederClass;
                $shortName = class_basename($seederClass);
                Artisan::call('db:seed', [
                    '--class' => $seederClass,
                    '--force' => true,
                ]);
                $lastOutput = Artisan::output();
                $results[] = ['seeder' => $shortName, 'status' => 'success', 'output' => trim($lastOutput)];
            }

            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            return response()->json([
                'success' => true,
                'message' => 'All seeders ran successfully',
                'results' => $results,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seeder run failed',
                'results' => $results,
                'error' => $e->getMessage(),
                'failed_at' => $currentSeeder ? class_basename($currentSeeder) : null,
            ], 500);
        }
    }
}
