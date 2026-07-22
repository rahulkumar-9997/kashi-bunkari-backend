<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\State;

class StateController extends Controller
{
    public function list()
    {
        $states = State::orderBy('name')->get(['id', 'code', 'name']);
        return response()->json([
            'success' => true,
            'message' => 'States fetched successfully.',
            'data' => $states,
        ]);
    }
}
