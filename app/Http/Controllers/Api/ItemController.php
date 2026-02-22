<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ItemController extends Controller
{
    /**
     * Serve item image from app's public/items-img (works when public files
     * are deployed elsewhere, e.g. production: files in /injini_app/public/items-img).
     * Public route so <img src="..."> works without auth.
     */
    public function serveItemImage(string $filename)
    {
        // Only allow safe filenames (UUID.ext style), no path traversal
        if (! preg_match('/^[a-zA-Z0-9_\-]+\.(jpeg|jpg|png|gif|webp)$/', $filename)) {
            abort(404);
        }
        $path = public_path('items-img/' . $filename);
        if (! File::isFile($path)) {
            abort(404);
        }
        return response()->file($path, [
            'Content-Type' => File::mimeType($path),
        ]);
    }

    // Create new item
    public function createItem(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('CREATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $validator = Validator::make($request->all(), [
            'barcode' => 'nullable|string|unique:items,barcode',
            'item_code' => 'string|unique:items,item_code',
            'description' => 'nullable|string',
            'item_image' => 'nullable', // file upload or base64 data URL from frontend
            'buying_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'gender' => 'nullable|in:male,female,unisex',
            'category_id' => 'required|exists:categories,id',
            'type_id' => 'required|exists:item_types,id',
            'age_id' => 'required|exists:age_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $itemImagePath = $this->processItemImageFromRequest($request);
        if ($itemImagePath === false) {
            return response()->json(['errors' => ['item_image' => ['Invalid image. Use a file upload or a base64 data URL (e.g. data:image/jpeg;base64,...). Allowed: jpeg, png, gif, webp. Max size: 2MB.']]], 422);
        }

        $item = Item::create([
            'active' => true,
            'barcode' => $request->barcode,
            'item_code' => $request->item_code,
            'description' => $request->description,
            'item_image' => $itemImagePath,
            'buying_price' => $request->buying_price,
            'selling_price' => $request->selling_price,
            'gender' => $request->gender,
            'category_id' => $request->category_id,
            'type_id' => $request->type_id,
            'age_id' => $request->age_id,
        ]);

        // Load category relationship for response
        $item->load('category');

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true,
            'message' => 'Item created successfully'
        ], 201);
    }

    // Get paginated list of items
    public function getPaginatedItems(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $perPage = min((int) $request->input('per_page', 15), 100);
        $perPage = max($perPage, 1);
        $items = Item::with(['category', 'inventory'])->paginate($perPage);

        return response()->json([
            'items' => $items,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get non-paginated list of items
    public function getAllItems(Request $request)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $items = Item::with(['category', 'inventory'])->get();

        return response()->json([
            'items' => $items,
            'code' => 200,
            'status' => true
        ]);
    }

    // Get item by ID
    public function getItemById(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('VIEW_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $item = Item::with(['category', 'inventory'])->find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true
        ]);
    }

    // Update item
    public function updateItem(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('UPDATE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $item = Item::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'barcode' => 'nullable|string|unique:items,barcode,' . $id,
            'item_code' => 'nullable|string|unique:items,item_code,' . $id,
            'description' => 'nullable|string',
            'item_image' => 'nullable', // file upload or base64 data URL from frontend
            'buying_price' => 'sometimes|required|numeric|min:0',
            'selling_price' => 'sometimes|required|numeric|min:0',
            'gender' => 'nullable|in:male,female,unisex',
            'category_id' => 'sometimes|required|exists:categories,id',
            'type_id' => 'sometimes|required|exists:item_types,id',
            'age_id' => 'sometimes|required|exists:age_groups,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $updateData = $validator->validated();
        unset($updateData['item_image']); // we set it below from processed image

        $itemImagePath = $this->processItemImageFromRequest($request);
        if ($itemImagePath === false) {
            return response()->json(['errors' => ['item_image' => ['Invalid image. Use a file upload or a base64 data URL (e.g. data:image/jpeg;base64,...). Allowed: jpeg, png, gif, webp. Max size: 2MB.']]], 422);
        }
        if ($itemImagePath !== null) {
            $this->deleteItemImage($item->item_image);
            $updateData['item_image'] = $itemImagePath;
        }

        $item->update($updateData);
        $item->load(['category', 'inventory']);

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true,
            'message' => 'Item updated successfully'
        ]);
    }

    // Delete item (soft delete by changing active status)
    public function deleteItem(Request $request, $id)
    {
        $user = $request->user();
        if (!$user->hasPermission('DELETE_PRODUCTS')) {
            return response()->json(['message' => 'Access denied'], 403);
        }

        $item = Item::find($id);
        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        // Check if item has associated inventory or movements
        if ($item->inventory || $item->movements()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete item that has associated inventory or movements'
            ], 400);
        }

        // Change active state to false instead of hard delete
        $item->update(['active' => !$item->active]);
        $status = $item->active ? 'activated' : 'deactivated';

        return response()->json([
            'item' => $item,
            'code' => 200,
            'status' => true,
            'message' => "Item record {$status} successfully"
        ]);
    }

    /**
     * Process item_image from request: file upload or base64 data URL.
     * Creates items-img folder if it does not exist.
     *
     * @return string|null|false Relative path (e.g. items-img/abc123.jpg), null if no image, false if invalid
     */
    private function processItemImageFromRequest(Request $request): string|null|false
    {
        $maxSizeBytes = 2 * 1024 * 1024; // 2MB
        $allowedMimes = ['jpeg', 'jpg', 'png', 'gif', 'webp'];

        if ($request->hasFile('item_image')) {
            $file = $request->file('item_image');
            $mime = $file->getClientMimeType();
            $extension = strtolower($file->getClientOriginalExtension());
            if (! in_array($extension, $allowedMimes) || $file->getSize() > $maxSizeBytes) {
                return false;
            }
            return $this->storeItemImageFile($file);
        }

        $value = $request->input('item_image');
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_string($value) || ! preg_match('/^data:image\/(\w+);base64,(.+)$/s', $value, $matches)) {
            return false;
        }

        $mimePart = strtolower($matches[1]);
        $base64 = $matches[2];
        $allowedMimeParts = ['jpeg', 'jpg', 'png', 'gif', 'webp'];
        if (! in_array($mimePart, $allowedMimeParts)) {
            return false;
        }

        $binary = base64_decode($base64, true);
        if ($binary === false || strlen($binary) > $maxSizeBytes) {
            return false;
        }

        $extension = ($mimePart === 'jpeg' || $mimePart === 'jpg') ? 'jpg' : $mimePart;

        return $this->storeItemImageFromBinary($binary, $extension);
    }

    /**
     * Store uploaded file in items-img folder.
     *
     * @return string Relative path for storage in DB (e.g. items-img/abc123.jpg)
     */
    private function storeItemImageFile($file): string
    {
        $folder = public_path('items-img');
        File::ensureDirectoryExists($folder);

        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid() . '.' . $extension;
        $file->move($folder, $filename);

        return 'items-img/' . $filename;
    }

    /**
     * Store binary image content in items-img folder.
     *
     * @return string Relative path for storage in DB (e.g. items-img/abc123.jpg)
     */
    private function storeItemImageFromBinary(string $binary, string $extension): string
    {
        $folder = public_path('items-img');
        File::ensureDirectoryExists($folder);

        $filename = Str::uuid() . '.' . $extension;
        File::put($folder . '/' . $filename, $binary);

        return 'items-img/' . $filename;
    }

    /**
     * Delete item image file from disk if it exists.
     */
    private function deleteItemImage(?string $path): void
    {
        if ($path && File::exists(public_path($path))) {
            File::delete(public_path($path));
        }
    }
}
