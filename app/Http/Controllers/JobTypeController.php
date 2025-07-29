<?php

namespace App\Http\Controllers;

use App\Custom\Formatter;
use App\Models\JobType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class JobTypeController extends Controller
{
    public function index()
    {
        $jobTypeQuery = JobType::query();

        if (request()->filled('search')) {
            $searchTerm = '%' . request()->search . '%';
            $jobTypeQuery->where('nama', 'LIKE', $searchTerm);
        }

        $validColumns = ['nama'];
        $sortBy = in_array(request()->sortBy, $validColumns) ? request()->sortBy : 'created_at';
        $sortDir = strtolower(request()->sortDir) === 'desc' ? 'DESC' : 'ASC';
        $jobTypeQuery->orderBy($sortBy, $sortDir);

        $size = min(max(request()->size ?? 10, 1), 100);

        $jobTypes = $jobTypeQuery->simplePaginate($size);

        return Formatter::ApiResponse(200, 'Job type list retrieved', $jobTypes);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'required|string|unique:job_types,nama',
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $newJobType = JobType::create($validated);

        return Formatter::ApiResponse(200, 'Job type added', JobType::find($newJobType->id));
    }

    public function show(JobType $jobType)
    {
        return Formatter::ApiResponse(200, 'Job type found', $jobType);
    }

    public function update(Request $request, JobType $jobType)
    {
        $validator = Validator::make($request->all(), [
            'nama' => 'sometimes|required|string|unique:job_types,nama,' . $jobType->id,
        ]);

        if ($validator->fails()) {
            return Formatter::ApiResponse(422, 'Validation failed', null, $validator->errors()->all());
        }

        $validated = $validator->validated();
        $jobType->update($validated);

        return Formatter::ApiResponse(200, 'Job type updated', JobType::find($jobType->id));
    }

    public function destroy(JobType $jobType)
    {
        $jobType->delete();
        return Formatter::ApiResponse(200, 'Job type removed');
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
                $v = Validator::make($row, ['nama' => 'required|string|unique:job_types,nama']);
                if ($v->fails()) {
                    $failed[] = "Row " . ($index + 1) . ": " . implode(', ', $v->errors()->all());
                    continue;
                }

                try {
                    \DB::beginTransaction();
                    $job = JobType::create($v->validated());
                    \DB::commit();
                    $inserted[] = $job;
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
