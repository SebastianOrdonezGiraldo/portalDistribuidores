@php
    $sections = [
        [
            'title' => 'Canales de atención',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Para presentar una solicitud o consultar su estado, comunícate con nosotros:'],
                ['type' => 'contact', 'items' => [
                    ['label' => 'Correo electrónico', 'value' => 'admin@importcorporalmedical.com', 'href' => 'mailto:admin@importcorporalmedical.com'],
                    ['label' => 'Teléfono y WhatsApp', 'value' => '+57 321 238 7916', 'href' => 'tel:+573212387916'],
                    ['label' => 'Dirección para recepción de equipos', 'value' => 'Av. Bolívar # 8 Norte - 67, oficina 406, Armenia Hotel, Armenia, Quindío, Colombia'],
                    ['label' => 'Horario de atención', 'value' => 'Lunes a viernes, de 8:15 a. m. a 5:45 p. m., excepto festivos.'],
                ]],
                ['type' => 'paragraph', 'text' => 'Antes de enviar un producto, recomendamos comunicarte con nuestro equipo para coordinar su recepción y recibir instrucciones de embalaje y transporte. Esta coordinación no constituye una autorización previa obligatoria para ejercer tus derechos legales.'],
            ],
        ],
        [
            'title' => 'Motivos de devolución o reclamación',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Atenderemos, entre otras, las solicitudes relacionadas con:'],
                ['type' => 'list', 'items' => [
                    'Errores de despacho: diferencias entre lo comprado y lo recibido, como referencia, cantidad, presentación o accesorios incluidos.',
                    'Novedades en la entrega: daños en el producto, deterioro del embalaje, faltantes o sellos que hayan llegado abiertos o alterados.',
                    'Defectos de calidad o funcionamiento: situaciones que deban evaluarse mediante el procedimiento de garantía.',
                    'Retracto o cambios voluntarios: cuando se cumplan las condiciones correspondientes de esta política.',
                ]],
                ['type' => 'paragraph', 'text' => 'La solución dependerá del motivo y de las circunstancias del caso. La recepción de una solicitud o de un producto no implica, por sí sola, que proceda automáticamente el reembolso.'],
            ],
        ],
        [
            'title' => 'Derecho de retracto',
            'blocks' => [
                ['type' => 'subheading', 'text' => 'Plazo y aplicación'],
                ['type' => 'paragraph', 'text' => 'Cuando la compra esté amparada por el derecho de retracto, podrás ejercerlo dentro de los cinco (5) días hábiles siguientes a la entrega del producto, sin necesidad de justificar tu decisión.'],
                ['type' => 'paragraph', 'text' => 'Este derecho aplica a las ventas a distancia, como las realizadas por internet o teléfono, y a los demás casos previstos legalmente. No se reconoce de manera general para toda compra presencial.'],
                ['type' => 'subheading', 'text' => 'Condiciones del producto'],
                ['type' => 'paragraph', 'text' => 'El producto debe devolverse en las condiciones en que fue recibido, completo y con los accesorios, componentes y manuales que acompañaron la compra.'],
                ['type' => 'paragraph', 'text' => 'Conserva el empaque original y utiliza una protección adecuada para el transporte. La revisión del producto tendrá como finalidad verificar las condiciones de la devolución y la procedencia del retracto.'],
                ['type' => 'subheading', 'text' => 'Excepciones'],
                ['type' => 'paragraph', 'text' => 'El retracto no procede en las excepciones previstas en el artículo 47 de la Ley 1480 de 2011. Entre las relevantes para los productos comercializados se encuentran los bienes de uso personal, los elaborados conforme a especificaciones del comprador o claramente personalizados, los perecederos y aquellos que, por su naturaleza, no puedan devolverse o se deterioren o caduquen rápidamente.'],
                ['type' => 'paragraph', 'text' => 'La excepción se evaluará según las características concretas del producto; no se excluirán todos los equipos de manera general por el solo hecho de ser médicos o terapéuticos. Estas excepciones no eliminan la posibilidad de reclamar por un defecto.'],
                ['type' => 'subheading', 'text' => 'Transporte y reembolso'],
                ['type' => 'paragraph', 'text' => 'El consumidor asumirá los gastos de transporte correspondientes a la devolución por retracto.'],
                ['type' => 'paragraph', 'text' => 'Reintegraremos las sumas pagadas correspondientes a la compra objeto del retracto, incluido el flete inicial cobrado por nosotros que corresponda a esa compra, sin penalidades ni cargos de reposición de inventario.'],
                ['type' => 'paragraph', 'text' => 'El reembolso se realizará dentro de un máximo de quince (15) días calendario, una vez ejercido el derecho y cumplidas las obligaciones de devolver el producto y suministrar los datos correctos y completos necesarios para efectuar el reintegro. Este término no comienza nuevamente por una aprobación interna de la empresa.'],
                ['type' => 'paragraph', 'text' => 'El reintegro se realizará al medio de pago correspondiente o mediante otro medio acordado expresamente. No se impondrán bonos o saldos a favor como sustitución del dinero.'],
            ],
        ],
        [
            'title' => 'Cambios voluntarios por talla, color o referencia',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Como beneficio comercial, podrás solicitar un cambio dentro de los quince (15) días calendario siguientes a la entrega, siempre que el producto esté sin uso, completo, en perfecto estado y con su empaque original.'],
                ['type' => 'paragraph', 'text' => 'Los cambios estarán sujetos a disponibilidad de inventario. Si la referencia solicitada no se encuentra disponible, te informaremos las alternativas de cambio, saldo a favor o reembolso que correspondan, para acordar la solución.'],
                ['type' => 'paragraph', 'text' => 'Los gastos de envío de regreso y del nuevo despacho serán asumidos por el cliente, excepto cuando el cambio se origine en un error de Import Corporal Medical S.A.S.'],
                ['type' => 'subheading', 'text' => 'Restricciones para cambios y devoluciones comerciales'],
                ['type' => 'paragraph', 'text' => 'No aceptamos devoluciones comerciales de inventario motivadas exclusivamente por baja rotación, sobrecompra, falta de ventas o acumulación de existencias, salvo acuerdo escrito con la empresa.'],
                ['type' => 'paragraph', 'text' => 'Tampoco procederán los cambios voluntarios de productos usados, incompletos, personalizados, deteriorados por el cliente o con alteraciones en sus componentes o dispositivos de seguridad. Los productos de higiene o uso personal abiertos o utilizados no estarán incluidos en este beneficio comercial.'],
                ['type' => 'paragraph', 'text' => 'El deterioro o vencimiento ocasionado durante el almacenamiento del cliente, por incumplir las instrucciones de conservación, no constituye por sí mismo una causa de devolución comercial.'],
                ['type' => 'paragraph', 'text' => 'Estas restricciones se refieren a cambios voluntarios y devoluciones comerciales. No se utilizarán para rechazar automáticamente una garantía, un error de entrega o un retracto legalmente procedente.'],
            ],
        ],
        [
            'title' => 'Garantía por defectos de calidad o funcionamiento',
            'blocks' => [
                ['type' => 'subheading', 'text' => 'Término y cobertura'],
                ['type' => 'paragraph', 'text' => 'Cada producto tendrá el término de garantía informado al momento de la compra, respetando las disposiciones obligatorias aplicables. Cuando no se haya informado un término y no exista una regla especial, los productos nuevos tendrán una garantía de un (1) año desde su entrega.'],
                ['type' => 'paragraph', 'text' => 'La garantía cubre defectos de calidad, idoneidad, seguridad o funcionamiento. Su término se suspende mientras el consumidor esté privado del uso del producto con ocasión de su atención en garantía. Si se reemplaza totalmente el producto, el término comienza nuevamente desde la entrega del reemplazo.'],
                ['type' => 'subheading', 'text' => 'Accesorios y consumibles'],
                ['type' => 'paragraph', 'text' => 'Los cables, electrodos, baterías, adaptadores y demás accesorios o consumibles están sujetos a desgaste normal, que no equivale necesariamente a un defecto.'],
                ['type' => 'paragraph', 'text' => 'Sin embargo, podrán reclamarse defectos de fabricación o de calidad dentro del término aplicable. La recomendación de reportar una falla inicial durante las primeras 48 horas no limita la garantía a ese período.'],
                ['type' => 'subheading', 'text' => 'Evaluación técnica'],
                ['type' => 'paragraph', 'text' => 'Se evaluará si la falla corresponde a un defecto cubierto o si fue causada por uso indebido, instalación incorrecta, almacenamiento inadecuado, golpes, exposición incompatible con las especificaciones, sobrecargas eléctricas o intervenciones de terceros.'],
                ['type' => 'paragraph', 'text' => 'Una marca de uso, un sello alterado o una intervención no justifican por sí solos la pérdida de toda garantía. Cuando se niegue la cobertura por alguna de estas circunstancias, se explicará y sustentará su relación con el defecto reclamado.'],
                ['type' => 'subheading', 'text' => 'Soluciones'],
                ['type' => 'paragraph', 'text' => 'Como regla general, procederá la reparación gratuita. Cuando el producto no admita reparación o se repita la falla, se atenderán las opciones de cambio o devolución del dinero y las demás alternativas que correspondan legalmente, respetando la elección del consumidor cuando proceda.'],
                ['type' => 'paragraph', 'text' => 'En productos de un solo uso o desechables, la solución por garantía será el cambio o la devolución correspondiente, no una reparación.'],
                ['type' => 'paragraph', 'text' => 'No exigiremos que el producto esté sin uso ni que se conserve su caja original para recibir una reclamación de garantía. Sí deberá entregarse protegido y con los componentes necesarios para evaluar la falla.'],
            ],
        ],
        [
            'title' => 'Errores de despacho, daños de transporte y faltantes',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Al recibir tu pedido, recomendamos revisar la cantidad de paquetes, las referencias, el estado del embalaje y los accesorios incluidos.'],
                ['type' => 'paragraph', 'text' => 'Si encuentras una novedad, comunícala lo antes posible, preferiblemente dentro de las 48 horas siguientes a la entrega, para facilitar la revisión con el área logística y la transportadora.'],
                ['type' => 'paragraph', 'text' => 'Cuando sea posible, conserva el embalaje y adjunta fotografías de la guía, la caja y el producto. Un video de apertura puede ayudar, pero no es un requisito indispensable para presentar la reclamación.'],
                ['type' => 'paragraph', 'text' => 'Si el producto recibido es incorrecto, evita utilizarlo y comunícate con nosotros para coordinar la solución. Si adviertes daños o una posible alteración que afecte su seguridad, suspende su uso.'],
                ['type' => 'paragraph', 'text' => 'Cuando confirmemos un error de despacho o una afectación atribuible a la entrega gestionada por nosotros, coordinaremos la corrección, reposición, complemento del pedido o solución correspondiente sin trasladarte los costos asociados.'],
                ['type' => 'paragraph', 'text' => 'El plazo recomendado de 48 horas no elimina los derechos legales que puedan ejercerse posteriormente.'],
            ],
        ],
        [
            'title' => 'Cómo presentar una solicitud',
            'blocks' => [
                ['type' => 'list', 'items' => [
                    'Primero, comunícate por nuestros canales de atención. Indica tus datos de contacto, el producto involucrado, la fecha aproximada de compra y lo que solicitas. Para ejercer el retracto basta con manifestar claramente esa decisión.',
                    'Segundo, aporta la información disponible. Puedes enviar número de pedido, factura, comprobante de pago, referencia, número de serie o lote, cuando corresponda. Las fotografías y los videos son soportes útiles, pero no constituyen el único medio de prueba.',
                    'La factura facilita la identificación de la compra, pero su presentación no será el único medio admitido para ejercer tus derechos.',
                    'Tercero, recibe las instrucciones de atención. Te informaremos cómo poner el producto a disposición, si requiere evaluación técnica y cómo se coordinará el transporte.',
                    'Cuarto, conserva el número de caso y los soportes de envío. Dejaremos constancia de la solicitud para que puedas consultar su estado. La asignación del número de caso no modifica la fecha en que presentaste la reclamación.',
                ]],
            ],
        ],
        [
            'title' => 'Costos de transporte y servicios no cubiertos',
            'blocks' => [
                ['type' => 'list', 'items' => [
                    'Errores de despacho o daños atribuibles a la entrega gestionada por la empresa: Import Corporal Medical S.A.S. asumirá los costos necesarios para corregir la situación.',
                    'Garantía procedente: asumiremos los costos necesarios de transporte para su atención. Coordinaremos la recogida o el mecanismo de envío, evitando que el consumidor tenga que asumir definitivamente un gasto que corresponde a la garantía.',
                    'Retracto o cambio voluntario sin error de la empresa: el cliente asumirá los gastos indicados en las secciones correspondientes.',
                ]],
                ['type' => 'paragraph', 'text' => 'Cuando el diagnóstico determine que la falla no está cubierta, informaremos las razones y presentaremos una cotización de los servicios y costos que correspondan. No realizaremos reparaciones con cargo al cliente sin su autorización previa.'],
                ['type' => 'paragraph', 'text' => 'Los envíos con flete por cobrar deberán coordinarse previamente con nuestro equipo.'],
            ],
        ],
        [
            'title' => 'Tiempos de atención',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Confirmaremos la recepción de la solicitud y su número de caso en un máximo de dos (2) días hábiles. Para reclamaciones directas de consumidores, emitiremos respuesta de fondo dentro de los quince (15) días hábiles siguientes a su recepción, en los términos legales.'],
                ['type' => 'paragraph', 'text' => 'La revisión de devoluciones que no requieran evaluación técnica se realizará en un máximo de siete (7) días hábiles desde la recepción del producto.'],
                ['type' => 'paragraph', 'text' => 'El diagnóstico técnico se efectuará en un máximo de diez (10) días hábiles desde la recepción del equipo. Como compromiso de servicio, la reparación se realizará en un máximo de treinta (30) días calendario, contados desde el día siguiente a su entrega para reparación. El diagnóstico está incluido en ese plazo, no se suma a él.'],
                ['type' => 'paragraph', 'text' => 'Cuando corresponda hacer efectiva la garantía mediante reposición o devolución del dinero, se aplicarán, salvo regla especial, los plazos de diez (10) días hábiles para la reposición y quince (15) días hábiles para el reembolso, contados desde la puesta a disposición del bien en los términos reglamentarios.'],
                ['type' => 'paragraph', 'text' => 'Ningún plazo interno de revisión, diagnóstico o aprobación ampliará los términos legales de respuesta o reembolso.'],
            ],
        ],
        [
            'title' => 'Pedidos no entregados y reversión del pago',
            'blocks' => [
                ['type' => 'subheading', 'text' => 'Incumplimiento de la entrega'],
                ['type' => 'paragraph', 'text' => 'Si el pedido no se entrega dentro del plazo acordado o el producto comprado no está disponible, podrás ejercer los derechos de terminación y devolución previstos para el comercio electrónico. Cuando no se haya acordado un plazo, se aplicará el término legal de entrega.'],
                ['type' => 'paragraph', 'text' => 'En los casos de terminación contemplados por la ley, el reintegro se efectuará sin retenciones ni descuentos y dentro del máximo de quince (15) días calendario.'],
                ['type' => 'subheading', 'text' => 'Reversión del pago'],
                ['type' => 'paragraph', 'text' => 'La reversión del pago es un mecanismo diferente del retracto. Puede proceder en compras electrónicas pagadas mediante instrumentos de pago electrónicos, por fraude, operaciones no solicitadas, productos no recibidos, distintos de los pedidos o defectuosos.'],
                ['type' => 'paragraph', 'text' => 'Para solicitarla, el consumidor deberá reclamar ante el proveedor y notificar al emisor del medio de pago dentro de los cinco (5) días hábiles, contados desde el hecho que corresponda a cada causal, y poner el producto a disposición cuando proceda.'],
                ['type' => 'paragraph', 'text' => 'Su trámite no exige esperar a que finalice una revisión interna de la empresa.'],
            ],
        ],
        [
            'title' => 'Embalaje, conservación y seguridad',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Utiliza un embalaje que proteja el producto durante el transporte. Conserva la guía y evita enviar accesorios ajenos a la solicitud.'],
                ['type' => 'paragraph', 'text' => 'Para equipos reutilizables, sigue las instrucciones de limpieza del fabricante. No abras, desarmes, modifiques ni desinfectes de manera no autorizada un producto para preparar una reclamación.'],
                ['type' => 'paragraph', 'text' => 'Si existe riesgo de contaminación, presencia de fluidos o una condición que requiera manejo especial, infórmalo antes del envío para coordinar una recepción segura.'],
                ['type' => 'paragraph', 'text' => 'En equipos que almacenen información, realiza una copia de seguridad cuando sea posible. Esta recomendación no constituye una exoneración general de las responsabilidades legales de la empresa.'],
            ],
        ],
        [
            'title' => 'Compras empresariales y actualizaciones',
            'blocks' => [
                ['type' => 'paragraph', 'text' => 'Las compras para reventa se atenderán según las condiciones comerciales acordadas y el régimen jurídico aplicable. No se presumirá que toda persona jurídica carece de derechos como consumidora: se tendrá en cuenta la finalidad concreta de la compra.'],
                ['type' => 'paragraph', 'text' => 'Las actualizaciones de esta política se publicarán en nuestros canales oficiales y no afectarán retroactivamente los derechos ni las condiciones más favorables adquiridas en compras anteriores.'],
            ],
        ],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="mx-auto flex max-w-6xl flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.18em] text-brand-primary">Información al consumidor</p>
                <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950 sm:text-4xl">Política de devoluciones</h1>
                <p class="mt-3 max-w-3xl text-base leading-relaxed text-slate-600">Cambios, devoluciones, derecho de retracto y garantías de Import Corporal Medical S.A.S.</p>
            </div>
            <div class="flex shrink-0 items-center gap-2 rounded-full border border-brand-primary/20 bg-brand-primary/5 px-3 py-2 text-xs font-semibold text-brand-dark">
                <svg class="h-4 w-4 text-brand-primary" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16l-7-3-7 3V5a2 2 0 0 1 2-2Z"/><path d="M9 8h6M9 12h6"/></svg>
                Última actualización: 15/10/2025
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl pb-8">
        <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-panel">
            <div class="relative overflow-hidden border-b border-slate-200 bg-slate-950 px-5 py-8 text-white sm:px-8 lg:px-10">
                <div class="absolute -right-20 -top-28 h-72 w-72 rounded-full bg-brand-primary/25 blur-3xl" aria-hidden="true"></div>
                <div class="relative max-w-3xl">
                    <p class="text-sm font-semibold uppercase tracking-[0.14em] text-cyan-200">Import Corporal Medical S.A.S.</p>
                    <p class="mt-4 text-lg leading-relaxed text-slate-200">Atendemos solicitudes relacionadas con cambios, devoluciones, errores de despacho, daños durante el transporte y garantías de los productos adquiridos a través de nuestros canales oficiales.</p>
                    <p class="mt-4 text-sm leading-relaxed text-slate-300">Ninguna disposición de esta política limita los derechos que la legislación colombiana reconoce al consumidor.</p>
                </div>
            </div>

            <div class="grid gap-8 p-5 sm:p-8 lg:grid-cols-[15rem_minmax(0,1fr)] lg:p-10">
                <aside class="lg:sticky lg:top-24 lg:self-start">
                    <details class="group rounded-2xl border border-slate-200 bg-slate-50 lg:hidden">
                        <summary class="flex cursor-pointer list-none items-center justify-between px-4 py-3 text-sm font-semibold text-slate-900">
                            <span>En esta política</span>
                            <svg class="h-4 w-4 text-slate-500 transition group-open:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
                        </summary>
                        <nav class="border-t border-slate-200 px-4 py-3" aria-label="Secciones de la política">
                            <ol class="space-y-2 text-sm text-slate-600">
                                @foreach($sections as $index => $section)
                                    <li><a class="transition hover:text-brand-dark" href="#politica-seccion-{{ $index + 1 }}">{{ $index + 1 }}. {{ $section['title'] }}</a></li>
                                @endforeach
                            </ol>
                        </nav>
                    </details>

                    <div class="hidden lg:block">
                        <p class="text-xs font-bold uppercase tracking-[0.14em] text-slate-400">En esta política</p>
                        <nav class="mt-4 border-l border-slate-200 pl-4" aria-label="Secciones de la política">
                            <ol class="space-y-3 text-sm leading-snug text-slate-600">
                                @foreach($sections as $index => $section)
                                    <li><a class="transition hover:text-brand-dark" href="#politica-seccion-{{ $index + 1 }}">{{ $index + 1 }}. {{ $section['title'] }}</a></li>
                                @endforeach
                            </ol>
                        </nav>
                    </div>
                </aside>

                <article class="min-w-0">
                    <div class="space-y-10">
                        @foreach($sections as $index => $section)
                            <section id="politica-seccion-{{ $index + 1 }}" class="scroll-mt-28 {{ $index > 0 ? 'border-t border-slate-200 pt-10' : '' }}">
                                <div class="flex items-start gap-3">
                                    <span class="mt-0.5 inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-brand-primary/10 text-sm font-bold text-brand-dark">{{ $index + 1 }}</span>
                                    <h2 class="text-xl font-bold leading-tight text-slate-950 sm:text-2xl">{{ $section['title'] }}</h2>
                                </div>

                                <div class="mt-5 space-y-4 pl-11 text-[0.98rem] leading-8 text-slate-600">
                                    @foreach($section['blocks'] as $block)
                                        @if($block['type'] === 'subheading')
                                            <h3 class="-mb-1 pt-2 text-base font-bold leading-6 text-slate-900">{{ $block['text'] }}</h3>
                                        @elseif($block['type'] === 'list')
                                            <ul class="space-y-3 pl-5 marker:text-brand-primary">
                                                @foreach($block['items'] as $item)
                                                    <li class="pl-2">{{ $item }}</li>
                                                @endforeach
                                            </ul>
                                        @elseif($block['type'] === 'contact')
                                            <dl class="grid gap-3 rounded-2xl border border-brand-primary/15 bg-brand-primary/5 p-4 sm:grid-cols-2 sm:p-5">
                                                @foreach($block['items'] as $item)
                                                    <div>
                                                        <dt class="text-xs font-bold uppercase tracking-[0.1em] text-slate-500">{{ $item['label'] }}</dt>
                                                        <dd class="mt-1 text-sm leading-6 text-slate-700">
                                                            @if(isset($item['href']))
                                                                <a class="font-semibold text-brand-dark underline decoration-brand-primary/40 underline-offset-2 hover:decoration-brand-primary" href="{{ $item['href'] }}">{{ $item['value'] }}</a>
                                                            @else
                                                                {{ $item['value'] }}
                                                            @endif
                                                        </dd>
                                                    </div>
                                                @endforeach
                                            </dl>
                                        @else
                                            <p>{{ $block['text'] }}</p>
                                        @endif
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>

                    <div class="mt-10 rounded-2xl border border-slate-200 bg-slate-50 p-5 sm:p-6">
                        <p class="text-sm font-semibold text-slate-900">¿Necesitas presentar una solicitud?</p>
                        <p class="mt-1 text-sm leading-6 text-slate-600">Comunícate con nuestro equipo por correo electrónico o WhatsApp para recibir orientación sobre el siguiente paso.</p>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <a href="mailto:admin@importcorporalmedical.com" class="btn btn-primary">Escribir por correo</a>
                            <a href="https://wa.me/573212387916" target="_blank" rel="noopener noreferrer" class="btn btn-secondary">Hablar por WhatsApp</a>
                        </div>
                    </div>
                </article>
            </div>
        </div>
    </div>
</x-app-layout>
