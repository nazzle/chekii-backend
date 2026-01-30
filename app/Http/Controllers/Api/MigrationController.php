<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class MigrationController extends Controller
{
    public function runMigrations(Request $request)
    {
        // Add security checks!
        if (!app()->environment('local', 'staging')) {
            abort(403, 'Migrations can only be run in non-production environments');
        }

        // Or check for a specific token/secret
        if ($request->header('deployment_key') !== config('app.deployment_key')) {
            abort(401, 'Unauthorized');
        }

        try {
            // Run migrations
            Artisan::call('migrate', [
                '--force' => true,
                '--no-interaction' => true,
            ]);
//          Artisan::call('db:seed', ['--force' => true]);
            Artisan::call('config:clear');
            Artisan::call('route:clear');
            Artisan::call('view:clear');
            Artisan::call('cache:clear');

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Migrations completed successfully',
                'output' => $output
            ]);
//            return view('operation-success');

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
