<x-guest-layout>
    <div class="flex flex-col justify-center items-center min-h-screen bg-gray-50">
        <div class="p-6 mt-12 w-full max-w-sm bg-white rounded-md border border-gray-200">
            <h2 class="mb-1 text-xl font-semibold text-center text-gray-800">Restablecer Contraseña</h2>
            <p class="mb-6 text-sm text-center text-gray-500">Ingresa tu nueva contraseña para tu cuenta.</p>
            <form method="POST" action="{{ route('password.store') }}">
                @csrf

                <input type="hidden" name="token" value="{{ $request->route('token') }}">

                <div class="mb-4">
                    <x-input-label for="email" :value="__('Correo electrónico')" />
                    <x-text-input id="email" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="email" name="email" :value="old('email', $request->email)" required autofocus />
                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                </div>

                <div class="mb-4">
                    <x-input-label for="password" :value="__('Nueva contraseña')" />
                    <x-text-input id="password" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="password" name="password" required />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>

                <div class="mb-6">
                    <x-input-label for="password_confirmation" :value="__('Confirmar contraseña')" />
                    <x-text-input id="password_confirmation" class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500" type="password" name="password_confirmation" required />
                    <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
                </div>

                <x-primary-button class="justify-center w-full">
                    {{ __('Restablecer Contraseña') }}
                </x-primary-button>
            </form>
        </div>
    </div>
</x-guest-layout>
