<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProductRequest;
use App\Models\Product;
use Illuminate\Http\Request;
use App\Http\Services\ProductService;
use App\Http\Resources\ResponseResource;
use App\Http\Services\FileService;

class ProductController extends Controller
{
    public function __construct(private ProductService $productService, private FileService $fileService) {}
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // select all, select paginate
        $paginate = request()->paginate ? true : false;

        $products = $this->productService->getProduct($paginate);

        if ($products->isEmpty()) {
            return new ResponseResource(
                true,
                'Products not available',
                null,
                ['code' => 200],
                200
            );
        }

        $productResponse = $products->map(function ($product) {
            $product->price = 'Rp. ' . number_format($product->price, 0, ',', '.');
            // $product->makeHidden(['category_id', 'updated_id']);

            return $product;
        });

        return new ResponseResource(
            true,
            'List of Products',
            $productResponse,
            [
                'code' => 200,
                'total_products' => $products->count()
            ],
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(ProductRequest $request)
    {
        $data = $request->validated();

        try {
            $uploadImage = $this->fileService->upload($data['image'], 'images');
            $data['image'] = $uploadImage;
            $product = $this->productService->create($data);

            $productResponse = [
                'uuid' => $product->uuid,
                'category_id' => $product->category_id,
                'supplier_id' => $product->supplier_id,
                'name' => $product->name,
                'image' => $product->image,
                'price' => $product->price,
                'quantity' => $product->quantity,
                'description' => $product->description,
            ];

            $productMeta = $this->productService->getByFirst('uuid', $product->uuid, true);

            return new ResponseResource(
                true,
                'Product created successfully',
                $productResponse,
                [
                    'code' => 201,
                    'category_name' => $productMeta->category->name,
                    'supplier_name' => $productMeta->supplier->name,
                    'image_url' => url(asset('storage/' . $productMeta->image))
                ],
                201
            );
        } catch (\Exception $e) {
            return new ResponseResource(
                false,
                $e->getMessage(),
                null,
                ['code' => 500],
                500
            );
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        //
    }
}
