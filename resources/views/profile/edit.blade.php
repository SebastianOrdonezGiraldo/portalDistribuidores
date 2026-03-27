<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if($user->isDistributor() && $user->distributor_id)
                <div class="p-4 sm:p-8 border border-slate-200 bg-slate-50 shadow sm:rounded-lg">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                        <div class="max-w-2xl">
                            <h2 class="text-lg font-medium text-slate-900">Datos de empresa</h2>
                            <p class="mt-1 text-sm text-slate-600">
                                Administra la información maestra de tu empresa para autocompletar el checkout:
                                razón social, NIT/Cédula, contacto, teléfono y dirección.
                            </p>
                        </div>
                        <div class="shrink-0">
                            <a href="{{ route('empresa.profile.edit') }}" class="btn btn-primary w-full justify-center sm:w-auto">Editar datos de empresa</a>
                        </div>
                    </div>
                </div>
            @endif

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow sm:rounded-lg">
                <div class="max-w-xl">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
