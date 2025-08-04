<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            Agregar Nuevo Producto
        </h2>
    </x-slot>

    <div class="container px-4 py-8 mx-auto sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl">
            <form action="{{ route('inventory.store') }}" method="POST" class="px-8 pt-6 pb-8 mb-4 bg-white rounded shadow-md">
                @csrf
 
                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="name">
                        Nombre del Producto
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('name') border-red-500 @enderror"
                        id="name" type="text" name="name" value="{{ old('name') }}" required>
                    @error('name')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="description">
                        Descripción
                    </label>
                    <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('description') border-red-500 @enderror"
                        id="description" name="description">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="sku">
                        SKU
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('sku') border-red-500 @enderror"
                        id="sku" type="text" name="sku" value="{{ old('sku') }}" required>
                    @error('sku')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="quantity">
                        Cantidad
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('quantity') border-red-500 @enderror"
                        id="quantity" type="number" name="quantity" value="{{ old('quantity') }}" required min="0">
                    @error('quantity')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="price">
                        Precio
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('price') border-red-500 @enderror"
                        id="price" type="number" step="0.01" name="price" value="{{ old('price') }}" required min="0">
                    @error('price')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="category">
                        Categoría
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('category') border-red-500 @enderror"
                        id="category" type="text" name="category" value="{{ old('category') }}">
                    @error('category')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="location">
                        Ubicación
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('location') border-red-500 @enderror"
                        id="location" type="text" name="location" value="{{ old('location') }}">
                    @error('location')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700" for="minimum_stock">
                        Stock Mínimo
                    </label>
                    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline @error('minimum_stock') border-red-500 @enderror"
                        id="minimum_stock" type="number" name="minimum_stock" value="{{ old('minimum_stock', 0) }}" required min="0">
                    @error('minimum_stock')
                        <p class="text-xs italic text-red-500">{{ $message }}</p>
                    @enderror
                </div>

                <div class="mb-4">
                    <label class="block mb-2 text-sm font-bold text-gray-700">
                        Estado
                    </label>
                    <div class="mt-2">
                        <label class="inline-flex items-center">
                            <input type="checkbox" class="form-checkbox" name="active" value="1" {{ old('active', true) ? 'checked' : '' }}>
                            <span class="ml-2">Activo</span>
                        </label>
                    </div>
                </div>

                <div class="flex justify-between items-center">
                    <button class="px-4 py-2 font-bold text-white bg-blue-500 rounded hover:bg-blue-700 focus:outline-none focus:shadow-outline" type="submit">
                        Guardar Producto
                    </button>
                    <a href="{{ route('inventory.index') }}" class="text-gray-600 hover:text-gray-800">
                        Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
