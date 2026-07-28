<x-legal-page
    title="Términos y condiciones de uso"
    subtitle="Condiciones de acceso y uso del Portal de Distribuidores."
    :policy-version="$policyVersion"
>
    <p>
        Estos términos regulan el uso del Portal de Distribuidores operado por
        <strong>{{ $controller['name'] }}</strong>. Al acceder o registrarse, usted acepta
        estas condiciones.
    </p>

    <h2>1. Objeto del portal</h2>
    <p>
        El portal permite a distribuidores autorizados consultar catálogo, solicitar
        cotizaciones, generar pedidos y, cuando aplique, registrar pagos manuales mediante
        carga de comprobantes.
    </p>

    <h2>2. Registro y acceso</h2>
    <ul>
        <li>El registro público es una solicitud sujeta a validación comercial de ICM.</li>
        <li>El acceso queda habilitado únicamente cuando la cuenta y el distribuidor estén activos.</li>
        <li>Usted es responsable de la confidencialidad de sus credenciales.</li>
    </ul>

    <h2>3. Pedidos, precios e inventario</h2>
    <p>
        Los precios, disponibilidad y condiciones comerciales mostrados pueden variar según el
        nivel del distribuidor y la configuración vigente. Las reservas de inventario asociadas
        a pagos manuales tienen un plazo limitado; al vencimiento el pedido puede cancelarse y
        liberarse el stock.
    </p>

    <h2>4. Uso aceptable</h2>
    <p>
        Queda prohibido el uso automatizado abusivo, la extracción masiva no autorizada de
        información, la suplantación de identidad y cualquier actividad que afecte la seguridad
        o disponibilidad del portal.
    </p>

    <h2>5. Propiedad intelectual</h2>
    <p>
        Marcas, contenidos, fotografías y documentación técnica pertenecen a ICM o a sus
        licenciantes. Su uso está limitado a las finalidades del portal.
    </p>

    <h2>6. Limitación de responsabilidad</h2>
    <p>
        ICM procurará la disponibilidad del servicio, sin garantizar operación ininterrumpida.
        No responde por daños derivados de uso indebido de credenciales, información incompleta
        suministrada por el usuario o causas de fuerza mayor.
    </p>

    <h2>7. Datos personales</h2>
    <p>
        El tratamiento de datos se rige por la
        <a class="font-medium text-brand-primary hover:underline" href="{{ route('legal.privacy') }}">política de privacidad</a>
        y el
        <a class="font-medium text-brand-primary hover:underline" href="{{ route('legal.treatment') }}">aviso de tratamiento</a>.
    </p>

    <h2>8. Contacto</h2>
    <p>
        {{ $controller['name'] }} · {{ $controller['city'] }} ·
        <a class="font-medium text-brand-primary hover:underline" href="mailto:{{ $controller['email'] }}">{{ $controller['email'] }}</a>
        · {{ $controller['phone'] }}.
    </p>
</x-legal-page>
