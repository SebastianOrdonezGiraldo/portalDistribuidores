<x-guest-layout>
    <div class="space-y-4">
        <div>
            <h1 class="text-2xl font-semibold text-slate-900">Solicitud recibida</h1>
            <p class="mt-1 text-sm text-slate-600">
                Recibimos tu registro de distribuidor. Nuestro equipo validará la información de tu empresa.
            </p>
        </div>

        <x-auth-session-status :status="session('status')" />

        <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
            Te notificaremos por correo cuando tu acceso esté habilitado. Mientras tanto, no podrás ingresar al portal.
        </div>

        <div class="flex items-center justify-end">
            <a href="{{ route('login') }}" class="btn btn-primary">Volver a iniciar sesión</a>
        </div>
    </div>
</x-guest-layout>
