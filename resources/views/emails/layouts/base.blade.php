<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="x-apple-disable-message-reformatting">
    <title>@yield('title', 'Notificacion')</title>
    <style>
        @media only screen and (max-width: 620px) {
            .email-shell {
                width: 100% !important;
            }
            .email-body {
                padding: 24px 20px !important;
            }
            .email-footer {
                padding: 20px !important;
            }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f1f5f9; font-family: Arial, Helvetica, sans-serif; color: #0f172a;">
    <div style="display: none; max-height: 0; overflow: hidden; opacity: 0; visibility: hidden;">
        @yield('preheader', '')
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color: #f1f5f9; padding: 24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" class="email-shell" width="600" cellpadding="0" cellspacing="0" style="width: 600px; max-width: 600px; background-color: #ffffff; border-radius: 14px; overflow: hidden;">
                    @hasSection('email_header')
                        @yield('email_header')
                    @else
                        <tr>
                            <td style="padding: 20px 28px; background-color: #051b26; border-bottom: 4px solid #36b1bb;">
                                <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td valign="middle">
                                            <img src="{{ rtrim((string) config('app.url'), '/') }}/images/import-corporal-logo.png" alt="Import Corporal Medical SAS" width="150" style="display: block; width: 150px; height: auto; max-width: 150px;">
                                        </td>
                                        <td valign="middle" align="right" style="font-size: 12px; color: #cbd5e1; letter-spacing: 0.3px;">
                                            Portal de Distribuidores
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    @endif

                    <tr>
                        <td class="email-body" style="padding: 32px 36px;">
                            <h1 style="margin: 0 0 14px; font-size: 24px; line-height: 1.3; color: #0f172a;">
                                @yield('heading')
                            </h1>

                            <div style="font-size: 15px; line-height: 1.65; color: #334155;">
                                @yield('content')
                            </div>

                            @hasSection('cta')
                                <div style="margin-top: 24px;">
                                    @yield('cta')
                                </div>
                            @endif
                        </td>
                    </tr>

                    <tr>
                        <td class="email-footer" style="padding: 22px 36px; background-color: #f8fafc; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0 0 8px; font-size: 12px; line-height: 1.5; color: #475569;">
                                Import Corporal Medical SAS
                            </p>
                            <p style="margin: 0 0 8px; font-size: 12px; line-height: 1.5; color: #64748b;">
                                Este es un correo informativo. Si necesitas ayuda, responde este mensaje.
                            </p>
                            <p style="margin: 0; font-size: 11px; line-height: 1.5; color: #94a3b8;">
                                © {{ date('Y') }} Import Corporal Medical SAS. Todos los derechos reservados.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
