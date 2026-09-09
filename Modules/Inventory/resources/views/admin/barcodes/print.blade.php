<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Warehouse SKU Labels — {{ $product->name }}</title>
  <style>
    body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 20px; background: #fff; color: #111; }
    .label-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    .label-card { border: 2px dashed #bbb; padding: 14px; text-align: center; border-radius: 6px; background: #fafafa; }
    .product-title { font-weight: bold; font-size: 13px; margin-bottom: 4px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .sku-code { font-family: monospace; font-size: 14px; font-weight: bold; letter-spacing: 1px; color: #2e3a59; }
    .price-tag { font-size: 14px; font-weight: bold; color: #696cff; margin: 4px 0; }
    .barcode-placeholder { height: 42px; background: repeating-linear-gradient(to right, #000 0, #000 2px, transparent 2px, transparent 4px, #000 4px, #000 7px, transparent 7px, transparent 9px); margin: 8px auto; width: 85%; }
    .print-actions { margin-bottom: 20px; }
    @media print {
      .print-actions { display: none; }
      body { margin: 0; }
      .label-card { border: 1px solid #999; }
    }
  </style>
</head>
<body>
  <div class="print-actions">
    <button onclick="window.print()" style="padding: 8px 18px; font-size: 14px; font-weight: bold; background: #696cff; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
      🖨️ Print Warehouse Labels
    </button>
  </div>

  <div class="label-grid">
    @for($i = 0; $i < 6; $i++)
      <div class="label-card">
        <div class="product-title">{{ $product->name }}</div>
        <div class="price-tag">{{ money($product->price) }}</div>
        <div class="barcode-placeholder"></div>
        <div class="sku-code">{{ $product->sku ?: 'SKU-'.$product->id }}</div>
        <small style="font-size: 10px; color: #777;">SNEAT FULFILLMENT CENTER</small>
      </div>
    @endfor
  </div>
</body>
</html>
