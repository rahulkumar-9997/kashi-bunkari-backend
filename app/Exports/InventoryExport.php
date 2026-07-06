<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
class InventoryExport implements FromCollection, WithHeadings
{
    public function collection()
    {
        return Product::with(['category:id,title', 'inventories'])
            ->get()
            ->flatMap(function ($product) {
                if ($product->inventories->isEmpty()) {
                    return [[
                        'id' => $product->id,
                        'name' => ucwords(strtolower($product->title)),
                        'category' => $product->category->title ?? 'No Category',
                        'mrp' => '',
                        'offer_rate' => '',
                        'purchase_rate' => '',
                        'quantity' => '',
                    ]];
                }
                return $product->inventories->map(function ($inv) use ($product) {
                    return [
                        'id' => $product->id,
                        'name' => ucwords(strtolower($product->title)),
                        'category' => $product->category->title ?? 'No Category',
                        'mrp' => $inv->mrp,
                        'offer_rate' => $inv->offer_rate,
                        'purchase_rate' => $inv->purchase_rate,
                        'quantity' => $inv->stock_quantity,
                    ];
                });
            });
    }

    public function headings(): array
    {
        return [
            'Product ID',
            'Product Name',
            'Category Name',
            'MRP',
            'Offer Rate',
            'Purchase Rate',
            'Quantity'
        ];
    }
}