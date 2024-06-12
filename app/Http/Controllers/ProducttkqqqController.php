<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ProducttkqqqController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $index=\Illuminate\Support\Facades\Cache::remember('Producttkqqq', 84000, function () {
return \App\Models\Producttkqqq::all();
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
        \Illuminate\Support\Facades\Cache::forget('Producttkqqq');
$validData=$request->validate([]);
\App\Models\Producttkqqq::create($validData);


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
        $show=\App\Models\Producttkqqq::findOrFail($id);


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
        \Illuminate\Support\Facades\Cache::forget('Producttkqqq');
$validData=$request->validate([]);
\App\Models\Producttkqqq::where('id',$id)->update($validData);


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
        \Illuminate\Support\Facades\Cache::forget('Producttkqqq');
\App\Models\Producttkqqq::findOrFail($id)->delete();


        return response()->json(['message'=>'Deleted Successfully'],200);
    }
}
