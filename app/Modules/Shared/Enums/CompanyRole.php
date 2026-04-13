<?php

namespace App\Modules\Shared\Enums;

enum CompanyRole: string
{
    case AdminEmpresa = 'admin_empresa';
    case UsuarioComercial = 'usuario_comercial';
    case SoloLectura = 'solo_lectura';

    public function label(): string
    {
        return match ($this) {
            self::AdminEmpresa => 'Admin empresa',
            self::UsuarioComercial => 'Usuario comercial',
            self::SoloLectura => 'Solo lectura',
        };
    }

    public function canManageUsers(): bool
    {
        return $this === self::AdminEmpresa;
    }

    public function canEditCompany(): bool
    {
        return $this === self::AdminEmpresa;
    }

    public function canCreateOrders(): bool
    {
        return match ($this) {
            self::AdminEmpresa, self::UsuarioComercial => true,
            default => false,
        };
    }

    public function canReorder(): bool
    {
        return match ($this) {
            self::AdminEmpresa, self::UsuarioComercial => true,
            default => false,
        };
    }

    public function canManageLists(): bool
    {
        return match ($this) {
            self::AdminEmpresa, self::UsuarioComercial => true,
            default => false,
        };
    }

    public function canManageBranches(): bool
    {
        return $this === self::AdminEmpresa;
    }

    public function canApproveOrders(): bool
    {
        return $this === self::AdminEmpresa;
    }

    /**
     * Cuando este rol crea una orden, ¿debe pasar por aprobación interna?
     */
    public function requiresOrderApproval(): bool
    {
        return $this === self::UsuarioComercial;
    }
}
