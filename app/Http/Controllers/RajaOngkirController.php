<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;

class RajaOngkirController extends Controller
{
    protected $apiKey;
    protected $baseUrl;
    protected $client;

    public function __construct()
    {
        $this->apiKey = env('RAJAONGKIR_KEY');
        $this->baseUrl = env('RAJAONGKIR_URL');
        $this->client = new Client();
    }

    public function getProvinces()
    {
        try {
            $response = $this->client->request('GET', $this->baseUrl . 'province', [
                'headers' => ['key' => $this->apiKey]
            ]);

            return response()->json(json_decode($response->getBody()->getContents(), true));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getCities(Request $request)
    {
        try {
            $provinceId = $request->query('province');
            $url = $this->baseUrl . 'city';
            if ($provinceId) {
                $url .= "?province={$provinceId}";
            }

            $response = $this->client->request('GET', $url, [
                'headers' => ['key' => $this->apiKey]
            ]);

            return response()->json(json_decode($response->getBody()->getContents(), true));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function calculateShipping(Request $request)
    {
        try {
            $this->validate($request, [
                'origin' => 'required',
                'destination' => 'required',
                'weight' => 'required|integer|min:1',
                'courier' => 'required|in:jne,tiki,pos'
            ]);

            $response = $this->client->request('POST', $this->baseUrl . 'cost', [
                'headers' => ['key' => $this->apiKey],
                'form_params' => [
                    'origin' => $request->origin,
                    'destination' => $request->destination,
                    'weight' => $request->weight,
                    'courier' => $request->courier
                ]
            ]);

            return response()->json(json_decode($response->getBody()->getContents(), true));
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
}
