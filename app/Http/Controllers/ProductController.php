<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
            'variants' => 'nullable|string',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
        ]);

        try {
            DB::beginTransaction();

            // Create product first
            $product = new Product();
            $product->title = $request->title;
            $product->description = $request->description;
            $product->category = $request->category;
            $product->price = $request->price;
            $product->discount = $request->discount ?? 0;
            $product->main_stock = $request->main_stock;
            $product->weight = $request->weight;
            $product->status = $request->main_stock > 0 ? 'available' : 'unavailable';
            $product->has_variants = !empty($request->variants);
            $product->save();            // Handle variants after product is created
            if ($request->has('variants')) {
                $variantsData = is_string($request->variants) ? json_decode($request->variants, true) : $request->variants;

                if (is_array($variantsData)) {
                    foreach ($variantsData as $variant) {
                        if (isset($variant['name'])) {
                            ProductVariant::create([
                                'product_id' => $product->id,
                                'variant_name' => $variant['name'],
                                'price' => floatval($variant['price'] ?? $product->price),
                                'stock' => intval($variant['stock'] ?? 0),
                                'discount' => floatval($variant['discount'] ?? 0)
                            ]);
                        }
                    }
                }
            }

            // Handle images
            if ($request->hasFile('images')) {
                $uploadPath = storage_path('app/public/uploads/products');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                foreach ($request->file('images') as $index => $image) {
                    $fileName = time() . '_' . $index . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->move($uploadPath, $fileName);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => '/storage/uploads/products/' . $fileName,
                        'image_order' => $index
                    ]);
                }
            }

            DB::commit();

            // Fetch fresh product data with relations
            $product = Product::with(['images', 'variants'])->find($product->id);

            return response()->json([
                'status' => 1,
                'message' => 'Product added successfully',
                'product' => $product
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => 'Failed to add product: ' . $e->getMessage()
            ], 500);
        }
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
            $uploadPath = public_path('uploads/products');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $currentMaxOrder = $product->images()->max('image_order') ?? -1;
            $order = $currentMaxOrder + 1;

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
