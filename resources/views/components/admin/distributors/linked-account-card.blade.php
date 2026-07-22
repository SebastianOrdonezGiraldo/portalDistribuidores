@props(['distributor'])

@php
    $account = $distributor->relationLoaded('user') ? $distributor->user : null;
@endphp

<x-ui.card {{ $attributes->merge(['class' => 'p-5']) }}>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="card-title">Cuenta de acceso vinculada</h2>
            <p class="mt-1 text-sm text-slate-600">
                Credenciales del portal asociadas a esta empresa distribuidora.
            </p>
        </div>
        @if($account)
            <x-admin.verification-badge :verified-at="$account->email_verified_at" />
        @endif
    </div>

    @if($account)
        <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2">
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Nombre</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $account->name }}</dd>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Correo</dt>
                <dd class="mt-1 font-semibold text-slate-900">{{ $account->email }}</dd>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Rol</dt>
                <dd class="mt-1">
                    <x-admin.role-badge :role="$account->role" />
                </dd>
            </div>
            <div class="rounded-xl border border-slate-200 bg-slate-50 p-3">
                <dt class="text-xs uppercase tracking-wide text-slate-500">Creada</dt>
                <dd class="mt-1 font-semibold text-slate-900">
                    {{ $account->created_at?->format('d/m/Y H:i') ?? '—' }}
                    @if($account->created_at)
                        <span class="block text-xs font-normal text-slate-500">{{ $account->created_at->diffForHumans() }}</span>
                    @endif
                </dd>
            </div>
        </dl>

        <div class="mt-4 border-t border-slate-200 pt-4">
            <a href="{{ route('admin.users.edit', $account) }}" class="btn btn-secondary">
                Editar cuenta de acceso
            </a>
        </div>
    @else
        <div class="mt-4 rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-5">
            <p class="text-sm font-medium text-slate-900">Sin cuenta vinculada</p>
            <p class="mt-1 text-sm text-slate-600">
                Esta empresa todavía no tiene una cuenta de acceso vinculada.
            </p>
            <a href="{{ route('admin.users.create', ['role' => 'distributor', 'distributor_id' => $distributor->id]) }}"
               class="btn btn-primary mt-4">
                Crear cuenta de acceso
            </a>
        </div>
    @endif
</x-ui.card>
