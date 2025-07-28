<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\ItemType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ItemTypeController extends Controller
{
    public function index()
    {
        $itemTypeQuery = ItemType::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $itemTypeQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ['nama'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $itemTypeQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $itemTypes = $itemTypeQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Item type list retrieved', $itemTypes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:item_types,nama',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newItemType = ItemType::create($validated);

        return Formatter::ApiResponse(200, 'Item type added', ItemType::find($newItemType->id));
    }

    public function show(ItemType $itemType)
    {
        return Formatter::ApiResponse(200, 'Item type found', $itemType);
    }

    public function update(Request $request, ItemType $itemType)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:item_types,nama,' . $itemType->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $itemType->update($validated);

        return Formatter::ApiResponse(200, 'Item type updated', $itemType);
    }

    public function destroy(ItemType $itemType)
    {
        $itemType->delete();
        return Formatter::ApiResponse(200, 'Item type removed');
    }
}
