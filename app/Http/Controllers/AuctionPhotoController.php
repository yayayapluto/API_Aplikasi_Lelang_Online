<?php

namespace App\Http\Controllers;

use App\Models\AuctionPhoto;
use App\Models\File;
use App\Custom\Formatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class AuctionPhotoController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = AuctionPhoto::query();

        // Optional search by auction_id
        if (request()->filled('search')) {
            $search = request()->search;
            $query->whereHas('auction', function ($q) use ($search) {
                $q->where('nama_lot', 'LIKE', "%$search%")
                    ->orWhere('kode_lot', 'LIKE', "%$search%");
            })->orWhere('auction_id', $search);
        }

        $validColumns = ['auction_id', 'file_id', 'created_at'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $photos = $query->with(['auction', 'file'])->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Auction photos list retrieved', $photos);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auction_id' => 'required|exists:auctions,id',
            'photos' => 'required|array',
            'photos.*' => 'file|mimes:jpg,jpeg,png|max:2048',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $auctionId = $request->auction_id;
        $files = $request->file('photos');

        foreach ($files as $photo) {
            $filename = 'uploads/' . uniqid() . '_' . str_replace(' ', '_', $photo->getClientOriginalName());
            $photo->storeAs('public/' . $filename);

            $fileUrl = 'storage/' . $filename;

            $file = File::create(['file_url' => $fileUrl]);

            AuctionPhoto::create([
                'auction_id' => $auctionId,
                'file_id' => $file->id,
            ]);
        }

        return Formatter::ApiResponse(200, 'Photos attached to auction', [
            'auction_id' => $auctionId,
            'photos_count' => count($files),
        ]);
    }

    /**
     * Display the specified resource.
     */
    public function show(AuctionPhoto $auctionPhoto)
    {
        $auctionPhoto->load(['auction', 'file']);
        return Formatter::ApiResponse(200, 'Auction photo found', $auctionPhoto);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AuctionPhoto $auctionPhoto)
    {
        // Optional: allow reassigning to another auction
        $validator = Validator::make($request->all(), [
            'auction_id' => 'sometimes|required|exists:auctions,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $auctionPhoto->update($validator->validated());

        return Formatter::ApiResponse(200, 'Auction photo updated', AuctionPhoto::with(['auction', 'file'])->find($auctionPhoto->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuctionPhoto $auctionPhoto)
    {
        $file = $auctionPhoto->file;

        // Delete file only if no other auction is using it
        if ($file) {
            $isShared = AuctionPhoto::where('file_id', $file->id)
                ->where('id', '!=', $auctionPhoto->id)
                ->exists();

            if (!$isShared) {
                $path = str_replace('storage/', 'public/', $file->file_url);
                if (Storage::exists($path)) {
                    Storage::delete($path);
                }
                $file->delete();
            }
        }

        $auctionPhoto->delete();

        return Formatter::ApiResponse(200, 'Auction photo removed');
    }
}
