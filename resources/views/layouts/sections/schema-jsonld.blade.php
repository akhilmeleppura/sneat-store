@php
use Illuminate\Support\Str;

if (isset($product) && $product instanceof \Modules\Catalog\Models\Product) {
    $schema = [
        '@context' => 'https://schema.org/',
        '@type' => 'Product',
        'name' => $product->name,
        'image' => [
            $product->thumbnail_url ?: asset('assets/img/ecommerce-images/product-1.png')
        ],
        'description' => Str::limit(strip_tags($product->short_description ?: $product->description), 200),
        'sku' => $product->sku ?: 'SKU-' . $product->id,
        'brand' => [
            '@type' => 'Brand',
            'name' => $product->brand?->name ?? 'Official',
        ],
        'offers' => [
            '@type' => 'Offer',
            'url' => route('storefront.product.show', $product->slug),
            'priceCurrency' => 'USD',
            'price' => number_format($product->price, 2, '.', ''),
            'availability' => $product->is_in_stock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        ],
    ];

    if ($product->relationLoaded('reviews') ? $product->reviews->count() > 0 : $product->reviews()->count() > 0) {
        $schema['aggregateRating'] = [
            '@type' => 'AggregateRating',
            'ratingValue' => round($product->reviews()->avg('rating') ?: 5, 1),
            'reviewCount' => $product->reviews()->count(),
        ];
    }
} else {
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'Organization',
        'name' => 'AK-Mart Enterprise Store',
        'url' => url('/'),
        'logo' => asset('assets/img/favicon/favicon.ico'),
    ];
}
@endphp
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
