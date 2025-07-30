<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\Organizer;
use App\Models\Kpknl;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class OrganizerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $query = Organizer::query();

        if (request()->filled('search')) {
            $search = '%' . request()->search . '%';
            $query->where('nama_bank', 'LIKE', $search)
                ->orWhere('nama_unit_kerja', 'LIKE', $search);
        }

        $validColumns = ['nama_bank', 'nama_unit_kerja'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $query->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);
        $organizers = $query->simplePaginate($size);

        $organizers->getCollection()->transform(function ($organizer) {
            $kpknl = Kpknl::query()->where('nama', $organizer->nama_unit_kerja)->first();

            return [
                'id' => $organizer->id,
                'nama_bank' => $organizer->nama_bank,
                'nomor_telepon' => $organizer->nomor_telepon ?? $kpknl?->nomor_telepon,
                'alamat' => $organizer->alamat ?? $kpknl?->alamat,
                'nama_unit_kerja' => $organizer->nama_unit_kerja,
                'kpknl' => $kpknl,
                'created_at' => $organizer->created_at,
                'updated_at' => $organizer->updated_at,
            ];
        });

        return Formatter::ApiResponse(200, 'Organizer list retrieved', $organizers);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama_bank' => 'required|string|max:255',
            'nomor_telepon' => 'nullable|string',
            'alamat' => 'nullable|string',
            'nama_unit_kerja' => 'required|string|max:255',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $organizer = Organizer::create($validator->validated());
        return Formatter::ApiResponse(200, 'Organizer added', $this->formatOrganizer($organizer));
    }

    /**
     * Display the specified resource.
     */
    public function show(Organizer $organizer)
    {
        return Formatter::ApiResponse(200, 'Organizer found', $this->formatOrganizer($organizer));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Organizer $organizer)
    {
        $validator = Validator::make($request->all(), [
            'nama_bank' => 'sometimes|required|string|max:255',
            'nomor_telepon' => 'nullable|string',
            'alamat' => 'nullable|string',
            'nama_unit_kerja' => 'sometimes|required|string|max:255',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $organizer->update($validator->validated());
        return Formatter::ApiResponse(200, 'Organizer updated', $this->formatOrganizer($organizer));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Organizer $organizer)
    {
        $organizer->delete();
        return Formatter::ApiResponse(200, 'Organizer removed');
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
                    'nama_bank' => 'required|string|max:255',
                    'nomor_telepon' => 'nullable|string',
                    'alamat' => 'nullable|string',
                    'nama_unit_kerja' => 'required|string|max:255',
                ]);

                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                $validated = $v->validated();

                try {
                    \DB::beginTransaction();
                    $organizer = Organizer::create($validated);
                    \DB::commit();
                    $inserted[] = $this->formatOrganizer($organizer);
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

    /**
     * Format organizer response with merged fields and optional kpknl
     */
    private function formatOrganizer($organizer)
    {
        $kpknl = Kpknl::query()->where('nama', $organizer->nama_unit_kerja)->first();

        return [
            'id' => $organizer->id,
            'nama_bank' => $organizer->nama_bank,
            'nomor_telepon' => $organizer->nomor_telepon ?? $kpknl?->nomor_telepon,
            'alamat' => $organizer->alamat ?? $kpknl?->alamat,
            'nama_unit_kerja' => $organizer->nama_unit_kerja,
            'kpknl' => $kpknl,
            'created_at' => $organizer->created_at,
            'updated_at' => $organizer->updated_at,
        ];
    }
}
