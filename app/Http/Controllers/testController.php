<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class testController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $index=\Illuminate\Support\Facades\Cache::remember('test', 84000, function () {
return \App\Models\test::all();
});

        return view('index',$index);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        \Illuminate\Support\Facades\Cache::forget('test');
$validData=$request->validate(['id' => 'required',
'created_at' => 'required',
'updated_at' => 'required',
]);
\App\Models\test::create($validData);


        return back();
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $show=\App\Models\test::findOrFail($id);


        return view('show',$show);
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
        \Illuminate\Support\Facades\Cache::forget('test');
$validData=$request->validate(['id' => 'sometimes',
'created_at' => 'sometimes',
'updated_at' => 'sometimes',
]);
\App\Models\test::where('id',$id)->update($validData);


        return back();
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        \Illuminate\Support\Facades\Cache::forget('test');
\App\Models\test::findOrFail($id)->delete();


        return back();
    }
}
