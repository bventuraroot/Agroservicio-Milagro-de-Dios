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
     * @param  string  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            // Decodificar el ID que viene en base64
            $decodedId = base64_decode($id);

            // Buscar la marca por ID
            $marca = Marca::find($decodedId);

            if (!$marca) {
                return response()->json([
                    'res' => 0,
                    'message' => 'Marca no encontrada'
                ]);
            }

            // Verificar si la marca está siendo utilizada en productos
            $productsCount = \App\Models\Product::where('marca_id', $decodedId)->count();

            if ($productsCount > 0) {
                return response()->json([
                    'res' => 0,
                    'message' => 'No se puede eliminar la marca porque está siendo utilizada por ' . $productsCount . ' producto(s)'
                ]);
            }

            // Eliminar la marca
            $marca->delete();

            return response()->json([
                'res' => 1,
                'message' => 'Marca eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'res' => 0,
                'message' => 'Error al eliminar la marca: ' . $e->getMessage()
            ]);
        }
    }
}
