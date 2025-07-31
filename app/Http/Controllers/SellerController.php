<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Seller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SellerController extends Controller
{
    public function index()
    {
        $query = Seller::query();

        if (request()->filled('search')) {
            $search = '%' . request()->search . '%';
            $query->where('nama', 'LIKE', $search)
                ->orWhere('nomor_telepon', 'LIKE', $search)
                ->orWhere('alamat', 'LIKE', $search);
        }

        $validColumns = ['nama', 'nomor_telepon'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $sellers = $query->with('province', 'city')->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Seller list retrieved', $sellers);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:sellers,nama',
            'nomor_telepon' => 'required|string|unique:sellers,nomor_telepon',
            'alamat' => 'required|string',
            'province_id' => 'required|exists:provinces,id',
            'city_id' => 'required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $seller = Seller::create($validator->validated());
        return Formatter::ApiResponse(200, 'Seller added', Seller::with('province', 'city')->find($seller->id));
    }

    public function show(Seller $seller)
    {
        $seller = $seller->load('province', 'city');
        return Formatter::ApiResponse(200, 'Seller found', $seller);
    }

    public function update(Request $request, Seller $seller)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:sellers,nama,' . $seller->id,
            'nomor_telepon' => 'sometimes|required|string|unique:sellers,nomor_telepon,' . $seller->id,
            'alamat' => 'sometimes|required|string',
            'province_id' => 'sometimes|required|exists:provinces,id',
            'city_id' => 'sometimes|required|exists:cities,id',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $seller->update($validator->validated());
        return Formatter::ApiResponse(200, 'Seller updated', Seller::with('province', 'city')->find($seller->id));
    }

    public function destroy(Seller $seller)
    {
        $seller->delete();
        return Formatter::ApiResponse(200, 'Seller removed');
    }
}
