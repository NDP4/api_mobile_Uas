<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class RajaOngkirController extends Controller
{
    protected $apiKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->apiKey = env('RAJAONGKIR_KEY');
        $this->baseUrl = env('RAJAONGKIR_URL');
    }

    public function getProvinces()
    {
        $response = Http::withHeaders([
            'key' => $this->apiKey
        ])->get($this->baseUrl . 'province');

        return response()->json($response->json());
    }

    public function getCities(Request $request)
    {
        $provinceId = $request->query('province');
        $url = $this->baseUrl . 'city';

        if ($provinceId) {
            $url .= "?province={$provinceId}";
        }

        $response = Http::withHeaders([
            'key' => $this->apiKey
        ])->get($url);

        return response()->json($response->json());
    }

    public function calculateShipping(Request $request)
    {
        $this->validate($request, [
            'origin' => 'required',
            'destination' => 'required',
            'weight' => 'required|integer|min:1',
            'courier' => 'required|in:jne,tiki,pos'
        ]);

        $response = Http::withHeaders([
            'key' => $this->apiKey
        ])->post($this->baseUrl . 'cost', [
            'origin' => $request->origin,
            'destination' => $request->destination,
            'weight' => $request->weight,
            'courier' => $request->courier
        ]);

        return response()->json($response->json());
    }
}
