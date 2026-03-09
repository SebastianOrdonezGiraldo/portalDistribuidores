<x-app-layout>
    <x-slot name="header">
        <h1 class="text-xl font-extrabold text-slate-900">
            {{ $user->exists ? 'Editar usuario' : 'Nuevo usuario' }}
        </h1>
    </x-slot>

    <section class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="POST" action="{{ $user->exists ? route('admin.users.update', $user) : route('admin.users.store') }}" class="space-y-3">
            @csrf
            @if($user->exists)
                @method('PUT')
            @endif

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block font-semibold text-slate-700">Nombre</span>
                    <input type="text" name="name" required value="{{ old('name', $user->name) }}" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
                </label>
                <label class="block text-sm">
                    <span class="mb-1 block font-semibold text-slate-700">Email</span>
                    <input type="email" name="email" required value="{{ old('email', $user->email) }}" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
                </label>
            </div>

            <div class="grid gap-3 sm:grid-cols-2">
                <label class="block text-sm">
                    <span class="mb-1 block font-semibold text-slate-700">Rol</span>
                    <select name="role" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
                        @foreach($roles as $role)
                            <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value)>{{ ucfirst($role->value) }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="block text-sm">
                    <span class="mb-1 block font-semibold text-slate-700">Distribuidor (si aplica)</span>
                    <select name="distributor_id" class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
                        <option value="">Ninguno</option>
                        @foreach($distributors as $distributor)
                            <option value="{{ $distributor->id }}" @selected((string) old('distributor_id', $user->distributor_id) === (string) $distributor->id)>{{ $distributor->name }}</option>
                        @endforeach
                    </select>
                </label>
            </div>

            <label class="block text-sm">
                <span class="mb-1 block font-semibold text-slate-700">Password {{ $user->exists ? '(opcional)' : '' }}</span>
                <input type="password" name="password" {{ $user->exists ? '' : 'required' }} class="w-full rounded-xl border-slate-300 focus:border-[#8DC543] focus:ring-[#8DC543]">
            </label>

            <div class="flex gap-2">
                <button type="submit" class="btn-primary">Guardar</button>
                <a href="{{ route('admin.users.index') }}" class="btn-secondary">Cancelar</a>
            </div>
        </form>
    </section>
</x-app-layout>

