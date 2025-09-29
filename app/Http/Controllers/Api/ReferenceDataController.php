<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Country;
use App\Models\ItemType;
use App\Models\ItemGender;
use App\Models\AgeGroup;

class ReferenceDataController extends Controller
{
    // ===== Countries =====
    public function createCountry(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:countries,code|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $country = Country::create([
            'active' => true,
            'name' => $request->name,
            'code' => $request->code,
        ]);
        return response()->json(['country' => $country, 'status' => true, 'code' => 200], 201);
    }

    public function getCountries(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        return response()->json([
            'countries' => Country::paginate($perPage),
            'status' => true,
            'code' => 200,
        ]);
    }

    public function getAllCountries(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json([
            'countries' => Country::all(),
            'status' => true,
            'code' => 200,
        ]);
    }

    public function getCountryById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $country = Country::find($id);
        if (! $country) {
            return response()->json(['message' => 'Country not found'], 404);
        }
        return response()->json(['country' => $country, 'status' => true, 'code' => 200]);
    }

    public function updateCountry(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $country = Country::find($id);
        if (! $country) {
            return response()->json(['message' => 'Country not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:countries,code,' . $id . '|max:10',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $country->update($validator->validated());
        return response()->json(['country' => $country, 'status' => true, 'code' => 200, 'message' => 'Country updated successfully']);
    }

    public function deleteCountry(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $country = Country::find($id);
        if (! $country) {
            return response()->json(['message' => 'Country not found'], 404);
        }
        $country->update(['active' => ! $country->active]);
        $status = $country->active ? 'activated' : 'deactivated';
        return response()->json(['country' => $country, 'status' => true, 'code' => 200, 'message' => "Country record {$status} successfully"]);
    }

    // ===== Item Types =====
    public function createItemType(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:item_types,code|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $itemType = ItemType::create(['active' => true, 'name' => $request->name, 'code' => $request->code, 'description' => $request->description]);
        return response()->json(['item_type' => $itemType, 'status' => true, 'code' => 200], 201);
    }

    public function getItemTypes(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        return response()->json(['item_types' => ItemType::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllItemTypes(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['item_types' => ItemType::all(), 'status' => true, 'code' => 200]);
    }

    public function getItemTypeById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $itemType = ItemType::find($id);
        if (! $itemType) {
            return response()->json(['message' => 'Item type not found'], 404);
        }
        return response()->json(['item_type' => $itemType, 'status' => true, 'code' => 200]);
    }

    public function updateItemType(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $itemType = ItemType::find($id);
        if (! $itemType) {
            return response()->json(['message' => 'Item type not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:item_types,code,' . $id . '|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $itemType->update($validator->validated());
        return response()->json(['item_type' => $itemType, 'status' => true, 'code' => 200, 'message' => 'Item type updated successfully']);
    }

    public function deleteItemType(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $itemType = ItemType::find($id);
        if (! $itemType) {
            return response()->json(['message' => 'Item type not found'], 404);
        }
        $itemType->update(['active' => ! $itemType->active]);
        $status = $itemType->active ? 'activated' : 'deactivated';
        return response()->json(['item_type' => $itemType, 'status' => true, 'code' => 200, 'message' => "Item type record {$status} successfully"]);
    }

    // ===== Item Genders =====
    public function createItemGender(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:item_genders,code|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $itemGender = ItemGender::create(['active' => true, 'name' => $request->name, 'code' => $request->code]);
        return response()->json(['item_gender' => $itemGender, 'status' => true, 'code' => 200], 201);
    }

    public function getItemGenders(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        return response()->json(['item_genders' => ItemGender::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllItemGenders(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['item_genders' => ItemGender::all(), 'status' => true, 'code' => 200]);
    }

    public function getItemGenderById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $itemGender = ItemGender::find($id);
        if (! $itemGender) {
            return response()->json(['message' => 'Item gender not found'], 404);
        }
        return response()->json(['item_gender' => $itemGender, 'status' => true, 'code' => 200]);
    }

    public function updateItemGender(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $itemGender = ItemGender::find($id);
        if (! $itemGender) {
            return response()->json(['message' => 'Item gender not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:item_genders,code,' . $id . '|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $itemGender->update($validator->validated());
        return response()->json(['item_gender' => $itemGender, 'status' => true, 'code' => 200, 'message' => 'Item gender updated successfully']);
    }

    public function deleteItemGender(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $itemGender = ItemGender::find($id);
        if (! $itemGender) {
            return response()->json(['message' => 'Item gender not found'], 404);
        }
        $itemGender->update(['active' => ! $itemGender->active]);
        $status = $itemGender->active ? 'activated' : 'deactivated';
        return response()->json(['item_gender' => $itemGender, 'status' => true, 'code' => 200, 'message' => "Item gender record {$status} successfully"]);
    }

    // ===== Age Groups =====
    public function createAgeGroup(Request $request)
    {
        if (! $request->user()->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|unique:age_groups,code|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $ageGroup = AgeGroup::create(['active' => true, 'name' => $request->name, 'code' => $request->code]);
        return response()->json(['age_group' => $ageGroup, 'status' => true, 'code' => 200], 201);
    }

    public function getAgeGroups(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $perPage = $request->input('per_page', 15);
        return response()->json(['age_groups' => AgeGroup::paginate($perPage), 'status' => true, 'code' => 200]);
    }

    public function getAllAgeGroups(Request $request)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        return response()->json(['age_groups' => AgeGroup::all(), 'status' => true, 'code' => 200]);
    }

    public function getAgeGroupById(Request $request, $id)
    {
        if (! $request->user()->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $ageGroup = AgeGroup::find($id);
        if (! $ageGroup) {
            return response()->json(['message' => 'Age group not found'], 404);
        }
        return response()->json(['age_group' => $ageGroup, 'status' => true, 'code' => 200]);
    }

    public function updateAgeGroup(Request $request, $id)
    {
        if (! $request->user()->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $ageGroup = AgeGroup::find($id);
        if (! $ageGroup) {
            return response()->json(['message' => 'Age group not found'], 404);
        }
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'code' => 'sometimes|required|string|unique:age_groups,code,' . $id . '|max:50',
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }
        $ageGroup->update($validator->validated());
        return response()->json(['age_group' => $ageGroup, 'status' => true, 'code' => 200, 'message' => 'Age group updated successfully']);
    }

    public function deleteAgeGroup(Request $request, $id)
    {
        if (! $request->user()->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }
        $ageGroup = AgeGroup::find($id);
        if (! $ageGroup) {
            return response()->json(['message' => 'Age group not found'], 404);
        }
        $ageGroup->update(['active' => ! $ageGroup->active]);
        $status = $ageGroup->active ? 'activated' : 'deactivated';
        return response()->json(['age_group' => $ageGroup, 'status' => true, 'code' => 200, 'message' => "Age group record {$status} successfully"]);
    }
}
