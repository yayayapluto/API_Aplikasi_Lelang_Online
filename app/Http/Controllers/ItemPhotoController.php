<?php

namespace App\Http\Controllers;

use App\Models\ItemPhoto;
use App\Models\File;
use App\Custom\Formatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ItemPhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = ItemPhoto::query();

        // Optional search by item_id or file_id
        if (request()->filled('search')) {
            $search = request()->search;
            $query->where('item_id', $search)
                ->orWhere('file_id', $search);
        }

        $validColumns = ['item_id', 'file_id', 'created_at'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $itemPhotos = $query->with(['item', 'file'])->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Item photos list retrieved', $itemPhotos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'item_id' => 'required|exists:items,id',
            'photos' => 'required|array',
            'photos.*' => 'file|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $item = ItemPhoto::where('item_id', $request->item_id)->first()?->item ?? Item::find($request->item_id);
        $uploadedFiles = $request->file('photos');

        foreach ($uploadedFiles as $photo) {
            $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $photo->getClientOriginalName());
            $photo->storeAs('public/' . $filename);

            $fileUrl = 'storage/' . $filename;

            $file = File::create(['file_url' => $fileUrl]);

            ItemPhoto::create([
                'item_id' => $item->id,
                'file_id' => $file->id,
            ]);
        }

        return Formatter::ApiResponse(200, 'Photos attached to item', [
            'item_id' => $item->id,
            'photos_count' => count($uploadedFiles),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(ItemPhoto $itemPhoto)
    {
        $itemPhoto->load(['item', 'file']);
        return Formatter::ApiResponse(200, 'Item photo found', $itemPhoto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, ItemPhoto $itemPhoto)
    {
        // This would typically not be used to change item/file ID
        // But if needed, allow reassignment

        $validator = Validator::make($request->all(), [
            'item_id' => 'sometimes|required|exists:items,id',
            'file_id' => 'sometimes|required|exists:files,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        // Prevent invalid re-linking if needed
        $itemPhoto->update($validated);

        return Formatter::ApiResponse(200, 'Item photo updated', ItemPhoto::with(['item', 'file'])->find($itemPhoto->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(ItemPhoto $itemPhoto)
    {
        $file = $itemPhoto->file;

        // Delete physical file if no other item is using it
        if ($file) {
            $isShared = ItemPhoto::where('file_id', $file->id)
                ->where('id', '!=', $itemPhoto->id)
                ->exists();

            if (!$isShared) {
                $path = str_replace('storage/', 'public/', $file->file_url);
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
                $file->delete();
            }
        }

        $itemPhoto->delete();

        return Formatter::ApiResponse(200, 'Item photo removed');
    }
}
