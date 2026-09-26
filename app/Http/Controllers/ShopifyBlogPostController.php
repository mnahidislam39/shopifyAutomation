<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ShopifyBlogPostController extends Controller
{
    public function createBlogPosts(Request $request)
    {
        $shopDomain  = trim($request->input('shop_domain', ''));
        $shopDomain  = preg_replace('#^https?://|/+$#', '', $shopDomain);

        $accessToken = trim($request->input('access_token', ''));
        $blogTitle   = trim($request->input('blog_title', 'News'));
        $posts       = $request->input('posts', []);

        if (!$shopDomain || !$accessToken) {
            return response()->json([
                'success' => false,
                'message' => 'Shop domain and access token are required.',
            ], 400);
        }

        $graphqlUrl = "https://{$shopDomain}/admin/api/2024-01/graphql.json";

        // ১. ব্লগ ফেচ করা
        $blogsQuery = '
            query {
                blogs(first: 50) {
                    edges {
                        node {
                            id
                            title
                        }
                    }
                }
            }
        ';

        try {
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type'           => 'application/json',
            ])->post($graphqlUrl, ['query' => $blogsQuery]);

            if (!$response->successful()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Shopify HTTP Error: ' . $response->body(),
                ], 400);
            }

            $result = $response->json();

            if (isset($result['errors'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'GraphQL Root Error: ' . json_encode($result['errors']),
                ], 400);
            }

            $edges = $result['data']['blogs']['edges'] ?? [];
            $targetBlogId = null;

            foreach ($edges as $edge) {
                if (strcasecmp(trim($edge['node']['title']), $blogTitle) === 0) {
                    $targetBlogId = $edge['node']['id'];
                    break;
                }
            }

            // ২. ব্লগ না থাকলে তৈরি করা
            if (!$targetBlogId) {
                $createBlogMutation = '
                    mutation blogCreate($blog: BlogCreateInput!) {
                        blogCreate(blog: $blog) {
                            blog {
                                id
                                title
                            }
                            userErrors {
                                field
                                message
                            }
                        }
                    }
                ';

                $createRes = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type'           => 'application/json',
                ])->post($graphqlUrl, [
                    'query'     => $createBlogMutation,
                    'variables' => ['blog' => ['title' => $blogTitle]]
                ]);

                $createData = $createRes->json();
                $blogUserErrors = $createData['data']['blogCreate']['userErrors'] ?? [];

                if (!empty($blogUserErrors)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Blog Creation Failed: ' . implode(', ', array_column($blogUserErrors, 'message')),
                    ], 400);
                }

                $targetBlogId = $createData['data']['blogCreate']['blog']['id'] ?? null;
            }

            if (!$targetBlogId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Target Blog ID could not be resolved.',
                ], 400);
            }

            // ৩. আর্টিকেল তৈরি করার মিউটেশন (Metafields সহ)
            $articleQuery = '
                mutation articleCreate($article: ArticleCreateInput!) {
                    articleCreate(article: $article) {
                        article {
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

            $results = [];

            foreach ($posts as $postData) {
                $title          = $postData['title'] ?? 'Untitled Post';
                $bodyHtml       = $postData['body_html'] ?? '';
                $excerpt        = $postData['excerpt'] ?? '';
                $isPublished    = isset($postData['is_published']) ? (bool)$postData['is_published'] : true;
                $handle         = $postData['handle'] ?? Str::slug($title);
                $tags           = $postData['tags'] ?? [];
                $imageUrl       = $postData['image_url'] ?? null;
                $authorName     = $postData['author'] ?? 'Admin';

                $seoTitle       = $postData['seo_title'] ?? $title;
                $seoDescription = $postData['seo_description'] ?? $excerpt;

                // শপিফাইয়ের নিজস্ব এসইও মেটাফিল্ড স্ট্রাকচার
                $metafields = [];
                if (!empty($seoTitle)) {
                    $metafields[] = [
                        'namespace' => 'seo',
                        'key'       => 'title',
                        'type'      => 'single_line_text_field',
                        'value'     => $seoTitle
                    ];
                }
                if (!empty($seoDescription)) {
                    $metafields[] = [
                        'namespace' => 'seo',
                        'key'       => 'description',
                        'type'      => 'multi_line_text_field',
                        'value'     => $seoDescription
                    ];
                }

                $articleInput = [
                    'blogId'      => $targetBlogId,
                    'title'       => $title,
                    'body'        => $bodyHtml,
                    'summary'     => $excerpt,
                    'isPublished' => $isPublished,
                    'handle'      => $handle,
                    'tags'        => $tags,
                    'author'      => ['name' => $authorName],
                    'metafields'  => $metafields
                ];

                if (!empty($imageUrl)) {
                    $articleInput['image'] = [
                        'url' => $imageUrl,
                        'altText' => $title
                    ];
                }

                $articleRes = Http::withHeaders([
                    'X-Shopify-Access-Token' => $accessToken,
                    'Content-Type'           => 'application/json',
                ])->post($graphqlUrl, [
                    'query'     => $articleQuery,
                    'variables' => ['article' => $articleInput]
                ]);

                $artData = $articleRes->json();
                $userErrors = $artData['data']['articleCreate']['userErrors'] ?? [];

                // ইমেজের কারণে ফেইল করলে ইমেজ বাদ দিয়ে রিট্রাই করা
                if (!empty($userErrors) && !empty($imageUrl)) {
                    unset($articleInput['image']);

                    $retryRes = Http::withHeaders([
                        'X-Shopify-Access-Token' => $accessToken,
                        'Content-Type'           => 'application/json',
                    ])->post($graphqlUrl, [
                        'query'     => $articleQuery,
                        'variables' => ['article' => $articleInput]
                    ]);

                    $retryData = $retryRes->json();
                    $retryErrors = $retryData['data']['articleCreate']['userErrors'] ?? [];

                    if (empty($retryErrors)) {
                        $results[] = [
                            'title'   => $title,
                            'success' => true,
                            'data'    => $retryData['data']['articleCreate']['article'] ?? null
                        ];
                    } else {
                        $results[] = [
                            'title'   => $title,
                            'success' => false,
                            'message' => implode(', ', array_column($retryErrors, 'message'))
                        ];
                    }
                } elseif (!empty($userErrors)) {
                    $results[] = [
                        'title'   => $title,
                        'success' => false,
                        'message' => implode(', ', array_column($userErrors, 'message'))
                    ];
                } else {
                    $createdArticle = $artData['data']['articleCreate']['article'] ?? null;
                    if ($createdArticle) {
                        $results[] = [
                            'title'   => $title,
                            'success' => true,
                            'data'    => $createdArticle
                        ];
                    } else {
                        $results[] = [
                            'title'   => $title,
                            'success' => false,
                            'message' => 'Shopify Response Raw: ' . json_encode($artData)
                        ];
                    }
                }
            }

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }
}
