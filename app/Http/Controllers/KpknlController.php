<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Kpknl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class KpknlController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Kpknl::query();

        if (request()->filled('search')) {
            $search = '%' . request()->search . '%';
            $query->where('nama', 'LIKE', $search)
                ->orWhere('kode_satker', 'LIKE', $search)
                ->orWhere('alamat', 'LIKE', $search);
        }

        // Filter by province (by provinceCode prefix: first 2 digits)
        if (request()->filled('provinceCode')) {
            $provinceCode = request()->provinceCode;
            if (is_string($provinceCode) && strlen($provinceCode) == 2 && ctype_digit($provinceCode)) {
                $query->where('kode_satker', '>=', (int)($provinceCode . '0000'))
                    ->where('kode_satker', '<', (int)($provinceCode . '9999'));
            }
        }

        // Filter by city (by cityCode prefix: first 4 digits)
        if (request()->filled('cityCode')) {
            $cityCode = request()->cityCode;
            if (is_string($cityCode) && strlen($cityCode) == 4 && ctype_digit($cityCode)) {
                $query->where('kode_satker', '>=', (int)($cityCode . '00'))
                    ->where('kode_satker', '<', (int)($cityCode . '99'));
            }
        }

        $validColumns = ['nama', 'kode_satker', 'kota', 'provinsi'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $kpknls = $query->simplePaginate($size);

        return Formatter::ApiResponse(200, 'KPKNL list retrieved', $kpknls);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|max:255',
            'kode_satker' => 'required|integer|unique:kpknls,kode_satker',
            'alamat' => 'required|string',
            'kota' => 'required|string',
            'provinsi' => 'required|string',
            'nomor_telepon' => 'required|string',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $kpknl = Kpknl::create($validator->validated());
        return Formatter::ApiResponse(200, 'KPKNL added', Kpknl::find($kpknl->id));
    }

    /**
     * Display the specified resource.
     */
    public function show(Kpknl $kpknl)
    {
        return Formatter::ApiResponse(200, 'KPKNL found', $kpknl);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Kpknl $kpknl)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|max:255',
            'kode_satker' => 'sometimes|required|integer|unique:kpknls,kode_satker,' . $kpknl->id,
            'alamat' => 'sometimes|required|string',
            'kota' => 'sometimes|required|string',
            'provinsi' => 'sometimes|required|string',
            'nomor_telepon' => 'sometimes|required|string',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $kpknl->update($validator->validated());
        return Formatter::ApiResponse(200, 'KPKNL updated', Kpknl::find($kpknl->id));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Kpknl $kpknl)
    {
        $kpknl->delete();
        return Formatter::ApiResponse(200, 'KPKNL removed');
    }

    /**
     * Upload batch data from JSON or CSV
     */
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
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return Formatter::ApiResponse(422, 'Invalid JSON file');
                }
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
                $v = Validator::make($row, [
                    'nama' => 'required|string|max:255',
                    'kode_satker' => 'required|integer',
                    'alamat' => 'required|string',
                    'kota' => 'required|string',
                    'provinsi' => 'required|string',
                    'nomor_telepon' => 'required|string',
                ]);

                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();

                if (Kpknl::where('kode_satker', $validated['kode_satker'])->exists()) {
                    $failed[] = "Row " . ($index + 1) . ": Kode satker already exists";
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $kpknl = Kpknl::create($validated);
                    \DB::commit();
                    $inserted[] = $kpknl;
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
