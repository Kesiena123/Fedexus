<?php

namespace App\Http\Controllers;

use App\Services\QuoteService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QuoteController extends Controller
{
    public function __invoke(Request $request, QuoteService $quotes): JsonResponse
    {
        $data = $request->validate([
            'origin_country' => ['required', 'string', 'size:2'],
            'origin_postal_code' => ['required', 'string'],
            'destination_country' => ['required', 'string', 'size:2'],
            'destination_postal_code' => ['required', 'string'],
            'weight_kg' => ['required', 'numeric', 'min:0.1', 'max:10000'],
            'declared_value' => ['nullable', 'numeric', 'min:0'],
            'service_level' => ['required', 'in:domestic_express,international_priority,freight'],
        ]);

        return response()->json(['quote' => $quotes->calculate($data)]);
    }
}
