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
                'success' => false,
                'message' => 'Shop domain and Access token are required.'
            ], 400);
        }

        $results = [];

        foreach ($collections as $collection) {
            $title = $collection['title'] ?? null;
            $type = strtolower($collection['type'] ?? 'custom');
            $handle = $collection['handle'] ?? null;
            $description = $collection['description'] ?? '';
            $templateSuffix = $collection['template_suffix'] ?? null;
            $imageUrl = $collection['image_url'] ?? null;

            if (!$title) {
                continue;
            }

            try {
                if ($type === 'smart') {
                    $payload = [
                        'smart_collection' => [
                            'title' => $title,
                            'body_html' => $description,
                            'rules' => $collection['rules'] ?? []
                        ]
                    ];

                    if ($handle) $payload['smart_collection']['handle'] = $handle;
                    if ($templateSuffix) $payload['smart_collection']['template_suffix'] = $templateSuffix;
                    if ($imageUrl) $payload['smart_collection']['image'] = ['src' => $imageUrl];

                    $response = Http::withHeaders([
                        'X-Shopify-Access-Token' => $accessToken,
                        'Content-Type' => 'application/json',
                    ])->post("https://{$shopDomain}/admin/api/2026-01/smart_collections.json", $payload);

                } else {
                    $payload = [
                        'custom_collection' => [
                            'title' => $title,
                            'body_html' => $description,
                        ]
                    ];

                    if ($handle) $payload['custom_collection']['handle'] = $handle;
                    if ($templateSuffix) $payload['custom_collection']['template_suffix'] = $templateSuffix;
                    if ($imageUrl) $payload['custom_collection']['image'] = ['src' => $imageUrl];

                    $response = Http::withHeaders([
                        'X-Shopify-Access-Token' => $accessToken,
                        'Content-Type' => 'application/json',
                    ])->post("https://{$shopDomain}/admin/api/2026-01/custom_collections.json", $payload);
                }

                if ($response->successful()) {
                    $responseData = $response->json();

                    $createdData = $responseData['smart_collection'] ?? $responseData['custom_collection'] ?? [];
                    $shopifyId = $createdData['id'] ?? null;
                    $createdHandle = $createdData['handle'] ?? $handle;

                    // ---> এখানে ডাটাবেজে সব ফিল্ড সেভ করা হচ্ছে <---
                    ShopifyCollection::create([
                        'shop_domain'     => $shopDomain,
                        'shopify_id'      => $shopifyId ? "gid://shopify/Collection/" . $shopifyId : null,
                        'title'           => $title,
                        'handle'          => $createdHandle,
                        'type'            => $type,
                        'description'     => $description,
                        'template_suffix' => $templateSuffix,
                        'image_url'       => $imageUrl,
                        'status'          => 'success',
                    ]);

                    $results[] = [
                        'title' => $title,
                        'status' => 'success',
                        'message' => 'Collection Created Successfully'
                    ];
                } else {
                    $results[] = [
                        'title' => $title,
                        'status' => 'failed',
                        'message' => json_encode($response->json()['errors'] ?? $response->body())
                    ];
                }

            } catch (\Exception $e) {
                $results[] = [
                    'title' => $title,
                    'status' => 'failed',
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results
        ]);
    }
}
