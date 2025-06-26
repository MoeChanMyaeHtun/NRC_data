<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Nrc;

class NrcController extends Controller
{
    public function create()
    {
        $states = DB::table('nrc_states')
            ->select('code','name_mm')
            ->orderBy('name_mm')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->code => "{$item->name_mm}"];
            })
            ->toArray();

        $types = DB::table('nrc_types')
            ->select('code','name_mm')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->code => [
                    'mm' => $item->name_mm
                ]];
            })
            ->toArray();
        $townships = [];

        return view('nrc.create', compact('states', 'types', 'townships'));
    }

  public function getTownships($stateCode)
    {
        try {
            $stateExists = DB::table('nrc_states')
                ->where('code', $stateCode)
                ->exists();

            if (!$stateExists) {
                return response()->json([
                    'error' => 'Invalid state code'
                ], 404);
            }

            $townships = DB::table('nrc_townships')
                ->where('nrc_state_id', $stateCode)
                ->select('code', 'name_mm')
                ->orderBy('name_mm')
                ->get()
                ->mapWithKeys(function ($item) {
                    return [
                        $item->code => "({$item->name_mm})" 
                    ];
                })
                ->all(); 
            return response()->json($townships);

        } catch (\Exception $e) {
            \Log::error("Township Error: " . $e->getMessage());
            return response()->json([
                'error' => 'Server error',
                'debug' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'state_code' => [
                'required', 
                'string', 
                'size:2',
                Rule::exists('nrc_states', 'code')
            ],
            'township_code' => [
                'required',
                'string',
                'size:3',
                Rule::exists('nrc_townships', 'code')->where('state_code', $request->state_code)
            ],
            'type' => [
                'required',
                Rule::in(['N', 'P', 'E'])
            ],
            'number' => 'required|numeric|digits:6'
        ]);

        $nrc = Nrc::create($validated);

        return redirect()->back()
            ->with('success', 'NRC saved successfully: ' . $nrc->full_nrc);
    }
}