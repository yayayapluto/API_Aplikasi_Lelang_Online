<?php

namespace App\Http\Controllers;

use App\Models\Auction;
use App\Custom\Formatter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AuctionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Auction::query();

        if (request()->filled('search')) {
            $search = '%' . request()->search . '%';
            $query->where('nama_lot', 'LIKE', $search)
                ->orWhere('kode_lot', 'LIKE', $search)
                ->orWhere('cara_penawaran', 'LIKE', $search)
                ->orWhereHas('province', function ($q) use ($search) {
                    $q->where('name', 'LIKE', $search);
                })
                ->orWhereHas('kpknl', function ($q) use ($search) {
                    $q->where('name', 'LIKE', $search);
                });
        }

        $validColumns = [
            'nama_lot', 'kode_lot', 'nilai_limit', 'nilai_jaminan',
            'tanggal_batas_jaminan', 'province_id', 'kpknl_id',
            'tanggal_mulai', 'tanggal_selesai', 'status', 'cara_penawaran', 'created_at'
        ];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $auctions = $query->with([
            'province',
            'kpknl',
        ])->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Auction list retrieved', $auctions);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_lot' => 'required|string|max:255|unique:auctions,nama_lot',
            'kode_lot' => 'required|string|max:50|unique:auctions,kode_lot',
            'nilai_limit' => 'required|integer|min:0',
            'nilai_jaminan' => 'required|integer|min:0',
            'tanggal_batas_jaminan' => 'required|date',
            'province_id' => 'required|exists:provinces,id',
            'kpknl_id' => 'required|exists:kpknls,id',
            'tanggal_mulai' => 'required|date',
            'tanggal_selesai' => 'required|date|after_or_equal:tanggal_mulai',
            'status' => 'required|in:TAYANG,SELESAI',
            'cara_penawaran' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $auction = Auction::create($validator->validated());

        return Formatter::ApiResponse(200, 'Auction created', Auction::with([
            'province',
            'kpknl',
        ])->find($auction->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(Auction $auction)
    {
        $auction = $auction->load([
            'province',
            'kpknl',
        ]);

        return Formatter::ApiResponse(200, 'Auction found', $auction);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Auction $auction)
    {
        $validator = Validator::make($request->all(), [
            'nama_lot' => 'sometimes|required|string|max:255|unique:auctions,nama_lot,' . $auction->id,
            'kode_lot' => 'sometimes|required|string|max:50|unique:auctions,kode_lot,' . $auction->id,
            'nilai_limit' => 'sometimes|required|integer|min:0',
            'nilai_jaminan' => 'sometimes|required|integer|min:0',
            'tanggal_batas_jaminan' => 'sometimes|required|date',
            'province_id' => 'sometimes|required|exists:provinces,id',
            'kpknl_id' => 'sometimes|required|exists:kpknls,id',
            'tanggal_mulai' => 'sometimes|required|date',
            'tanggal_selesai' => 'sometimes|required|date|after_or_equal:tanggal_mulai',
            'status' => 'sometimes|required|in:TAYANG,SELESAI',
            'cara_penawaran' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $auction->update($validator->validated());

        return Formatter::ApiResponse(200, 'Auction updated', Auction::with([
            'province',
            'kpknl',
        ])->find($auction->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Auction $auction)
    {
        $auction->delete();

        return Formatter::ApiResponse(200, 'Auction deleted');
    }
}
