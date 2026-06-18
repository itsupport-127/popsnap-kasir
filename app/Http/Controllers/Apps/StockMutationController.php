<?php

namespace App\Http\Controllers\Apps;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMutation;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StockMutationController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = [
            'product_id' => $request->input('product_id'),
            'mutation_type' => $request->input('mutation_type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $stockMutations = StockMutation::query()
            ->with(['product:id,title,barcode,sku', 'creator:id,name'])
            ->when($filters['product_id'], fn ($query, $productId) => $query->where('product_id', $productId))
            ->when($filters['mutation_type'], fn ($query, $mutationType) => $query->where('mutation_type', $mutationType))
            ->when($filters['date_from'], fn ($query, $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['date_to'], fn ($query, $date) => $query->whereDate('created_at', '<=', $date))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Dashboard/StockMutations/Index', [
            'stockMutations' => $stockMutations,
            'products' => Product::query()->orderBy('title')->get(['id', 'title', 'barcode', 'sku']),
            'filters' => $filters,
        ]);
    }
}
