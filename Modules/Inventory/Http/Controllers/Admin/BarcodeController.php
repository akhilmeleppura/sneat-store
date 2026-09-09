<?php

namespace Modules\Inventory\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Modules\Catalog\Models\Product;

class BarcodeController extends Controller
{
    /**
     * Printable warehouse picker label sheet.
     */
    public function print(int $productId)
    {
        $product = Product::with(['category', 'brand', 'variants'])->findOrFail($productId);

        return view('inventory::admin.barcodes.print', compact('product'));
    }
}
