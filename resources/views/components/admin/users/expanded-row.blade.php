@props(['user'])

@php
    use App\Modules\Shared\Enums\UserRole;

    $isAdmin = $user->role === UserRole::Admin;
    $isVerified = filled($user->email_verified_at);
    $ordersCount = (int) ($user->orders_count ?? 0);
    $lastActivityAt = $user->updated_at ?? $user->created_at;

    $ordersUrl = $user->distributor_id
        ? route('admin.orders.index', ['distributor_id' => $user->distributor_id])
        : route('admin.orders.index', ['q' => $user->email]);

    $rolePermissionLabel = $isAdmin
        ? 'Acceso total al panel administrativo'
        : 'Acceso al portal distribuidor';
@endphp

<div class="user-expanded-panel">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
        <div class="user-detail-cell">
            <span class="user-detail-icon user-detail-icon--brand" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
            </span>
            <div class="min-w-0">
                <p class="user-detail-label">Correo electrónico</p>
                <p class="user-detail-value truncate">{{ $user->email }}</p>
            </div>
        </div>

        <div class="user-detail-cell">
            <span class="user-detail-icon user-detail-icon--info" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M3 21h18"/><path d="M5 21V7l8-4v18"/><path d="M19 21V11l-6-4"/></svg>
            </span>
            <div class="min-w-0">
                <p class="user-detail-label">Empresa vinculada</p>
                @if($user->distributor)
                    <p class="user-detail-value truncate">{{ $user->distributor->name }}</p>
                    <a href="{{ route('admin.distributors.edit', $user->distributor) }}" class="user-detail-link">
                        Ver empresa
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
                    </a>
                @else
                    <p class="user-detail-value">Sin empresa</p>
                    <p class="user-detail-meta">Cuenta sin vínculo comercial</p>
                @endif
            </div>
        </div>

        <div class="user-detail-cell">
            <span class="user-detail-icon user-detail-icon--neutral" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
            </span>
            <div class="min-w-0">
                <p class="user-detail-label">Rol y permisos</p>
                <div class="mt-1"><x-admin.role-badge :role="$user->role" /></div>
                <p class="user-detail-meta">{{ $rolePermissionLabel }}</p>
            </div>
        </div>

        <div class="user-detail-cell">
            <span class="user-detail-icon user-detail-icon--warning" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
            </span>
            <div class="min-w-0">
                <p class="user-detail-label">Estado de verificación</p>
                <div class="mt-1"><x-admin.verification-badge :verified-at="$user->email_verified_at" /></div>
                <p class="user-detail-meta">
                    {{ $isVerified ? 'Correo verificado correctamente.' : 'Pendiente de verificación del correo.' }}
                </p>
            </div>
        </div>

        <div class="user-detail-cell">
            <span class="user-detail-icon user-detail-icon--success" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18"/><path d="M16 10a4 4 0 0 1-8 0"/></svg>
            </span>
            <div class="min-w-0">
                <p class="user-detail-label">Pedidos asociados</p>
                <p class="user-detail-value">{{ number_format($ordersCount) }}</p>
                @if($ordersCount > 0)
                    <a href="{{ $ordersUrl }}" class="user-detail-link">Ver pedidos</a>
                @else
                    <p class="user-detail-meta">Sin pedidos registrados</p>
                @endif
            </div>
        </div>

        <div class="user-detail-cell">
            <span class="user-detail-icon user-detail-icon--neutral" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
            </span>
            <div class="min-w-0">
                <p class="user-detail-label">Último acceso</p>
                <p class="user-detail-value">{{ $lastActivityAt?->format('d/m/Y H:i') ?? '—' }}</p>
                <p class="user-detail-meta">{{ $lastActivityAt?->diffForHumans() ?? 'Sin actividad registrada' }}</p>
            </div>
        </div>
    </div>

    <div class="mt-4 flex flex-wrap gap-2 border-t border-slate-200/80 pt-4">
        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary gap-2 !px-3 !py-2 text-xs">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
            Editar cuenta
        </a>
        @unless($isVerified)
            <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-secondary gap-2 !px-3 !py-2 text-xs">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m22 2-7 20-4-9-9-4Z"/><path d="M22 2 11 13"/></svg>
                Reenviar verificación
            </a>
        @endunless
    </div>
</div>
