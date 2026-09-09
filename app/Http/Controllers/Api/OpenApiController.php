<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OpenApiController extends Controller
{
    /**
     * Return the OpenAPI 3.0.3 Specification in JSON format.
     */
    public function spec(Request $request): JsonResponse
    {
        $baseUrl = url('/api/v1');

        $spec = [
            'openapi' => '3.0.3',
            'info' => [
                'title' => 'AK-Mart Omnichannel Headless REST API',
                'version' => '1.0.0',
                'description' => 'Production-grade headless e-commerce API powering mobile apps (iOS, Android, Flutter) and headless web frontends (Next.js, Nuxt).',
                'contact' => [
                    'name' => 'AK-Mart Enterprise Engineering',
                    'email' => 'support@akmart.com',
                ],
            ],
            'servers' => [
                [
                    'url' => $baseUrl,
                    'description' => 'Current Environment API Base',
                ],
            ],
            'paths' => [
                '/store/info' => [
                    'get' => [
                        'summary' => 'Get Store Information & Supported Currencies',
                        'tags' => ['Store'],
                        'responses' => [
                            '200' => [
                                'description' => 'Store details, active currency, and store metadata',
                            ],
                        ],
                    ],
                ],
                '/store/products' => [
                    'get' => [
                        'summary' => 'List and Filter Products',
                        'tags' => ['Catalog'],
                        'parameters' => [
                            ['name' => 'search', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                            ['name' => 'category_id', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer']],
                            ['name' => 'brand_id', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer']],
                            ['name' => 'sort', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string', 'enum' => ['newest', 'price_asc', 'price_desc']]],
                            ['name' => 'per_page', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer', 'default' => 12]],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Paginated list of catalog products',
                            ],
                        ],
                    ],
                ],
                '/store/products/{slug}' => [
                    'get' => [
                        'summary' => 'Get Product Details by Slug',
                        'tags' => ['Catalog'],
                        'parameters' => [
                            ['name' => 'slug', 'in' => 'path', 'required' => true, 'schema' => ['type' => 'string']],
                        ],
                        'responses' => [
                            '200' => [
                                'description' => 'Full product details with images, variants, and recommendations',
                            ],
                            '404' => [
                                'description' => 'Product not found',
                            ],
                        ],
                    ],
                ],
                '/store/categories' => [
                    'get' => [
                        'summary' => 'List Categories',
                        'tags' => ['Catalog'],
                        'responses' => [
                            '200' => ['description' => 'Hierarchy of active categories'],
                        ],
                    ],
                ],
                '/store/brands' => [
                    'get' => [
                        'summary' => 'List Featured Brands',
                        'tags' => ['Catalog'],
                        'responses' => [
                            '200' => ['description' => 'List of active brands'],
                        ],
                    ],
                ],
                '/store/search' => [
                    'get' => [
                        'summary' => 'Full-Text Multi-Field Search',
                        'tags' => ['Catalog'],
                        'parameters' => [
                            ['name' => 'q', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string']],
                            ['name' => 'category_id', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'integer']],
                            ['name' => 'sort', 'in' => 'query', 'required' => false, 'schema' => ['type' => 'string']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Matched products and categories'],
                        ],
                    ],
                ],
                '/store/search/autocomplete' => [
                    'get' => [
                        'summary' => 'Instant Autocomplete Suggestions',
                        'tags' => ['Catalog'],
                        'parameters' => [
                            ['name' => 'q', 'in' => 'query', 'required' => true, 'schema' => ['type' => 'string']],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Instant search suggestions and popular keywords'],
                        ],
                    ],
                ],
                '/store/cart' => [
                    'get' => [
                        'summary' => 'Get Current Session Shopping Cart',
                        'tags' => ['Cart'],
                        'responses' => [
                            '200' => ['description' => 'Current cart line items, subtotal, and tax summary'],
                        ],
                    ],
                ],
                '/store/cart/add' => [
                    'post' => [
                        'summary' => 'Add an Item to the Cart',
                        'tags' => ['Cart'],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['product_id', 'quantity'],
                                        'properties' => [
                                            'product_id' => ['type' => 'integer'],
                                            'quantity' => ['type' => 'integer', 'minimum' => 1],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Updated cart state'],
                        ],
                    ],
                ],
                '/store/orders/track' => [
                    'post' => [
                        'summary' => 'Track Order by Order Number and Email/Phone',
                        'tags' => ['Orders'],
                        'requestBody' => [
                            'required' => true,
                            'content' => [
                                'application/json' => [
                                    'schema' => [
                                        'type' => 'object',
                                        'required' => ['order_number'],
                                        'properties' => [
                                            'order_number' => ['type' => 'string'],
                                            'contact' => ['type' => 'string'],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                        'responses' => [
                            '200' => ['description' => 'Order status, delivery timeline, and items'],
                            '404' => ['description' => 'Order not found'],
                        ],
                    ],
                ],
            ],
            'components' => [
                'schemas' => [
                    'ApiResponse' => [
                        'type' => 'object',
                        'properties' => [
                            'success' => ['type' => 'boolean'],
                            'message' => ['type' => 'string'],
                            'data' => ['type' => 'object'],
                        ],
                    ],
                ],
            ],
        ];

        return response()->json($spec, 200, [
            'Access-Control-Allow-Origin' => '*',
            'Content-Type' => 'application/json; charset=UTF-8',
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }
}
