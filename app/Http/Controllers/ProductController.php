<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

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
        try {
            DB::beginTransaction();

            // Validate basic product data
            $this->validate($request, [
                'title' => 'required|string',
                'price' => 'required|numeric|min:0',
                'main_stock' => 'required|integer|min:0',
                'weight' => 'required|integer|min:1'
            ]);

            // Create product
            $product = new Product();
            $product->title = $request->title;
            $product->description = $request->description;
            $product->category = $request->category;
            $product->price = $request->price;
            $product->discount = $request->discount ?? 0;
            $product->main_stock = $request->main_stock;
            $product->weight = $request->weight;
            $product->status = $request->main_stock > 0 ? 'available' : 'unavailable';

            // Check for variants
            $variantsData = json_decode($request->variants, true);
            $product->has_variants = !empty($variantsData);

            if (!$product->save()) {
                throw new \Exception('Failed to save product');
            }

            // Handle variants if they exist
            if (!empty($variantsData) && is_array($variantsData)) {
                foreach ($variantsData as $variant) {
                    if (!empty($variant['name'])) {
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

            // Handle images if they exist
            if ($request->hasFile('images')) {
                $uploadPath = public_path('uploads/products');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                foreach ($request->file('images') as $index => $image) {
                    $fileName = time() . '_' . $index . '_' . str_replace(' ', '_', $image->getClientOriginalName());
                    $image->move($uploadPath, $fileName);

                    ProductImage::create([
                        'product_id' => $product->id,
                        'image_url' => 'uploads/products/' . $fileName,
                        'image_order' => $index
                    ]);
                }
            }

            DB::commit();

            // Return fresh product with relations
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
        try {
            // Log semua request data
            Log::info('Update request received', [
                'request_data' => $request->all(),
                'files' => $request->hasFile('images') ? 'Has Images' : 'No Images',
                'variants' => $request->variants
            ]);

            DB::beginTransaction();

            $product = Product::findOrFail($id);
            Log::info('Found product', ['product' => $product]);

            // Update basic info
            $product->fill($request->only([
                'title',
                'description',
                'category',
                'price',
                'discount',
                'main_stock',
                'weight'
            ]));
            $product->status = $request->main_stock > 0 ? 'available' : 'unavailable';

            // Process variants
            $variantsData = $request->variants ? json_decode($request->variants, true) : [];
            Log::info('Processing variants', ['variants_data' => $variantsData]);

            $product->has_variants = !empty($variantsData);
            $product->save();

            // Handle variants
            if (!empty($variantsData) && is_array($variantsData)) {
                // Delete existing variants
                ProductVariant::where('product_id', $product->id)->delete();

                foreach ($variantsData as $variant) {
                    if (!empty($variant['name'])) {
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

            // Handle images
            if ($request->has('deleted_images')) {
                $deletedImages = json_decode($request->deleted_images, true);
                foreach ($deletedImages as $imageId) {
                    $image = ProductImage::find($imageId);
                    if ($image) {
                        // Hapus file
                        File::delete(public_path($image->image_url));
                        $image->delete();
                    }
                }
            }

            DB::commit();

            $updatedProduct = Product::with(['images', 'variants'])->find($product->id);
            Log::info('Update successful', ['updated_product' => $updatedProduct]);

            return response()->json([
                'status' => 1,
                'message' => 'Product updated successfully',
                'product' => $updatedProduct
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Update failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'status' => 0,
                'message' => 'Failed to update product: ' . $e->getMessage()
            ], 500);
        }
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
