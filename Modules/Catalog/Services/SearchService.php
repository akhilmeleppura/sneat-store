<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;

class SearchService
{
    /**
     * Fast prefix & keyword autocomplete for search bar dropdown.
     */
    public function autocomplete(string $query, int $limit = 6): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) {
            return [
                'query'      => $query,
                'products'   => [],
                'categories' => [],
                'total'      => 0,
            ];
        }

        $products = Product::query()
            ->where('status', 'published')
            ->where(function (Builder $b) use ($query) {
                $b->where('name', 'LIKE', "%{$query}%")
                  ->orWhere('sku', 'LIKE', "%{$query}%")
                  ->orWhere('short_description', 'LIKE', "%{$query}%");
            })
            ->with(['category', 'brand'])
            ->limit($limit)
            ->get()
            ->map(function ($p) {
                return [
                    'id'            => $p->id,
                    'name'          => $p->name,
                    'slug'          => $p->slug,
                    'sku'           => $p->sku,
                    'price'         => (float) $p->price,
                    'price_display' => money($p->price),
                    'category'      => $p->category?->name,
                    'brand'         => $p->brand?->name,
                    'image'         => $p->thumbnail_url ?: asset('assets/img/ecommerce-images/product-1.png'),
                    'url'           => route('storefront.product.show', $p->slug),
                ];
            });

        $categories = Category::query()
            ->active()
            ->where('name', 'LIKE', "%{$query}%")
            ->limit(4)
            ->get(['id', 'name', 'slug'])
            ->map(function ($c) {
                return [
                    'id'   => $c->id,
                    'name' => $c->name,
                    'url'  => route('storefront.catalog', ['category' => $c->slug]),
                ];
            });

        return [
            'query'      => $query,
            'products'   => $products,
            'categories' => $categories,
            'total'      => $products->count(),
        ];
    }

    /**
     * Faceted search with filters, sorting, and pagination.
     */
    public function search(array $filters = [], int $perPage = 12): LengthAwarePaginator
    {
        $builder = Product::query()->where('status', 'published');

        if (!empty($filters['q'])) {
            $term = trim($filters['q']);
            $builder->where(function (Builder $b) use ($term) {
                $b->where('name', 'LIKE', "%{$term}%")
                  ->orWhere('sku', 'LIKE', "%{$term}%")
                  ->orWhere('description', 'LIKE', "%{$term}%");
            });
        }

        if (!empty($filters['category_id'])) {
            $builder->where('category_id', $filters['category_id']);
        } elseif (!empty($filters['category_slug'])) {
            $builder->whereHas('category', function ($q) use ($filters) {
                $q->where('slug', $filters['category_slug']);
            });
        }

        if (!empty($filters['brand_id'])) {
            $builder->where('brand_id', $filters['brand_id']);
        }

        if (isset($filters['min_price']) && is_numeric($filters['min_price'])) {
            $builder->where('price', '>=', (float) $filters['min_price']);
        }

        if (isset($filters['max_price']) && is_numeric($filters['max_price'])) {
            $builder->where('price', '<=', (float) $filters['max_price']);
        }

        if (!empty($filters['in_stock'])) {
            $builder->where('is_in_stock', true);
        }

        // Sorting
        $sort = $filters['sort'] ?? 'latest';
        match ($sort) {
            'price_low_high' => $builder->orderBy('price', 'asc'),
            'price_high_low' => $builder->orderBy('price', 'desc'),
            'name_asc'       => $builder->orderBy('name', 'asc'),
            'name_desc'      => $builder->orderBy('name', 'desc'),
            default          => $builder->latest('id'),
        };

        return $builder->with(['category', 'brand'])->paginate($perPage);
    }
}
