<?php

namespace App\Http\Controllers;

use App\Models\AuctionContent;
use App\Custom\Formatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuctionContentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = AuctionContent::query();

        if (request()->filled('search')) {
            $search = '%' . request()->search . '%';
            $query->whereHas('auction', function ($q) use ($search) {
                $q->where('nama_lot', 'LIKE', $search)
                    ->orWhere('kode_lot', 'LIKE', $search);
            })->orWhereHas('item', function ($q) use ($search) {
                $q->where('bukti_kepemilikan_no', 'LIKE', $search)
                    ->orWhere('nopol', 'LIKE', $search);
            })->orWhereHas('seller', function ($q) use ($search) {
                $q->where('nama', 'LIKE', $search);
            })->orWhereHas('organizer', function ($q) use ($search) {
                $q->where('nama', 'LIKE', $search);
            });
        }

        $validColumns = ['auction_id', 'item_id', 'seller_id', 'organizer_id', 'created_at'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $contents = $query->with([
            'auction',
            'item',
            'seller',
            'organizer',
        ])->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Auction content list retrieved', $contents);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'auction_id' => 'required|exists:auctions,id',
            'item_id' => 'required|exists:items,id',
            'seller_id' => 'required|exists:sellers,id',
            'organizer_id' => 'required|exists:organizers,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $content = AuctionContent::create($validator->validated());

        return Formatter::ApiResponse(200, 'Auction content created', AuctionContent::with([
            'auction',
            'item',
            'seller',
            'organizer',
        ])->find($content->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(AuctionContent $auctionContent)
    {
        $auctionContent = $auctionContent->load([
            'auction',
            'item',
            'seller',
            'organizer',
        ]);

        return Formatter::ApiResponse(200, 'Auction content found', $auctionContent);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, AuctionContent $auctionContent)
    {
        $validator = Validator::make($request->all(), [
            'auction_id' => 'sometimes|required|exists:auctions,id',
            'item_id' => 'sometimes|required|exists:items,id',
            'seller_id' => 'sometimes|required|exists:sellers,id',
            'organizer_id' => 'sometimes|required|exists:organizers,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $auctionContent->update($validator->validated());

        return Formatter::ApiResponse(200, 'Auction content updated', AuctionContent::with([
            'auction',
            'item',
            'seller',
            'organizer',
        ])->find($auctionContent->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(AuctionContent $auctionContent)
    {
        $auctionContent->delete();

        return Formatter::ApiResponse(200, 'Auction content deleted');
    }
}
