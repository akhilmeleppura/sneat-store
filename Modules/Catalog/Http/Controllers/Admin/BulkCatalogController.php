<?php

namespace Modules\Catalog\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Product;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BulkCatalogController extends Controller
{
    /**
     * Export all products to CSV.
     */
    public function exportCsv(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv',
            'Content-Disposition' => 'attachment; filename="products_export_' . date('Ymd_His') . '.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['ID', 'Name', 'SKU', 'Price', 'Category', 'Brand', 'Status', 'In Stock']);

            Product::with(['category', 'brand'])
                ->chunk(100, function ($products) use ($handle) {
                    foreach ($products as $p) {
                        fputcsv($handle, [
                            $p->id,
                            $p->name,
                            $p->sku,
                            $p->price,
                            $p->category?->name,
                            $p->brand?->name,
                            $p->status,
                            $p->is_in_stock ? 'Yes' : 'No',
                        ]);
                    }
                });

            fclose($handle);
        }, 200, $headers);
    }
}
