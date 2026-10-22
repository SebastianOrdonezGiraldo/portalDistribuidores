<x-legal-page
    title="Política de privacidad"
    subtitle="Cómo tratamos la información personal en el Portal de Distribuidores."
    :policy-version="$policyVersion"
>
    <p>
        <strong>{{ $controller['name'] }}</strong> (en adelante, “ICM”), con domicilio en
        {{ $controller['city'] }}, es responsable del tratamiento de los datos personales
        recolectados a través de este portal, conforme a la Ley 1581 de 2012 y demás normas
        aplicables en Colombia.
    </p>

    <h2>1. Datos que recolectamos</h2>
    <p>Según el uso del portal, podemos tratar entre otros:</p>
    <ul>
        <li>Datos de identificación y contacto (nombre, correo, teléfono, ciudad, dirección).</li>
        <li>Datos de la empresa distribuidora (razón social, NIT, datos de contacto comercial).</li>
        <li>Datos de pedidos, cotizaciones, métodos de pago manual y comprobantes cargados.</li>
        <li>Datos técnicos de sesión necesarios para seguridad y operación del sitio (cookies de sesión).</li>
    </ul>

    <h2>2. Finalidades</h2>
    <ul>
        <li>Gestionar el registro, verificación y activación de cuentas de distribuidor.</li>
        <li>Procesar cotizaciones, pedidos, reservas de inventario y validación de pagos.</li>
        <li>Enviar comunicaciones operativas relacionadas con su solicitud o pedido.</li>
        <li>Atender requerimientos comerciales y de soporte.</li>
        <li>Cumplir obligaciones legales y prevenir fraude o abuso de la plataforma.</li>
    </ul>

    <h2>3. Derechos del titular</h2>
    <p>
        Puede conocer, actualizar, rectificar y solicitar la supresión de sus datos, así como
        revocar la autorización cuando proceda, escribiendo a
        <a class="font-medium text-brand-primary hover:underline" href="mailto:{{ $controller['email'] }}">{{ $controller['email'] }}</a>
        o al teléfono {{ $controller['phone'] }}.
    </p>

    <h2>4. Seguridad y retención</h2>
    <p>
        Aplicamos medidas técnicas y organizativas razonables (control de acceso, almacenamiento
        privado de archivos sensibles, cifrado de contraseñas). Conservamos la información el
        tiempo necesario para las finalidades descritas y para obligaciones legales o
        contractuales.
    </p>

    <h2>5. Cookies</h2>
    <p>
        Usamos cookies esenciales de sesión y preferencias básicas de navegación para operar el
        portal de forma segura. Consulte también el aviso mostrado en el banner de cookies.
    </p>

    <h2>6. Contacto</h2>
    <p>
        Responsable: {{ $controller['name'] }} · {{ $controller['city'] }} ·
        <a class="font-medium text-brand-primary hover:underline" href="mailto:{{ $controller['email'] }}">{{ $controller['email'] }}</a>
        · {{ $controller['phone'] }}.
    </p>
</x-legal-page>
