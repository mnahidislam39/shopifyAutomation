<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyMenuController extends Controller
{
    public function createMenu(Request $request)
    {
        $shopDomain = $request->input('shop_domain');
        $accessToken = $request->input('access_token');
        $menuTitle = $request->input('title', 'Main Navigation');
        $menuItems = $request->input('items', []);

        if (!$shopDomain || !$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Shop domain and Access token are required.'
            ], 400);
        }

        $menuHandle = Str::slug($menuTitle);

        $query = '
            mutation menuCreate($title: String!, $handle: String!, $items: [MenuItemCreateInput!]!) {
                menuCreate(title: $title, handle: $handle, items: $items) {
                    menu {
                        id
                        title
                        handle
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        $variables = [
            'title' => $menuTitle,
            'handle' => $menuHandle,
            'items' => $this->formatMenuItemsForGraphQL($menuItems, $shopDomain, $accessToken)
        ];

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ])->post("https://{$shopDomain}/admin/api/2026-01/graphql.json", [
                'query' => $query,
                'variables' => $variables,
            ]);

            $responseData = $response->json();

            if (isset($responseData['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'GraphQL API Error: ' . json_encode($responseData['errors'])
                ], 422);
            }

            if (!empty($responseData['data']['menuCreate']['userErrors'])) {
                $userErrors = $responseData['data']['menuCreate']['userErrors'];
                $errorMsg = implode(', ', array_column($userErrors, 'message'));

                return response()->json([
                    'success' => false,
                    'message' => 'Shopify Error: ' . $errorMsg
                ], 422);
            }

            return response()->json([
                'success' => true,
                'message' => 'Menu and nested sub-menus created successfully!',
                'data' => $responseData['data']['menuCreate']['menu'] ?? null
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server Error: ' . $e->getMessage()
            ], 500);
        }
    }

    // Recursive function to handle nested sub-menus
    private function formatMenuItemsForGraphQL(array $items, string $shopDomain, string $accessToken): array
    {
        $formatted = [];

        foreach ($items as $item) {
            $url = (string) ($item['url'] ?? '/');
            $type = $this->detectMenuItemType($url);

            $node = [
                'title' => (string) ($item['title'] ?? 'Menu Item'),
                'type' => $type,
                'url' => $this->normalizeUrl($url, $shopDomain)
            ];

            // Fetch Resource ID for native icons
            $resourceId = $this->fetchResourceId($url, $type, $shopDomain, $accessToken);
            if ($resourceId) {
                $node['resourceId'] = $resourceId;
            }

            // Recursive check for sub-menus inside sub-menus
            if (!empty($item['items']) && is_array($item['items'])) {
                $node['items'] = $this->formatMenuItemsForGraphQL($item['items'], $shopDomain, $accessToken);
            }

            $formatted[] = $node;
        }

        return $formatted;
    }

    private function detectMenuItemType(string $url): string
    {
        if ($url === '/' || $url === '') {
            return 'FRONTPAGE';
        }
        if ($url === '/collections/all' || $url === '/collections') {
            return 'CATALOG';
        }
        if (Str::contains($url, '/collections/')) {
            return 'COLLECTION';
        }
        if (Str::contains($url, '/pages/')) {
            return 'PAGE';
        }
        if (Str::contains($url, '/products/')) {
            return 'PRODUCT';
        }
        if (Str::contains($url, '/blogs/')) {
            return 'BLOG';
        }

        return 'HTTP';
    }

    private function normalizeUrl(string $url, string $shopDomain): string
    {
        if (Str::startsWith($url, 'http://') || Str::startsWith($url, 'https://')) {
            return $url;
        }
        return "https://{$shopDomain}" . (Str::startsWith($url, '/') ? '' : '/') . $url;
    }

    private function fetchResourceId(string $url, string $type, string $shopDomain, string $accessToken): ?string
    {
        if ($type === 'COLLECTION') {
            $handle = Str::after($url, '/collections/');
            $handle = explode('?', $handle)[0];

            $res = Http::withHeaders(['X-Shopify-Access-Token' => $accessToken])
                ->get("https://{$shopDomain}/admin/api/2026-01/custom_collections.json?handle={$handle}");

            $collections = $res->json()['custom_collections'] ?? [];
            if (empty($collections)) {
                $res = Http::withHeaders(['X-Shopify-Access-Token' => $accessToken])
                    ->get("https://{$shopDomain}/admin/api/2026-01/smart_collections.json?handle={$handle}");
                $collections = $res->json()['smart_collections'] ?? [];
            }

            if (!empty($collections[0]['id'])) {
                return "gid://shopify/Collection/" . $collections[0]['id'];
            }
        }

        if ($type === 'PAGE') {
            $handle = Str::after($url, '/pages/');
            $handle = explode('?', $handle)[0];

            $res = Http::withHeaders(['X-Shopify-Access-Token' => $accessToken])
                ->get("https://{$shopDomain}/admin/api/2026-01/pages.json?handle={$handle}");

            $pages = $res->json()['pages'] ?? [];
            if (!empty($pages[0]['id'])) {
                return "gid://shopify/Page/" . $pages[0]['id'];
            }
        }

        return null;
    }
}
