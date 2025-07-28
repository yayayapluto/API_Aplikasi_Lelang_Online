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

    public function uploadBatchData(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:json,csv|max:10240',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $file = $request->file('file');
        $data = [];

        try {
            if ($file->getClientOriginalExtension() === 'json') {
                $json = json_decode(file_get_contents($file), true);
                if (json_last_error() !== JSON_ERROR_NONE) return Formatter::ApiResponse(422, 'Invalid JSON');
                $data = $json;
            } elseif ($file->getClientOriginalExtension() === 'csv') {
                $csv = array_map('str_getcsv', file($file->getPathname()));
                $header = array_shift($csv);
                foreach ($csv as $row) {
                    if (count($row) === count($header)) {
                        $data[] = array_combine($header, $row);
                    }
                }
            }

            $inserted = [];
            $failed = [];

            foreach ($data as $index => $row) {
                $v = Validator::make($row, ['nama' => 'required|string|unique:item_types,nama']);
                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $item = ItemType::create($v->validated());
                    \DB::commit();
                    $inserted[] = $item;
                } catch (\Exception $e) {
                    \DB::rollBack();
                    $failed[] = "Row " . ($index + 1) . ": Save failed";
                }
            }

            return Formatter::ApiResponse(200, count($inserted) ? 'Batch processed' : 'No data inserted', compact('inserted', 'failed'));
        } catch (\Exception $e) {
            return Formatter::ApiResponse(500, 'Processing failed', null, [$e->getMessage()]);
        }
    }
}
