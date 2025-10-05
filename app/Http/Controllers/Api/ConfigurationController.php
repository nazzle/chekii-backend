<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Configuration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ConfigurationController extends Controller
{
    public function createConfiguration(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_CONFIGURATIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|unique:configurations,company_name|max:100',
            'address' => 'required|string',
            'company_logo' => 'nullable|string',
            'website' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string',
            'return_policy' => 'required|string',
            'currency_code' => 'required|string',
            'currency_symbol' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $configuration = Configuration::create($validator->validated() + ['active' => true]);
        return response()->json(['configuration' => $configuration, 'status' => true, 'code' => 200], 201);
    }

    public function getPaginatedConfigurations(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_CONFIGURATIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        return response()->json(['configurations' => Configuration::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllConfigurations(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_CONFIGURATIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['configurations' => Configuration::all(), 'status' => true, 'code' => 200]);
    }

    public function getConfigurationById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_CONFIGURATIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $config = Configuration::find($id);
        if (! $config) {
            return response()->json(['message' => 'Configuration not found'], 404);
        }
        return response()->json(['configuration' => $config, 'status' => true, 'code' => 200]);
    }

    public function updateConfiguration(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_CONFIGURATIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $config = Configuration::find($id);
        if (! $config) {
            return response()->json(['message' => 'Configuration not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string|unique:configurations,key|max:100',
            'address' => 'required|string',
            'company_logo' => 'nullable|string',
            'website' => 'required|string',
            'email' => 'required|string',
            'phone' => 'required|string',
            'return_policy' => 'required|string',
            'currency_code' => 'required|string',
            'currency_symbol' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $config->update($validator->validated());
        return response()->json(['configuration' => $config, 'status' => true, 'code' => 200, 'message' => 'Configuration updated successfully']);
    }

    public function deleteConfiguration(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_CONFIGURATIONS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $config = Configuration::find($id);
        if (! $config) {
            return response()->json(['message' => 'Configuration not found'], 404);
        }
        $config->update(['active' => ! $config->active]);
        $status = $config->active ? 'activated' : 'deactivated';
        return response()->json(['configuration' => $config, 'status' => true, 'code' => 200, 'message' => "Configuration record {$status} successfully"]);
    }
}
