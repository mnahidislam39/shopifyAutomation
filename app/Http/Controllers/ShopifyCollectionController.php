<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\ShopifyCollection;

class ShopifyCollectionController extends Controller
{
    public function bulkCreate(Request $request)
    {
        $shopDomain = $request->input('shop_domain');
        $accessToken = $request->input('access_token');
        $collections = $request->input('collections', []);

        if (!$shopDomain || !$accessToken) {
            return response()->json([
                'status' => 'error',
                'message' => 'Shop domain and access token are required.'
            ], 400);
        }

        $results = [];

        foreach ($collections as $item) {
            $type = $item['type'] ?? 'custom';

            $endpoint = $type === 'smart'
                ? "https://{$shopDomain}/admin/api/2026-07/smart_collections.json"
                : "https://{$shopDomain}/admin/api/2026-07/custom_collections.json";

            $payloadKey = $type === 'smart' ? 'smart_collection' : 'custom_collection';

            // 1. Mandatory / Core Payload
            $collectionData = [
                'title'  => $item['title'],
                'handle' => $item['handle'] ?? null,
            ];

            // 2. OPTIONAL: Description (HTML formatting supported)
            if (!empty($item['description'])) {
                $collectionData['body_html'] = $item['description'];
            }

            // 3. OPTIONAL: Theme Template (e.g., 'custom-layout' or null for 'Default collection')
            if (!empty($item['template_suffix'])) {
                $collectionData['template_suffix'] = $item['template_suffix'];
            }

            // 4. OPTIONAL: Collection Featured Image
            if (!empty($item['image_url'])) {
                $collectionData['image'] = [
                    'src' => $item['image_url'],
                    'alt' => $item['image_alt'] ?? $item['title'] // Optional image alt text
                ];
            }

            // 5. Smart Collection Rules
            if ($type === 'smart' && isset($item['rules'])) {
                $collectionData['rules'] = $item['rules'];
            }

            $payload = [$payloadKey => $collectionData];

            // Shopify API Request
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post($endpoint, $payload);

            if ($response->successful()) {
                $data = $response->json()[$payloadKey];

                ShopifyCollection::create([
                    'shop_domain' => $shopDomain,
                    'shopify_id'  => $data['id'] ?? null,
                    'title'       => $item['title'],
                    'handle'      => $item['handle'] ?? null,
                    'type'        => $type,
                    'status'      => 'success',
                ]);

                $results[] = [
                    'title'  => $item['title'],
                    'status' => 'success',
                    'data'   => $data
                ];
            } else {
                ShopifyCollection::create([
                    'shop_domain' => $shopDomain,
                    'shopify_id'  => null,
                    'title'       => $item['title'],
                    'handle'      => $item['handle'] ?? null,
                    'type'        => $type,
                    'status'      => 'failed',
                ]);

                $results[] = [
                    'title'  => $item['title'],
                    'status' => 'error',
                    'error'  => $response->json()
                ];
            }
        }

        return response()->json([
            'message' => 'Bulk collection creation process completed.',
            'results' => $results
        ]);
    }
}
