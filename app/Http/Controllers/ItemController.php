<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Custom\Formatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Item::query();

        if (request()->filled('search')) {
            $search = '%' . request()->search . '%';
            $query->where('bukti_kepemilikan', 'LIKE', $search)
                ->orWhere('bukti_kepemilikan_no', 'LIKE', $search)
                ->orWhere('alamat', 'LIKE', $search)
                ->orWhere('nopol', 'LIKE', $search)
                ->orWhere('stnk', 'LIKE', $search);
        }

        $validColumns = [
            'bukti_kepemilikan', 'bukti_kepemilikan_no', 'bukti_kepemilikan_tgl',
            'alamat', 'luas', 'stnk', 'nomor_rangka', 'nopol', 'tahun', 'warna',
            'item_type_id', 'object_type_id', 'category_id', 'created_at'
        ];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $items = $query->with([
            'itemType',
            'objectType',
            'category',
        ])->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Item list retrieved', $items);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'bukti_kepemilikan' => 'required|string|max:255',
            'bukti_kepemilikan_no' => 'required|string|max:255|unique:items,bukti_kepemilikan_no',
            'bukti_kepemilikan_tgl' => 'required|date',
            'alamat' => 'required|string',
            'luas' => 'nullable|string|max:50',
            'stnk' => 'nullable|string|max:255',
            'nomor_rangka' => 'nullable|string|max:255',
            'nopol' => 'nullable|string|max:20',
            'tahun' => 'nullable|string|max:4',
            'warna' => 'nullable|string|max:50',
            'item_type_id' => 'required|exists:item_types,id',
            'object_type_id' => 'required|exists:object_types,id',
            'category_id' => 'required|exists:categories,id',
            'photos' => 'nullable|array',
            'photos.*' => 'file|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        $photos = $request->file('photos');
        unset($validated['photos']);

        // Create item
        $item = Item::create($validated);

        // Handle file uploads and link via ItemPhoto
        if ($photos) {
            foreach ($photos as $photo) {
                $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $photo->getClientOriginalName());
                $photo->storeAs('public/' . $filename);

                $fileUrl = 'storage/' . $filename;

                // Save file first
                $file = File::create([
                    'file_url' => $fileUrl,
                ]);

                // Link file to item via item_photos
                ItemPhoto::create([
                    'item_id' => $item->id,
                    'file_id' => $file->id,
                ]);
            }
        }

        return Formatter::ApiResponse(200, 'Item created', Item::with([
            'itemType',
            'objectType',
            'category',
            'photos',
        ])->find($item->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(Item $item)
    {
        $item = $item->load([
            'itemType',
            'objectType',
            'category',
        ]);

        return Formatter::ApiResponse(200, 'Item found', $item);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Item $item)
    {
        $validator = Validator::make($request->all(), [
            'bukti_kepemilikan' => 'sometimes|required|string|max:255',
            'bukti_kepemilikan_no' => 'sometimes|required|string|max:255|unique:items,bukti_kepemilikan_no,' . $item->id,
            'bukti_kepemilikan_tgl' => 'sometimes|required|date',
            'alamat' => 'sometimes|required|string',
            'luas' => 'nullable|string|max:50',
            'stnk' => 'nullable|string|max:255',
            'nomor_rangka' => 'nullable|string|max:255',
            'nopol' => 'nullable|string|max:20',
            'tahun' => 'nullable|string|max:4',
            'warna' => 'nullable|string|max:50',
            'item_type_id' => 'sometimes|required|exists:item_types,id',
            'object_type_id' => 'sometimes|required|exists:object_types,id',
            'category_id' => 'sometimes|required|exists:categories,id',
            'photos' => 'nullable|array',
            'photos.*' => 'file|mimes:jpg,jpeg,png|max:2048',
            'delete_photos' => 'nullable|array',
            'delete_photos.*' => 'exists:item_photos,file_id,item_id,' . $item->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();

        $photos = $request->file('photos');
        $deletePhotos = $request->input('delete_photos', []);

        unset($validated['photos']);
        unset($validated['delete_photos']);

        $item->update($validated);

        // Delete specified photos (unlink + delete file)
        if ($deletePhotos) {
            $fileIds = $deletePhotos;
            $files = File::whereIn('id', $fileIds)->get();

            foreach ($files as $file) {
                $path = str_replace('storage/', 'public/', $file->file_url);
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }

                // Delete from item_photos first
                ItemPhoto::where('item_id', $item->id)->where('file_id', $file->id)->delete();
                $file->delete();
            }
        }

        // Upload and attach new photos
        if ($photos) {
            foreach ($photos as $photo) {
                $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $photo->getClientOriginalName());
                $photo->storeAs('public/' . $filename);

                $fileUrl = 'storage/' . $filename;

                $file = File::create([
                    'file_url' => $fileUrl,
                ]);

                ItemPhoto::create([
                    'item_id' => $item->id,
                    'file_id' => $file->id,
                ]);
            }
        }

        return Formatter::ApiResponse(200, 'Item updated', Item::with([
            'itemType',
            'objectType',
            'category',
            'photos',
        ])->find($item->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Item $item)
    {
        $item->delete();

        return Formatter::ApiResponse(200, 'Item deleted');
    }
}
