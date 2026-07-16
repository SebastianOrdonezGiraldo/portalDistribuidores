<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#087f86">
    <title>Mantenimiento en curso - Portal de Distribuidores</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <style>
        :root {
            color-scheme: light;
            --ink: #07172f;
            --muted: #53657d;
            --brand: #36b1bb;
            --brand-dark: #087f86;
            --brand-soft: #dff7f8;
            --line: #d9e5eb;
            --surface: rgba(255, 255, 255, .94);
        }

        * { box-sizing: border-box; }

        html { min-width: 320px; background: #edf5f8; }

        body {
            min-height: 100vh;
            margin: 0;
            color: var(--ink);
            font-family: "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background:
                radial-gradient(circle at 13% 15%, rgba(54, 177, 187, .22), transparent 28rem),
                radial-gradient(circle at 88% 82%, rgba(8, 127, 134, .12), transparent 24rem),
                linear-gradient(135deg, #f8fcfd 0%, #edf4f8 52%, #f8fbfc 100%);
        }

        .page {
            position: relative;
            display: grid;
            min-height: 100vh;
            place-items: center;
            overflow: hidden;
            padding: 32px;
        }

        .page::before,
        .page::after {
            position: absolute;
            border: 1px solid rgba(54, 177, 187, .17);
            border-radius: 999px;
            content: "";
            pointer-events: none;
        }

        .page::before { width: 360px; height: 360px; top: -190px; right: -90px; }
        .page::after { width: 240px; height: 240px; bottom: -145px; left: -70px; }

        .shell { width: min(1080px, 100%); }

        .brand {
            display: inline-flex;
            align-items: center;
            gap: 14px;
            margin: 0 0 18px 6px;
        }

        .brand img { display: block; width: auto; height: 42px; }

        .brand-divider { width: 1px; height: 30px; background: #cad8df; }

        .brand-copy { font-size: 14px; font-weight: 750; letter-spacing: -.01em; }

        .card {
            display: grid;
            grid-template-columns: minmax(0, 1.08fr) minmax(340px, .92fr);
            overflow: hidden;
            border: 1px solid rgba(190, 210, 220, .82);
            border-radius: 30px;
            background: var(--surface);
            box-shadow: 0 28px 70px rgba(19, 51, 70, .13);
            backdrop-filter: blur(14px);
        }

        .content { padding: clamp(42px, 6vw, 78px); }

        .eyebrow {
            display: inline-flex;
            align-items: center;
            gap: 9px;
            margin: 0 0 24px;
            color: var(--brand-dark);
            font-size: 12px;
            font-weight: 800;
            letter-spacing: .18em;
            text-transform: uppercase;
        }

        .pulse {
            width: 9px;
            height: 9px;
            border: 2px solid rgba(54, 177, 187, .35);
            border-radius: 50%;
            background: var(--brand);
            box-shadow: 0 0 0 5px rgba(54, 177, 187, .12);
        }

        h1 {
            max-width: 600px;
            margin: 0;
            font-size: clamp(38px, 5vw, 64px);
            font-weight: 800;
            letter-spacing: -.055em;
            line-height: 1.02;
        }

        h1 span { color: var(--brand-dark); }

        .description {
            max-width: 590px;
            margin: 24px 0 0;
            color: var(--muted);
            font-size: clamp(16px, 1.8vw, 19px);
            line-height: 1.7;
        }

        .status {
            display: flex;
            max-width: 540px;
            align-items: center;
            gap: 13px;
            margin-top: 32px;
            padding: 15px 17px;
            border: 1px solid #c7e9eb;
            border-radius: 16px;
            background: #f0fbfb;
            color: #225a60;
            font-size: 14px;
            line-height: 1.45;
        }

        .status-icon {
            display: grid;
            width: 38px;
            height: 38px;
            flex: 0 0 auto;
            place-items: center;
            border-radius: 12px;
            background: #d6f3f4;
            color: var(--brand-dark);
        }

        .status svg { width: 20px; height: 20px; }

        .note { margin: 20px 2px 0; color: #7a8a9b; font-size: 13px; }

        .visual {
            position: relative;
            display: grid;
            min-height: 520px;
            place-items: center;
            overflow: hidden;
            border-left: 1px solid rgba(190, 222, 227, .8);
            background:
                linear-gradient(150deg, rgba(221, 248, 249, .94), rgba(235, 247, 250, .92)),
                #e7f7f8;
        }

        .visual::before {
            position: absolute;
            width: 420px;
            height: 420px;
            border: 62px solid rgba(54, 177, 187, .09);
            border-radius: 50%;
            content: "";
        }

        .illustration { position: relative; width: min(330px, 78%); filter: drop-shadow(0 25px 26px rgba(17, 89, 96, .14)); }

        .footer {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin: 18px 6px 0;
            color: #708294;
            font-size: 12px;
        }

        @media (max-width: 780px) {
            .page { padding: 22px 16px; }
            .brand { margin-left: 2px; }
            .brand img { height: 34px; }
            .brand-copy { display: none; }
            .brand-divider { display: none; }
            .card { grid-template-columns: 1fr; border-radius: 24px; }
            .content { padding: 38px 28px 34px; }
            .visual { min-height: 240px; border-top: 1px solid rgba(190, 222, 227, .8); border-left: 0; }
            .illustration { width: min(245px, 68%); }
            .footer { flex-direction: column; align-items: center; gap: 5px; text-align: center; }
        }

        @media (max-width: 420px) {
            .page { padding: 18px 12px; }
            .content { padding: 32px 22px 28px; }
            .eyebrow { margin-bottom: 18px; letter-spacing: .13em; }
            .status { align-items: flex-start; }
            .visual { min-height: 205px; }
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after { scroll-behavior: auto !important; }
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="shell">
            <header class="brand" aria-label="Import Corporal Medical">
                <img src="{{ asset('images/import-corporal-logo.png') }}" alt="Import Corporal Medical SAS">
                <span class="brand-divider" aria-hidden="true"></span>
                <span class="brand-copy">Portal Distribuidores</span>
            </header>

            <section class="card" aria-labelledby="maintenance-title">
                <div class="content">
                    <p class="eyebrow"><span class="pulse" aria-hidden="true"></span> Actualización en curso</p>
                    <h1 id="maintenance-title">Estamos mejorando <span>tu portal.</span></h1>
                    <p class="description">
                        Estamos realizando ajustes para brindarte una experiencia más ágil y confiable.
                        El portal volverá a estar disponible muy pronto.
                    </p>

                    <div class="status" role="status">
                        <span class="status-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 8v4l2.5 1.5"/><circle cx="12" cy="12" r="8.5"/>
                            </svg>
                        </span>
                        <span><strong>No necesitas hacer nada.</strong><br>Esta página se actualizará automáticamente cuando terminemos.</span>
                    </div>

                    <p class="note">Gracias por tu paciencia y por confiar en Import Corporal Medical.</p>
                </div>

                <div class="visual" aria-hidden="true">
                    <svg class="illustration" viewBox="0 0 420 360" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <ellipse cx="211" cy="319" rx="146" ry="22" fill="#0B6F76" fill-opacity=".12"/>
                        <rect x="91" y="61" width="238" height="226" rx="28" fill="white" stroke="#B9E3E6" stroke-width="3"/>
                        <rect x="91" y="61" width="238" height="53" rx="28" fill="#F4FBFC"/>
                        <path d="M91 98V89C91 73.536 103.536 61 119 61H301C316.464 61 329 73.536 329 89V98H91Z" fill="#DDF5F6"/>
                        <circle cx="120" cy="81" r="6" fill="#36B1BB"/>
                        <circle cx="141" cy="81" r="6" fill="#8CD4D9"/>
                        <circle cx="162" cy="81" r="6" fill="#B8E5E8"/>
                        <rect x="121" y="139" width="178" height="18" rx="9" fill="#E7F1F4"/>
                        <rect x="121" y="171" width="127" height="12" rx="6" fill="#EDF4F6"/>
                        <rect x="121" y="195" width="96" height="12" rx="6" fill="#EDF4F6"/>
                        <circle cx="267" cy="224" r="68" fill="#087F86"/>
                        <path d="M267 188V224L289 237" stroke="white" stroke-width="12" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="267" cy="224" r="48" stroke="#83D6DA" stroke-width="6" stroke-dasharray="8 10"/>
                        <path d="M70 264C54 240 58 210 79 190" stroke="#36B1BB" stroke-width="9" stroke-linecap="round"/>
                        <path d="M64 183L81 189L79 171" stroke="#36B1BB" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                        <path d="M350 148C365 170 364 196 351 216" stroke="#8BD5D9" stroke-width="9" stroke-linecap="round"/>
                        <path d="M367 221L350 216L352 233" stroke="#8BD5D9" stroke-width="9" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </section>

            <footer class="footer">
                <span>&copy; {{ date('Y') }} Import Corporal Medical SAS</span>
                <span>Portal para distribuidores</span>
            </footer>
        </div>
    </main>
</body>
</html>
