<?php

namespace App\Http\Controllers;

use App\Models\Banner;
use App\Models\BannerImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class BannerController extends Controller
{
    public function index()
    {
        $banners = Banner::with('images')->get();
        return response()->json(['status' => 1, 'banners' => $banners]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'title' => 'required|string|max:100',
            'status' => 'nullable|in:active,inactive',
            'images.*' => 'required|image|mimes:jpeg,png,jpg|max:2048'
        ]);

        try {
            DB::beginTransaction();

            $banner = Banner::create([
                'title' => $request->title,
                'status' => $request->status ?? 'active'
            ]);

            $images = [];
            if ($request->hasFile('images')) {
                $uploadPath = 'uploads/banners';
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0777, true);
                }

                foreach ($request->file('images') as $index => $image) {
                    $fileName = time() . '_' . $index . '_' . $image->getClientOriginalName();
                    $image->move($uploadPath, $fileName);
                    $imagePath = $uploadPath . '/' . $fileName;

                    BannerImage::create([
                        'banner_id' => $banner->id,
                        'image_url' => $imagePath,
                        'image_order' => $index
                    ]);

                    $images[] = $imagePath;
                }
            }

            DB::commit();

            return response()->json([
                'status' => 1,
                'message' => 'Banner added successfully',
                'images' => $images
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'status' => 0,
                'message' => 'Failed to add banner',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'title' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive'
        ]);

        $banner = Banner::findOrFail($id);

        $banner->update($request->only(['title', 'status']));

        return response()->json([
            'status' => 1,
            'message' => 'Banner updated successfully'
        ]);
    }

    public function destroy($id)
    {
        $banner = Banner::with('images')->findOrFail($id);

        // Delete image files
        foreach ($banner->images as $image) {
            if (file_exists($image->image_url)) {
                unlink($image->image_url);
            }
        }

        $banner->delete();

        return response()->json([
            'status' => 1,
            'message' => 'Banner deleted successfully'
        ]);
    }
}
