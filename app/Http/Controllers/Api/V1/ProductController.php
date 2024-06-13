<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $index=\Illuminate\Support\Facades\Cache::remember('Product', 84000, function () {
return \App\Models\Product::all();
});

        return response()->json($index,200);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Cache::forget('Product');
$validData=$request->validate(['name' => 'required',
]);
\App\Models\Product::create($validData);


        return response()->json(['message'=>'Created Successfully'],200);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $show=\App\Models\Product::findOrFail($id);


        return response()->json($show,200);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        \Illuminate\Support\Facades\Cache::forget('Product');
$validData=$request->validate(['name' => 'sometimes',
]);
\App\Models\Product::where('id',$id)->update($validData);


        return response()->json(['message'=>'Updated Successfully'],200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        \Illuminate\Support\Facades\Cache::forget('Product');
\App\Models\Product::findOrFail($id)->delete();


        return response()->json(['message'=>'Deleted Successfully'],200);
    }
}
