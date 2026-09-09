<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;
use Modules\Catalog\Models\Category;
use Modules\Catalog\Models\Product;
use Modules\Context\Models\Branch;

class SeoController extends Controller
{
    /**
     * Generate dynamic sitemap.xml for search engines.
     */
    public function sitemap(): Response
    {
        $products = Product::where('status', 'published')
            ->select('slug', 'updated_at')
            ->get();

        $categories = Category::active()
            ->select('slug', 'updated_at')
            ->get();

        $branches = Branch::where('status', 'active')
            ->select('id', 'updated_at')
            ->get();

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

        // Static core routes
        $staticRoutes = [
            route('storefront.home') => '1.0',
            route('storefront.catalog') => '0.9',
            route('store.locations') => '0.8',
            route('order.track.page') => '0.7',
        ];

        foreach ($staticRoutes as $url => $priority) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars($url) . '</loc>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>' . $priority . '</priority>';
            $xml .= '</url>';
        }

        // Categories
        foreach ($categories as $cat) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars(route('storefront.catalog', ['category' => $cat->slug])) . '</loc>';
            $xml .= '<lastmod>' . ($cat->updated_at ? $cat->updated_at->toAtomString() : date('Y-m-d')) . '</lastmod>';
            $xml .= '<changefreq>weekly</changefreq>';
            $xml .= '<priority>0.8</priority>';
            $xml .= '</url>';
        }

        // Products
        foreach ($products as $prod) {
            $xml .= '<url>';
            $xml .= '<loc>' . htmlspecialchars(route('storefront.product.show', $prod->slug)) . '</loc>';
            $xml .= '<lastmod>' . ($prod->updated_at ? $prod->updated_at->toAtomString() : date('Y-m-d')) . '</lastmod>';
            $xml .= '<changefreq>daily</changefreq>';
            $xml .= '<priority>0.9</priority>';
            $xml .= '</url>';
        }

        $xml .= '</urlset>';

        return response($xml, 200, [
            'Content-Type' => 'application/xml',
        ]);
    }

    /**
     * Dynamic robots.txt
     */
    public function robots(): Response
    {
        $content = "User-agent: *\n";
        $content .= "Disallow: /admin/\n";
        $content .= "Disallow: /account/\n";
        $content .= "Disallow: /checkout\n";
        $content .= "Allow: /\n\n";
        $content .= "Sitemap: " . url('/sitemap.xml') . "\n";

        return response($content, 200, [
            'Content-Type' => 'text/plain',
        ]);
    }
}
