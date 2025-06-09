<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Models\ShippingTracking;

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
                'courier' => 'required|in:jne,tiki,pos',
                'order_id' => 'required|exists:orders_elsid,id'
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

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['rajaongkir']['results'][0]['costs'])) {
                $costs = $result['rajaongkir']['results'][0]['costs'];
                foreach ($costs as &$service) {
                    // Convert etd to integer days
                    $etd = str_replace(' HARI', '', $service['cost'][0]['etd']);
                    $etd = (int) str_replace('-', '', $etd); // Handle ranges like "2-3"
                    $service['etd_days'] = $etd;

                    // Save shipping tracking info
                    ShippingTracking::updateOrCreate(
                        ['order_id' => $request->order_id],
                        [
                            'courier' => $request->courier,
                            'service' => $service['service'],
                            'etd_days' => $etd,
                            'status' => 'pending'
                        ]
                    );
                }
                $result['rajaongkir']['results'][0]['costs'] = $costs;
            }

            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getShippingStatus($orderId)
    {
        try {
            $tracking = ShippingTracking::where('order_id', $orderId)->firstOrFail();
            return response()->json([
                'status' => 1,
                'data' => $tracking
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 0,
                'message' => 'Shipping tracking not found'
            ], 404);
        }
    }
}
