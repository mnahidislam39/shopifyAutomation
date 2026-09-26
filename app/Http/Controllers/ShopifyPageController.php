<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyPageController extends Controller
{
    public function createPages(Request $request)
    {
        $shopDomain  = $request->input('shop_domain');
        $accessToken = $request->input('access_token');
        $pages       = $request->input('pages', []);

        if (! $shopDomain || ! $accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Shop domain and Access token are required.',
            ], 400);
        }

        $results = [];
        $query = '
            mutation pageCreate($page: PageCreateInput!) {
                pageCreate(page: $page) {
                    page {
                        id
                        title
                        handle
                        templateSuffix
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
        ';

        foreach ($pages as $pageData) {
            $title       = $pageData['title'] ?? 'Untitled Page';
            $bodyHtml    = $pageData['body_html'] ?? '';
            $isPublished = $pageData['is_published'] ?? false;
            $handle      = $pageData['handle'] ?? Str::slug($title);

            // টেমপ্লেট সাফিক্স চেক (না থাকলে শপিফাই ডিফল্ট ধরবে)
            $templateSuffix = $pageData['template_suffix'] ?? null;

            $pageInput = [
                'title'       => $title,
                'body'        => $bodyHtml,
                'isPublished' => $isPublished,
                'handle'      => $handle,
            ];

            // যদি টেমপ্লেট নাম দেওয়া থাকে তবেই রিকোয়েস্টে যোগ হবে
            if (!empty($templateSuffix)) {
                $pageInput['templateSuffix'] = $templateSuffix;
            }

            $variables = [
                'page' => $pageInput
            ];

            try {
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type'           => 'application/json',
                    'Accept'                 => 'application/json',
                ])->post("https://{$shopDomain}/admin/api/2026-01/graphql.json", [
                    'query'     => $query,
                    'variables' => $variables,
                ]);

                $responseData = $response->json();

                if (isset($responseData['errors'])) {
                    $results[] = [
                        'title'   => $title,
                        'success' => false,
                        'message' => json_encode($responseData['errors'])
                    ];
                    continue;
                }

                $userErrors = $responseData['data']['pageCreate']['userErrors'] ?? [];
                if (!empty($userErrors)) {
                    $results[] = [
                        'title'   => $title,
                        'success' => false,
                        'message' => implode(', ', array_column($userErrors, 'message'))
                    ];
                    continue;
                }

                $createdPage = $responseData['data']['pageCreate']['page'] ?? null;
                $results[] = [
                    'title'   => $title,
                    'success' => true,
                    'data'    => $createdPage
                ];

            } catch (\Exception $e) {
                $results[] = [
                    'title'   => $title,
                    'success' => false,
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'results' => $results,
        ]);
    }
}
