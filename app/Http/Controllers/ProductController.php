<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::with(['images', 'variants'])->get();
        return response()->json(['status' => 1, 'products' => $products]);
    }

    public function show($id)
    {
        $product = Product::with(['images', 'variants'])->find($id);

        if (!$product) {
            return response()->json(['status' => 0, 'message' => 'Product not found'], 404);
        }

        return response()->json(['status' => 1, 'product' => $product]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string',
            'price' => 'required|numeric|min:0',
            'main_stock' => 'required|integer|min:0',
            'weight' => 'required|integer|min:1',
            'variants' => 'nullable|json',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product = new Product($request->all());
        $product->status = $request->main_stock > 0 ? 'available' : 'unavailable';
        $product->has_variants = !empty($request->variants);
        $product->save();

        // Handle variants
        if ($product->has_variants) {
            $variants = json_decode($request->variants, true);
            $totalVariantStock = 0;

            foreach ($variants as $variant) {
                $totalVariantStock += intval($variant['stock'] ?? 0);
                ProductVariant::create([
                    'product_id' => $product->id,
                    'variant_name' => $variant['name'],
                    'price' => $variant['price'],
                    'stock' => $variant['stock'] ?? 0,
                    'discount' => $variant['discount'] ?? 0
                ]);
            }

            if ($totalVariantStock > $product->main_stock) {
                return response()->json([
                    'status' => 0,
                    'message' => 'Total variant stock cannot exceed main stock'
                ], 400);
            }
        }

        // Handle images
        if ($request->hasFile('images')) {
            $uploadPath = getcwd() . '/uploads/products';
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $order = 0;
            foreach ($request->file('images') as $image) {
                $fileName = time() . '_' . $order . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                $image->move($uploadPath, $fileName);
                $imagePath = '/uploads/products/' . $fileName;

                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $imagePath,
                    'image_order' => $order++
                ]);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => 'Product added successfully',
            'product' => $product->load(['images', 'variants'])
        ], 201);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $this->validate($request, [
            'title' => 'nullable|string',
            'price' => 'nullable|numeric|min:0',
            'main_stock' => 'nullable|integer|min:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        $product->fill($request->all());

        if ($request->has('main_stock')) {
            $product->status = $request->main_stock > 0 ? 'available' : 'unavailable';
        }

        $product->save();

        // Handle new images
        if ($request->hasFile('images')) {
            $currentMaxOrder = $product->images()->max('image_order') ?? -1;
            $order = $currentMaxOrder + 1;

            foreach ($request->file('images') as $image) {
                $path = $image->store('uploads/products');
                ProductImage::create([
                    'product_id' => $product->id,
                    'image_url' => $path,
                    'image_order' => $order++
                ]);
            }
        }

        return response()->json([
            'status' => 1,
            'message' => 'Product updated successfully',
            'product' => $product->load(['images', 'variants'])
        ]);
    }

    public function destroy($id)
    {
        $product = Product::with('images')->findOrFail($id);

        // Delete image files
        foreach ($product->images as $image) {
            $filePath = getcwd() . $image->image_url;
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $product->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Product deleted successfully'
        ]);
    }

    public function updateViewCount(Request $request)
    {
        $this->validate($request, [
            'product_id' => 'required|exists:products_elsid,id'
        ]);

        $product = Product::findOrFail($request->product_id);
        $product->increment('view_count');

        return response()->json([
            'status' => 1,
            'message' => 'View count updated successfully'
        ]);
    }

    public function getVariants($productId)
    {
        $variants = ProductVariant::where('product_id', $productId)
            ->get()
            ->map(function ($variant) {
                $variant->final_price = $variant->price * (1 - $variant->discount / 100);
                return $variant;
            });

        return response()->json([
            'status' => 1,
            'variants' => $variants
        ]);
    }
}
