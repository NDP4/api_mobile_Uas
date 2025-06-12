<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Models\ShippingTracking;
use GuzzleHttp\Exception\GuzzleException;

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

    public function getCouriers()
    {
        $couriers = [
            [
                'code' => 'jne',
                'name' => 'Jalur Nugraha Ekakurir (JNE)',
                'services' => ['REG', 'YES', 'OKE', 'JTR']
            ],
            [
                'code' => 'tiki',
                'name' => 'Titipan Kilat (TIKI)',
                'services' => ['REG', 'ECO', 'ONS']
            ],
            [
                'code' => 'pos',
                'name' => 'POS Indonesia',
                'services' => ['Kilat Khusus', 'Express', 'Reguler']
            ]
        ];

        return response()->json([
            'status' => 1,
            'data' => $couriers
        ]);
    }

    public function calculateShipping(Request $request)
    {
        try {
            $this->validate($request, [
                'destination' => 'required',
                'weight' => 'required|integer|min:1',
                'courier' => 'required|in:jne,tiki,pos',
                'order_id' => 'required|exists:orders_elsid,id'
            ]);

            // Use default origin from env
            $origin = env('RAJAONGKIR_ORIGIN', '501'); // Default to Jakarta Pusat if not set

            $response = $this->client->request('POST', $this->baseUrl . 'cost', [
                'headers' => [
                    'key' => $this->apiKey,
                    'content-type' => 'application/x-www-form-urlencoded'
                ],
                'form_params' => [
                    'origin' => $origin,
                    'destination' => $request->destination,
                    'weight' => $request->weight,
                    'courier' => strtolower($request->courier)
                ]
            ]);

            $result = json_decode($response->getBody()->getContents(), true);

            if (isset($result['rajaongkir']['results'][0]['costs'])) {
                $costs = $result['rajaongkir']['results'][0]['costs'];
                foreach ($costs as &$service) {
                    // Convert etd to integer days
                    $etd = str_replace(' HARI', '', $service['cost'][0]['etd']);
                    $etdParts = explode('-', $etd);
                    // Use the maximum value for estimation
                    $etdDays = isset($etdParts[1]) ? (int)$etdParts[1] : (int)$etdParts[0];
                    $service['etd_days'] = $etdDays;

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

            return response()->json([
                'status' => 1,
                'data' => $result['rajaongkir'] ?? []
            ]);
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
