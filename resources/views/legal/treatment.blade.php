<x-legal-page
    title="Aviso de tratamiento de datos personales"
    subtitle="Autorización y finalidades del tratamiento conforme a la Ley 1581 de 2012."
    :policy-version="$policyVersion"
>
    <p>
        De acuerdo con la Ley 1581 de 2012 y el Decreto 1377 de 2013,
        <strong>{{ $controller['name'] }}</strong> informa a los titulares que los datos
        personales suministrados en el Portal de Distribuidores serán tratados bajo los
        principios de legalidad, finalidad, libertad, veracidad, transparencia, acceso y
        circulación restringida, seguridad y confidencialidad.
    </p>

    <h2>1. Responsable del tratamiento</h2>
    <ul>
        <li>Razón social: {{ $controller['name'] }}</li>
        <li>Ciudad: {{ $controller['city'] }}</li>
        <li>Correo: {{ $controller['email'] }}</li>
        <li>Teléfono: {{ $controller['phone'] }}</li>
    </ul>

    <h2>2. Tratamiento y finalidades</h2>
    <p>Los datos se recolectan, almacenan, usan, circulan y suprimen para:</p>
    <ul>
        <li>Evaluar y gestionar solicitudes de registro como distribuidor.</li>
        <li>Crear y administrar la cuenta de usuario y el perfil de empresa.</li>
        <li>Gestionar pedidos, cotizaciones, pagos manuales y soporte asociado.</li>
        <li>Enviar notificaciones transaccionales por correo electrónico.</li>
        <li>Cumplir deberes legales, contractuales y de auditoría interna.</li>
    </ul>

    <h2>3. Carácter de la respuesta</h2>
    <p>
        Los campos marcados como obligatorios en formularios son necesarios para prestar el
        servicio. Sin ellos no es posible completar el registro o el pedido correspondiente.
    </p>

    <h2>4. Derechos y canal de atención</h2>
    <p>
        El titular puede ejercer los derechos de acceso, actualización, rectificación,
        supresión y revocatoria de la autorización ante ICM a través de
        <a class="font-medium text-brand-primary hover:underline" href="mailto:{{ $controller['email'] }}">{{ $controller['email'] }}</a>.
        La solicitud debe identificar al titular y describir claramente el derecho que desea ejercer.
    </p>

    <h2>5. Autorización</h2>
    <p>
        Al marcar la casilla de aceptación en el registro (o al continuar el uso de servicios
        que lo requieran), el titular autoriza el tratamiento de sus datos para las finalidades
        aquí descritas, en la versión {{ $policyVersion }} de este aviso.
    </p>
</x-legal-page>
