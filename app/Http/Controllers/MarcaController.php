<?php

namespace App\Http\Controllers;

use App\Models\Marca;
use Illuminate\Http\Request;

class MarcaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $marcas = Marca::select('marcas.*')->get();

        return view('marcas.index', [
            'marcas' => $marcas
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

    }

    public function getmarcas(){
        $marca = Marca::all();
        return response()->json($marca);
    }

    public function getmarcaid($id){
        $marca = Marca::select('marcas.*')
        ->where('marcas.id', '=', base64_decode($id))
        ->get();
        return response()->json($marca);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $id_user = auth()->user()->id;
        $marca = new Marca();
        $marca->name = $request->name;
        $marca->description = $request->description;
        $marca->user_id = $id_user;
        $marca->save();
        return redirect()->route('marcas.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Marca  $marca
     * @return \Illuminate\Http\Response
     */
    public function show(Marca $marca)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Marca  $marca
     * @return \Illuminate\Http\Response
     */
    public function edit(Marca $marca)
    {
    }



    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Marca  $marca
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Marca $marca)
    {
        $id_user = auth()->user()->id;
        $marca = Marca::find($request->idupdate);
        $marca->name = $request->nameupdate;
        $marca->description = $request->descriptionupdate;
        $marca->status = $request->statusupdate;
        $marca->user_id = $id_user;
        $marca->save();
        return redirect()->route('marcas.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Marca  $marca
     * @return \Illuminate\Http\Response
     */
    public function destroy(Marca $marca)
    {
        //
    }
}
