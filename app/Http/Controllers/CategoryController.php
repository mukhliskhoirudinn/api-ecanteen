<?php

namespace App\Http\Controllers;

use App\Http\Middleware\OwnerMiddleware;
use App\Models\Categories;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Resources\ResponseResource;
use PhpParser\Node\Stmt\TryCatch;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class CategoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('owner', except: ['index']),
        ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $categories = Categories::when(request()->search, function ($query) {
            $query->where('name', 'like', '%' . request()->search . '%');
        })->latest()->paginate(10);

        $categories->appends(['search' => request()->search]);

        if ($categories->isEmpty()) {
            return new ResponseResource(
                true,
                'Categories not available',
                null,
                ['code' => 200],
                200
            );
        }

        return new ResponseResource(
            true,
            'List of Categories',
            $categories,
            [
                'code' => 200,
                'total_categories' => $categories->count()
            ]
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:255|unique:categories,name',
        ]);

        try {
            $data['slug'] = Str::slug($data['name']);

            $categories = Categories::create($data);

            $categoriesResponse = [
                'uuid' => $categories->uuid,
                'name' => $categories->name,
                'slug' => $categories->slug,
            ];

            return new ResponseResource(
                true,
                'Category Created',
                $categoriesResponse,
                ['code' => 201],
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
    public function show(string $uuid)
    {
        $categories = Categories::where('uuid', $uuid)->first();

        if (!$categories) {
            return new ResponseResource(
                true,
                'Categories not found with uuid: ' . $uuid . ' ',
                null,
                ['code' => 404],
                404
            );
        }

        $categoriesResponse = [

            'uuid' => $categories->uuid,
            'name' => $categories->name,
            'slug' => $categories->slug,
        ];

        return new ResponseResource(
            true,
            'Categories with uuid: ' . $uuid . ' ',
            $categoriesResponse,
            [
                'code' => 200,
                'updated_at' => $categories->updated_at,
            ],
            200
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $uuid)
    {
        $data = $request->validate([
            'name' => 'required|string|min:3|max:255|unique:categories,name, ' . $uuid . ',uuid',
        ]);



        try {

            $categories = Categories::where('uuid', $uuid)->first();

            if (!$categories) {
                return new ResponseResource(
                    false,
                    'Categories not found with uuid: ' . $uuid . ' ',
                    null,
                    ['code' => 404],
                    404
                );
            }

            $data['slug'] = Str::slug($data['name']);

            $categories->update($data);

            $categoriesResponse = [
                'uuid' => $categories->uuid,
                'name' => $categories->name,
                'slug' => $categories->slug,
            ];

            return new ResponseResource(
                true,
                'Category Updated',
                $categoriesResponse,
                ['code' => 200],
                200
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
     * Remove the specified resource from storage.
     */
    public function destroy(string $uuid)
    {
        try {

            $categories = Categories::where('uuid', $uuid)->first();

            if (!$categories) {
                return new ResponseResource(
                    false,
                    'Categories not found with uuid: ' . $uuid . ' ',
                    null,
                    ['code' => 404],
                    404
                );
            }

            $categories->delete();

            return new ResponseResource(
                true,
                'Category Deleted',
                null,
                ['code' => 200],
                200
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
}
