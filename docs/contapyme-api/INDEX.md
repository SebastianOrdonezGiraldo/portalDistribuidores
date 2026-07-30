# Índice de la API ContaPyme (Agente de Servicios Web)

> Índice generado únicamente a partir de la documentación local en `docs/contapyme-api/`, convertida desde el HTML oficial de ContaPyme. Si un dato no aparece explícitamente en la documentación, se indica `No documentado`.

**Funciones documentadas en este índice:** 182

## Tabla de contenidos

1. [Explicación general del funcionamiento de la API](#1-explicación-general-del-funcionamiento-de-la-api)
2. [Flujo de autenticación con GetAuth()](#2-flujo-de-autenticación-con-getauth)
3. [Estructura general de las URLs REST DataSnap](#3-estructura-general-de-las-urls-rest-datasnap)
4. [Parámetros comunes](#4-parámetros-comunes)
5. [Funciones agrupadas por módulo](#5-funciones-agrupadas-por-módulo)
6. [Sección especial: módulo de inventarios](#6-sección-especial-módulo-de-inventarios)
7. [Autenticación, cierre de sesión y verificación del Agente](#7-autenticación-cierre-de-sesión-y-verificación-del-agente)
8. [Códigos de error y eventualidades](#8-códigos-de-error-y-eventualidades)
9. [Funciones peligrosas (modifican información)](#9-funciones-peligrosas-modifican-información)
10. [Validación de referencias a archivos de origen](#10-validación-de-referencias-a-archivos-de-origen)

## 1. Explicación general del funcionamiento de la API

Según `inicio.md` (fuente: https://www.contapyme.com/api/):

- El web service (**Agente**) de ContaPyme expone funciones para acceder a la información del sistema central.
- El protocolo de transferencia de datos es **REST**.
- Los llamados y las respuestas de las peticiones están en formato **JSON**.
- Lo primero que se debe hacer es iniciar sesión con **GetAuth()** para obtener un identificador de usuario (`keyagente`) que permite realizar peticiones al Agente.
- Después de obtener el identificador, se pueden realizar peticiones para obtener o insertar información en ContaPyme.
- Para registrar una operación se usa **DoExecuteOprAction()** con la acción `"New"`, el identificador del tipo de operación y los datos.
- Para obtener un informe en PDF se usa **GetPDF()**.
- Para cerrar la conexión se usa **Logout()**.
- Para verificar el estado del Agente se usa **Test()**.

Módulos documentados en el portal (según inicio):

- Módulo básico
- Módulo cartera y proveedores
- Módulo contabilidad
- Módulo costos
- Módulo inventarios
- Módulo activos
- Módulo inventarios plus
- Módulo actividades
- Módulo automatización de documentos

Nota del portal: el mensaje *«Usted no tiene permisos para acceder a la documentación...»* aparece en la página de inicio descargada; aun así, varias páginas de funciones quedaron disponibles localmente.

## 2. Flujo de autenticación con GetAuth()

Fuente: [`005_INTRODUCCION/020-GetAuth.md`](005_INTRODUCCION/020-GetAuth.md) → HTML: `005_INTRODUCCION/020-GetAuth.html`

1. Llamar **GetAuth(datajson, controlkey, iapp, random)** en el controlador `TBasicoGeneral`.
2. En `dataJSON` enviar: `email` (requerido), `password` en mayúsculas y encriptado MD5 (requerido), `idmaquina` (opcional).
3. La respuesta exitosa incluye en `respuesta.datos`: `keyagente`, `version`, `release`, `actualizacion`.
4. El `keyagente` retornado se usa como **`controlkey`** en las peticiones posteriores.
5. GetAuth es la primera función que se debe ejecutar para establecer comunicación con el Agente (según su documentación).
6. Al finalizar, cerrar sesión con **Logout()** (`005_INTRODUCCION/130-Logout.html`).

Nota: en el ejemplo JavaScript de GetAuth, el `controlkey` inicial se obtiene con `getControlKey(URLUbicacion)` (modo aprendizaje). El detalle de esa función auxiliar: **No documentado** en las páginas de función revisadas (solo aparece en ejemplos).

## 3. Estructura general de las URLs REST DataSnap

Patrón observado en los ejemplos de la documentación:

```text
{URLUbicacion}/datasnap/rest/{ClaseDataSnap}/"{NombreFuncion}"/
```

Donde:

- `{URLUbicacion}`: dirección donde está el Agente (ej. en ejemplos: `http://local.insoft.co:9000`).
- `{ClaseDataSnap}`: clase/controlador DataSnap (ej. `TBasicoGeneral`, `TCatElemInv`).
- `{NombreFuncion}`: nombre de la función entre comillas en la ruta.

Cuerpo POST típico (según ejemplos):

```json
{
  "_parameters": [
    "<dataJSON stringificado>",
    "<controlkey>",
    "<iapp>",
    "<random>"
  ]
}
```

El verbo HTTP usado en los ejemplos JavaScript es **POST**. Varias páginas enlazan documentación adicional de petición por GET en PDF; el detalle de GET: consultar el PDF referido en cada función o indicar No documentado si el PDF no está disponible como página de función.

## 4. Parámetros comunes

Definiciones tomadas de la documentación de **GetAuth()** (`005_INTRODUCCION/020-GetAuth.html`), que documenta los cuatro parámetros estándar de la API:

### datajson / dataJSON

Tipo: JSON. Json que contiene en su interior la siguiente estructura. **email:** Correo electrónico con el cual se encuentra registrado el usuario en la aplicación ContaPyme/AgroWin. (requerido) **password:** Contraseña del usuario asignada en el sistema. Para enviar este parámetro en el llamado de la función se debe convertir a mayúscula y encriptarlo en MD5. (requerido) **idmaquina:** Código que se genera por cada equipo, se debe generar con las características del equipo desde el cual se está accediendo. El idmaquina permite restringir el acceso de varios usuarios con los mismos datos de logueo. (email y password). (opcional)

En el resto de funciones, `dataJSON` transporta la estructura específica de negocio de cada operación (filtros, campos, acciones, etc.).

### controlkey

Tipo: Varchar. Corresponde al keyagente obtenido en el logueo (requerido).

Tras el logueo exitoso, corresponde al `keyagente` retornado por GetAuth (según la descripción de respuesta de GetAuth).

### iapp

Tipo: Varchar. Código que identifica a la aplicación que interactúa con el Agente (requerido)

### random

Tipo: Varchar. Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional).

## 5. Funciones agrupadas por módulo

Para cada función se listan: nombre, clase DataSnap, ruta REST, objetivo, tipo de operación, parámetros principales, estructura principal de respuesta y archivo HTML de origen.

### Introducción / Autenticación y Agente

#### Sesión y Agente

##### `Test`

- **Nombre:** `Test`
- **Firma documentada:** `Test () : string`
- **Clase / controlador DataSnap:** `No documentado`
- **Ruta REST:** `No documentado`
- **Objetivo:** Esta función es la encargada de retornar mediante un código el estado en el que se encuentra el Agente de servicios web.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[]: string de estado (código|mensaje)
- **Archivo HTML de origen:** `005_INTRODUCCION/010-Test.html`
- **Archivo local (Markdown):** [`005_INTRODUCCION/010-Test.md`](005_INTRODUCCION/010-Test.md)

##### `GetAuth`

- **Nombre:** `GetAuth`
- **Firma documentada:** `GetAuth (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetAuth"/`
- **Objetivo:** Esta función es la encargada de loguear a un usuario en el Agente y retornar un código único para la sesión del usuario (keyAgente). Ésta es la primera función que se debe ejecutar para establecer comunicación con el Agente.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: email, password, idmaquina; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `005_INTRODUCCION/020-GetAuth.html`
- **Archivo local (Markdown):** [`005_INTRODUCCION/020-GetAuth.md`](005_INTRODUCCION/020-GetAuth.md)

##### `Logout` **[PELIGROSA — escritura]**

- **Nombre:** `Logout`
- **Firma documentada:** `Logout (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"Logout"/`
- **Objetivo:** Esta función es la encargada de cerrar la sesión del usuario en el Agente, es decir elimina los datos del usuario de la lista de usuarios logueados.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `005_INTRODUCCION/130-Logout.html`
- **Archivo local (Markdown):** [`005_INTRODUCCION/130-Logout.md`](005_INTRODUCCION/130-Logout.md)

### Módulo básico

#### 010_Fun_Basicas

##### `GetDatosTrabajo`

- **Nombre:** `GetDatosTrabajo`
- **Firma documentada:** `GetDatosTrabajo (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetDatosTrabajo"/`
- **Objetivo:** FunciÃ³n mediante la cual se pueden obtener los datos de trabajo del usuario, estos datos son: Ã¡rea de trabajo y empresa de trabajo activa, fecha de trabajo, licencia asignada, perfil definido, entre otros.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/010-GetDatosTrabajo.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/010-GetDatosTrabajo.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/010-GetDatosTrabajo.md)

##### `GetMenuReportesEmpresa`

- **Nombre:** `GetMenuReportesEmpresa`
- **Firma documentada:** `GetMenuReportesEmpresa (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetMenuReportesEmpresa"/`
- **Objetivo:** Función encargada de retornar el listado de informes que tiene disponibles el usuario, este listado incluye los informes propios del tipo de empresa de trabajo que están disponibles por licenciamiento y por los permisos del perfil del usuario activo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/020-GetMenuReportesEmpresa.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/020-GetMenuReportesEmpresa.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/020-GetMenuReportesEmpresa.md)

##### `GetPermisosPorAcciones`

- **Nombre:** `GetPermisosPorAcciones`
- **Firma documentada:** `GetPermisosPorAcciones (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetPermisosPorAcciones"/`
- **Objetivo:** Función que retorna si se tiene o no permiso sobre las acciones del sistema. La seguridad de acciones está definida a nivel de perfil de usuario, es decir, cada perfil tiene la definición de las acciones que puede o no ejecutar. Cada acción del sistema tiene un código definido por defecto, por lo cual si se va a implementar la seguridad de acciones se deben manejar dichos códigos. Para conocer más sobre la seguridad de acciones, consultar su manejo en el sistema ContaPyme o AgroWin.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: acciones; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/030-GetPermisosPorAcciones.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/030-GetPermisosPorAcciones.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/030-GetPermisosPorAcciones.md)

##### `SetDatosTrabajo` **[PELIGROSA — escritura]**

- **Nombre:** `SetDatosTrabajo`
- **Firma documentada:** `SetDatosTrabajo (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"SetDatosTrabajo"/`
- **Objetivo:** Función mediante la cual se pueden modificar los datos de trabajo para un usuario, los datos que se pueden actualizar son: Empresa de trabajo activa, fecha de trabajo, sede y lista de precios por defecto.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: iemp, fechatrabajo, isede, ilistaprecios; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/040-SetDatosTrabajo.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/040-SetDatosTrabajo.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/040-SetDatosTrabajo.md)

##### `GetInfoPorId`

- **Nombre:** `GetInfoPorId`
- **Firma documentada:** `GetInfoPorId (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetInfoPorId"/`
- **Objetivo:** Función encargada de retornar la información solicitada de los campos de un registro, es decir nos permite obtener datos particulares de un elemento del que conocemos su código. Por ejemplo podemos obtener el nombre, apellido, profesión y tratamiento de un tercero cuyo código sea 1053845789. Esta función retorna información de un solo registro.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ntabla, camposderetorno, datosfiltro, init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/050-GetInfoPorId.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/050-GetInfoPorId.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/050-GetInfoPorId.md)

##### `GetEmailFavoritos`

- **Nombre:** `GetEmailFavoritos`
- **Firma documentada:** `GetEmailFavoritos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetEmailFavoritos"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de los correos electrónicos que tiene asociados un usuario del sistema, estos correos se conocen como los email’s favoritos del usuario y es posible configurar uno o más emails favoritos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: nombre, Email
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/060-GetEmailFavoritos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/060-GetEmailFavoritos.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/060-GetEmailFavoritos.md)

##### `SetEmailFavoritos` **[PELIGROSA — escritura]**

- **Nombre:** `SetEmailFavoritos`
- **Firma documentada:** `SetEmailFavoritos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"SetEmailFavoritos"/`
- **Objetivo:** Esta función es la encargada de almacenar en la base de datos los emails favoritos de un usuario del sistema, un usuario puede tener uno o más emails favoritos.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: semailfavoritos, nombre, email; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/070-SetEmailFavoritos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/070-SetEmailFavoritos.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/070-SetEmailFavoritos.md)

##### `DoEnviarEmail` **[PELIGROSA — escritura]**

- **Nombre:** `DoEnviarEmail`
- **Firma documentada:** `DoEnviarEmail (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"DoEnviarEmail"/`
- **Objetivo:** Esta función es la encargada de enviar reportes o documentos en PDF por email. Para poder utilizar esta función es necesario tener en el licenciamiento el módulo de Campañas y alertas tempranas por email. Recibe la información que se enviará por email para que así el Agente se encargue de construir el email y despacharlo al destinatario indicado.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: url, asunto, contenido, narchivo, firma, email; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/080-DoEnviarEmail.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/080-DoEnviarEmail.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/080-DoEnviarEmail.md)

##### `GetSQL`

- **Nombre:** `GetSQL`
- **Firma documentada:** `GetSQL (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetSql"/`
- **Objetivo:** Esta función es la encargada de ejecutar un SQL de tipo “Select” directamente en la base de datos, a través de esta función se puede obtener la información de cualquier tabla del sistema. Para poder ejecutar esta función es necesario tener conocimiento del modelo entidad relación del sistema, es decir, conocer los nombres de las tablas, campos y relaciones entre tablas. Para poder ejecutar esta función se debe contar con un permiso específico, el cual se configura en el sistema ContaPyme entrando por la pestaña Básico – Móvil – Perfiles de seguridad para clientes móviles – Usuario a configurar – API abierta (licencia desarrollador) – Opciones.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: sql; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/090-GetSQL.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/090-GetSQL.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/090-GetSQL.md)

##### `SetSQL` **[PELIGROSA — escritura]**

- **Nombre:** `SetSQL`
- **Firma documentada:** `SetSQL (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"SetSql"/`
- **Objetivo:** Esta función es la encargada de ejecutar un SQL de tipo “Insert”, “Update” o “Delete” directamente sobre la base de datos, a través de esta función se puede crear, modificar o eliminar un registro del sistema. Para poder ejecutar esta función es necesario tener conocimiento del modelo entidad relación del sistema, es decir, conocer los nombres de las tablas, campos y relaciones entre tablas. Para poder ejecutar esta función se debe contar con un permiso específico, el cual se configura en el sistema ContaPyme entrando por la pestaña Básico – Móvil – Perfiles de seguridad para clientes móviles – Usuario a configurar – API abierta (licencia desarrollador) – Opciones.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: sql; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/100-SetSQL.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/100-SetSQL.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/100-SetSQL.md)

##### `MD5`

- **Nombre:** `MD5`
- **Firma documentada:** `MD5 (String) : String`
- **Clase / controlador DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"MD5"/`
- **Objetivo:** Esta funciÃ³n es la encargada de encriptar en MD5 cualquier string que se envÃ­e en el llamado de la funciÃ³n, retornarÃ¡ el string ya encriptado.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[]: string de estado (código|mensaje)
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/110-MD5.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/110-MD5.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/110-MD5.md)

##### `GetListaTitulos`

- **Nombre:** `GetListaTitulos`
- **Firma documentada:** `GetListaTitulos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTitulos`
- **Ruta REST:** `/datasnap/rest/TCatTitulos/"GetListaTitulos"/`
- **Objetivo:** Esta función es la encargada de retornar los valores para las listas de selección del sistema, como por ejemplo: Tipos de unidad de medida, Clases de operaciones, Tipos de documentos de los terceros, entre otros. Estos listados de selección están almacenados en el sistema como tablas virtuales, es decir, tabla de tablas. En una tabla de la base de datos se almacena información de muchas tablas, esto aplica cuando las tablas son pequeñas. Las listas de selección pueden tener código y nombre o pueden contener otros valores adicionales, para conocer la información de cada lista de selección disponible en el sistema, consulte el “Anexo 1” de este documento.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, itabla, ordenarpor, ititulo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/120-GetListaTitulos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/120-GetListaTitulos.md`](010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/120-GetListaTitulos.md)

#### 020_Cat_Terceros

##### `GetListaTerceros`

- **Nombre:** `GetListaTerceros`
- **Firma documentada:** `GetListaTerceros (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetListaTerceros"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de terceros registrados en el sistema. Retornará para cada tercero la información que sea solicitada. Esta función aplica seguridad de datos para retornar el listado de terceros y seguridad de acciones para determinar si se tiene o no acceso al catálogo de terceros. Esta función se debe utilizar cuando se van mostrar los terceros a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/010-GetListaTerceros.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/010-GetListaTerceros.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/010-GetListaTerceros.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de terceros registrados en el sistema, generalmente retorna de cada tercero el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de terceros en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción. Esta función aplica seguridad de datos al momento de retornar el listado de terceros para el selector. Solo retorna los terceros que estén marcados como “Visible en selección”.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, idntercero, ipais, filtroletra, ordenarpor, Init, ntercero; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/020-GetListaSeleccion.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/020-GetListaSeleccion.md)

##### `GetInfoTercero`

- **Nombre:** `GetInfoTercero`
- **Firma documentada:** `GetInfoTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetInfoTercero"/`
- **Objetivo:** Función encargada de retornar la información de un tercero. Existen 3 formas de obtener la información de un tercero:
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/030-GetInfoTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/030-GetInfoTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/030-GetInfoTercero.md)

##### `DoCrearTercero` **[PELIGROSA — escritura]**

- **Nombre:** `DoCrearTercero`
- **Firma documentada:** `DoCrearTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"DoCrearTercero"/`
- **Objetivo:** Función encargada de registrar toda la información de un tercero en la base de datos. Esta función retorna true cuando el tercero se crea satisfactoriamente o false cuando no se puede crear. Esta función recibe en el parámetro “datajson” toda la información que se registrará en la base de datos, es obligatorio que dicha información vaya agrupada por secciones tal y como se explica más adelante.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: init, infobasica, ntercero, napellido, bempresa, itddocum, tratamiento, sprofesion, isexo, fnacimiento, ipais, idep, imun, tdireccion, sbarrio, ttelefono, tcelular, semail, bvisible, sclasiflegal, tipotercero, codigo, base; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/040-DoCrearTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/040-DoCrearTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/040-DoCrearTercero.md)

##### `SetInfoTercero` **[PELIGROSA — escritura]**

- **Nombre:** `SetInfoTercero`
- **Firma documentada:** `SetInfoTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"SetInfoTercero"/`
- **Objetivo:** Esta función es la encargada de modificar la información de un tercero en la base de datos, recibe los datos que se actualizarán agrupados por secciones. Si no se envía una sección el agente la omitirá, pero si se envía una sección vacía la información que haya almacenada en la base de datos se eliminará. Por ejemplo, si se envía la sección “lista de direcciones” así: {"listadirecciones":[]} el sistema eliminará toda la información que haya registrada de la lista de otras direcciones del tercero.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: listacontactos, conceptosnominacontable, entidadesempleado, datosvendedor, lineasproductos, listaeleminvproveedor, listadirecciones, init, infobasica, scargo, tdireccion, sbarrio, ttelefono, tcelular, semail, ilinea, nnombre, napellido, sobservaciones, stratamiento, isexo, iinterno, sprofesion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/050-SetInfoTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/050-SetInfoTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/050-SetInfoTercero.md)

##### `GetConfigCampos`

- **Nombre:** `GetConfigCampos`
- **Firma documentada:** `GetConfigCampos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetConfigCampos"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración para cada campo del catálogo de terceros, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” del catálogo de terceros. Por cada campo retorna: Si es visible, requerido o de solo lectura, valor por defecto, etiqueta, tipo de lista y configuración para las listas.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: valorpordefecto, itdlista, C; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: valorpordefecto, itdlista, C
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/060-GetConfigCampos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/060-GetConfigCampos.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/060-GetConfigCampos.md)

##### `GetExisteTercero`

- **Nombre:** `GetExisteTercero`
- **Firma documentada:** `GetExisteTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetExisteTercero"/`
- **Objetivo:** Esta función es la encargada de validar si un tercero ya existe en la base de datos, recibe el código del tercero a validar y retorna True si el tercero existe o False si el tercero no existe en la base de datos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/070-GetExisteTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/070-GetExisteTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/070-GetExisteTercero.md)

##### `GetValidarNuevoIdTercero`

- **Nombre:** `GetValidarNuevoIdTercero`
- **Firma documentada:** `GetValidarNuevoIdTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetValidarNuevoIdTercero"/`
- **Objetivo:** Esta función es la encargada de validar si el código (init) de un nuevo tercero y su dígito de verificación son correctos, es decir, valida que no contengan caracteres especiales. También valida que el dígito de verificación sea el correspondiente para el código recibido.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: init, digitoverif, bdigitoverificacion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: dvvalido
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/080-GetValidarNuevoIdTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/080-GetValidarNuevoIdTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/080-GetValidarNuevoIdTercero.md)

##### `DoEliminarTercero` **[PELIGROSA — escritura]**

- **Nombre:** `DoEliminarTercero`
- **Firma documentada:** `DoEliminarTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"DoEliminarTercero"/`
- **Objetivo:** Esta función es la encargada de eliminar un tercero de la base de datos, esta función se debe ejecutar en dos pasos, así:
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: init, accion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/090-DoEliminarTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/090-DoEliminarTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/090-DoEliminarTercero.md)

##### `DoRecodificarTercero` **[PELIGROSA — escritura]**

- **Nombre:** `DoRecodificarTercero`
- **Firma documentada:** `DoRecodificarTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"DoRecodificarTercero"/`
- **Objetivo:** Esta función es la encargada de recodificar un tercero, es decir, cambia el código de identificación del tercero tanto en el catálogo de terceros como en las operaciones en las que esté relacionado.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: init, newinit; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: recodificar
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/100-DoRecodificarTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/100-DoRecodificarTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/100-DoRecodificarTercero.md)

##### `DoConsolidarTercero` **[PELIGROSA — escritura]**

- **Nombre:** `DoConsolidarTercero`
- **Firma documentada:** `DoConsolidarTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"DoConsolidarTercero"/`
- **Objetivo:** Esta función es la encargada de consolidar la información de un tercero con otro, para poder llamar la función es necesario conocer el código del tercero origen y el código del tercero destino.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: initorigen, initdestino; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: consolidar
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/110-DoConsolidarTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/110-DoConsolidarTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/110-DoConsolidarTercero.md)

##### `GetAutoLista`

- **Nombre:** `GetAutoLista`
- **Firma documentada:** `GetAutoLista (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetAutoLista"/`
- **Objetivo:** Esta función es la encargada de retornar la lista de valores que tiene registrados un campo en el catálogo de terceros. Por ejemplo, permite obtener las diferentes profesiones o tratamientos registrados en los terceros, los listados para los campos Clase 1, Clase 2, Dato 1, Dato 2, entre otros. Las auto-listas retornan datos que generalmente son presentados en campos de tipo Combobox. Esta función se debe llamar en cada campo que presente un listado de datos; para saber cuáles campos son de tipo auto-lista, la función GetConfigCampos retornará en estos campos el parámetro “itdlista” en 1.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: campo, secccion, filtro; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/120-GetAutoLista.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/120-GetAutoLista.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/120-GetAutoLista.md)

##### `GetFotoTercero`

- **Nombre:** `GetFotoTercero`
- **Firma documentada:** `GetFotoTercero (datajson, controlkey, iapp, random) : TMemoryStream`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetFotoTercero"/`
- **Objetivo:** Esta función es la encargada de retornar la foto que tenga asignada el tercero en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: Init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[]
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/130-GetFotoTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/130-GetFotoTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/130-GetFotoTercero.md)

##### `GetClasificacionSegunPerfil`

- **Nombre:** `GetClasificacionSegunPerfil`
- **Firma documentada:** `GetClasificacionSegunPerfil (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetClasificacionSegunPerfil"/`
- **Objetivo:** Esta función es la encargada de retornar la información de los perfiles de clasificación tributaria que se encuentran registrados en el sistema y que se pueden asignar a un tercero.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, clasificadores
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/140-GetClasificacionSegunPerfil.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/140-GetClasificacionSegunPerfil.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/140-GetClasificacionSegunPerfil.md)

##### `GetClasificacionTributaria`

- **Nombre:** `GetClasificacionTributaria`
- **Firma documentada:** `GetClasificacionTributaria (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetClasificacionTributaria"/`
- **Objetivo:** Esta función es la encargada de retornar la información de los tipos de clasificación tributaria que se pueden asignar a un tercero. Un ejemplo de clasificación tributaria es: Persona natural, persona jurídica, régimen común, régimen simplificado, entre otros.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/150-GetClasificacionTributaria.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/150-GetClasificacionTributaria.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/150-GetClasificacionTributaria.md)

##### `GetOperacionesPorTercero`

- **Nombre:** `GetOperacionesPorTercero`
- **Firma documentada:** `GetOperacionesPorTercero (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetOperacionesPorTercero"/`
- **Objetivo:** Esta función es la encargada de retornar la cantidad de operaciones por cada tipo de documento en las que está relacionado el tercero dado, bien sea como tercero de transacción o como tercero de cartera, se incluyen las operaciones tanto procesadas como no procesadas.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: init, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: itdsop
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/160-GetOperacionesPorTercero.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/160-GetOperacionesPorTercero.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/160-GetOperacionesPorTercero.md)

##### `GetDefDigitoVerif`

- **Nombre:** `GetDefDigitoVerif`
- **Firma documentada:** `GetDefDigitoVerif (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetDefDigitoVerif"/`
- **Objetivo:** Esta función es la encargada de validar si el campo dígito de verificación es visible o no cuando se cree un tercero. El dígito de verificación va asociado al tipo de documento del tercero.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: visible
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/170-GetDefDigitoVerif.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/170-GetDefDigitoVerif.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/170-GetDefDigitoVerif.md)

##### `GetDefEntidades`

- **Nombre:** `GetDefEntidades`
- **Firma documentada:** `GetDefEntidades (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetDefEntidades"/`
- **Objetivo:** Esta función es la encargada de retornar la información de las diferentes entidades a las que puede estar afiliado un tercero de tipo empleado, entidades tales como: EPS, Pensión, Cesantías, ARL, entre otras.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: ialiasentidad, naliasentidad
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/180-GetDefEntidades.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/180-GetDefEntidades.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/180-GetDefEntidades.md)

##### `GetDigitoVerifEsperado`

- **Nombre:** `GetDigitoVerifEsperado`
- **Firma documentada:** `GetDigitoVerifEsperado (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetDigitoVerifEsperado"/`
- **Objetivo:** Esta función es la encargada de calcular y retornar el dígito de verificación para el código de un tercero que se esté creando.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/190-GetDigitoVerifEsperado.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/190-GetDigitoVerifEsperado.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/190-GetDigitoVerifEsperado.md)

##### `GetListaFichas`

- **Nombre:** `GetListaFichas`
- **Firma documentada:** `GetListaFichas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetListaFichas"/`
- **Objetivo:** FunciÃ³n encargada de retornar el cÃ³digo y nombre de las fichas en las que se puede presentar la informaciÃ³n financiera del tercero. Con estas fichas se define la presentaciÃ³n que tendrÃ¡ la informaciÃ³n financiera del tercero en la aplicaciÃ³n ContaPyme mÃ³vil.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/200-GetListaFichas.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/200-GetListaFichas.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/200-GetListaFichas.md)

##### `GetFichaFinanciera`

- **Nombre:** `GetFichaFinanciera`
- **Firma documentada:** `GetFichaFinanciera (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTerceros`
- **Ruta REST:** `/datasnap/rest/TCatTerceros/"GetFichaFinanciera"/`
- **Objetivo:** Esta función es la encargada de retornar la lista de fichas de favoritos financieros que aplican para un tipo de tercero dado. Las fichas de información financiera según tipo de tercero se configuran en ContaPyme / AgroWin – en la pestaña Básico – Opción: Móvil – Opción: Saldos favoritos por terceros Esta función retorna las cuentas que se deben consultar obtener el saldo del favorito financiero.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: inode, itdtercero, fecha; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: itdficha, icuentas, valor
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/210-GetFichafinanciera.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/210-GetFichafinanciera.md`](010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/210-GetFichafinanciera.md)

#### 030_Cat_Operaciones

##### `GetListaOperaciones`

- **Nombre:** `GetListaOperaciones`
- **Firma documentada:** `GetListaOperaciones (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaOperaciones"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de las operaciones registradas en el catálogo de operaciones del sistema, retorna para cada operación la información que sea solicitada. Con esta función es posible obtener todas las operaciones registradas u obtener un tipo de operación en particular, por ejemplo: obtener todos los pedidos o facturas registradas en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, init, cantidadregistros, pagina, camposderetorno, ordenarpor, fsoport, datosfiltro; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/010-GetListaOperaciones.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/010-GetListaOperaciones.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/010-GetListaOperaciones.md)

##### `GetCountOperaciones`

- **Nombre:** `GetCountOperaciones`
- **Firma documentada:** `GetCountOperaciones (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetCountOperaciones"/`
- **Objetivo:** Esta función es la encargada de retornar la cantidad de operaciones que hay registradas en el sistema, agrupadas por tipo de operación.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/020-GetCountOperaciones.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/020-GetCountOperaciones.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/020-GetCountOperaciones.md)

##### `DoExecuteOprAction` **[PELIGROSA — escritura]**

- **Nombre:** `DoExecuteOprAction`
- **Firma documentada:** `DoExecuteOprAction (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/`
- **Objetivo:** Función que permite la ejecución de una acción sobre una o varias operaciones según el caso. Las acciones que se pueden ejecutar son:
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: accion, PROCESS, UNPROCESS, MTOTALAPAGAR, operaciones, itdoper; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/030-DoExecuteOprAction.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/030-DoExecuteOprAction.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/030-DoExecuteOprAction.md)

##### `GetAutoLista`

- **Nombre:** `GetAutoLista`
- **Firma documentada:** `GetAutoLista (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetAutoLista"/`
- **Objetivo:** Esta función es la encargada de retornar la lista de valores que tiene registrados un campo en el catálogo de operaciones. Las auto-listas retornan datos que generalmente son presentados en campos de tipo Combobox. Esta función se debe llamar en cada campo que presente un listado de datos; para saber cuáles campos son de tipo auto-lista, la función “GetConfig” de cada operación, retornará en estos campos el parámetro “itdlista” en 1.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: filtro, campo, seccion, itdoper; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/040-GetAutoLista.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/040-GetAutoLista.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/040-GetAutoLista.md)

##### `GetListaCondicionesAdicionales`

- **Nombre:** `GetListaCondicionesAdicionales`
- **Firma documentada:** `GetListaCondicionesAdicionales (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaCondicionesAdicionales"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de condiciones adicionales que se pueden definir para las operaciones de pedido de un cliente y cotización. Este listado va asociado a las condiciones comerciales de un pedido o una cotización.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/050-GetListaCondicionesAdicionales.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/050-GetListaCondicionesAdicionales.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/050-GetListaCondicionesAdicionales.md)

##### `GetListaFormasEnvio`

- **Nombre:** `GetListaFormasEnvio`
- **Firma documentada:** `GetListaFormasEnvio (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaFormasEnvio"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de las formas de envío que se pueden definir para las operaciones de pedido de un cliente y cotización. Este listado va asociado a las condiciones comerciales de un pedido o una cotización.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/060-GetListaFormasEnvio.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/060-GetListaFormasEnvio.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/060-GetListaFormasEnvio.md)

##### `GetListaFormasPago`

- **Nombre:** `GetListaFormasPago`
- **Firma documentada:** `GetListaFormasPago (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaFormasPago"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de formas de pago que se pueden definir para las operaciones de pedido de un cliente y cotización. Este listado va asociado a las condiciones comerciales de un pedido o una cotización.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/070-GetListaFormasPago.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/070-GetListaFormasPago.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/070-GetListaFormasPago.md)

##### `GetListaSucursales`

- **Nombre:** `GetListaSucursales`
- **Firma documentada:** `GetListaSucursales (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaSucursales"/`
- **Objetivo:** Esta función es la encargada de retornar el código y nombre de las sucursales (otras direcciones) que tenga configuradas el tercero principal de la operación. Esta función se utiliza en la operación de pedido de un cliente.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/080-GetListaSucursales.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/080-GetListaSucursales.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/080-GetListaSucursales.md)

##### `GetValoresVentaProducto`

- **Nombre:** `GetValoresVentaProducto`
- **Firma documentada:** `GetValoresVentaProducto (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetValoresVentaProducto"/`
- **Objetivo:** Esta función es la encargada de retornar el precio de venta de un producto de acuerdo a la bodega de la que se egresará el mismo, la lista de precios que tenga definida y la fecha de soporte de la operación. Adicionalmente, retorna el porcentaje de IVA y el porcentaje de descuento aplicable al producto de acuerdo a la configuración del tercero de la operación. También, retorna el código del centro de costos por defecto al que se debe cargar el valor del ingreso recibido por la orden o venta del producto.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: iinventario, ilistaprecios, iproducto, init, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: Icc
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/090-GetValoresVentaProducto.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/090-GetValoresVentaProducto.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/090-GetValoresVentaProducto.md)

##### `GetValoresVentaCliente`

- **Nombre:** `GetValoresVentaCliente`
- **Firma documentada:** `GetValoresVentaCliente (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetValoresVentaCliente"/`
- **Objetivo:** Esta función es la encargada de retornar el código de la lista de precios que tiene asignada por defecto un tercero de tipo cliente, esto con el fin de poder mostrar en las operaciones los precios de los productos según dicha lista de precios. También retorna el código del vendedor asignado al cliente.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: init, iinventario, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: ilistaprecios
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/100-GetValoresVentaCliente.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/100-GetValoresVentaCliente.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/100-GetValoresVentaCliente.md)

##### `GetOpcionesCuentaFormaCobro`

- **Nombre:** `GetOpcionesCuentaFormaCobro`
- **Firma documentada:** `GetOpcionesCuentaFormaCobro (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetOpcionesCuentaFormaCobro"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración que se debe aplicar a una cuenta cuando se utilice en la forma de cobro de una operación. Estas configuraciones están relacionadas principalmente con el manejo de flujo de efectivo y de conciliación bancaria, pueden ser usadas en las aplicaciones cliente para ocultar o visualizar campos en la forma de cobro por cada medio de pago.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: fsoport, icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/110-GetOpcionesCuentaFormaCobro.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/110-GetOpcionesCuentaFormaCobro.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/110-GetOpcionesCuentaFormaCobro.md)

##### `GetDocumentoOperacion`

- **Nombre:** `GetDocumentoOperacion`
- **Firma documentada:** `GetDocumentoOperacion (datajson, controlkey, iapp, random) : TMemoryStream`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetCountOperaciones"/`
- **Objetivo:** Esta función es la encargada de retornar el documento de impresión en PDF de una operación dada. Para realizar el llamado de esta función es necesario enviar el tipo de documento de impresión en el cual se imprimirá la operación y el número de la operación a imprimir.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/120-GetDocumentoOperacion.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/120-GetDocumentoOperacion.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/120-GetDocumentoOperacion.md)

##### `GetProductosPorReferencia`

- **Nombre:** `GetProductosPorReferencia`
- **Firma documentada:** `GetProductosPorReferencia (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetProductosPorReferencia"/`
- **Objetivo:** Esta función es la encargada de retornar los productos asociados a una referencia dada, permitiendo cargar productos de otra operación en la operación actual. Por ejemplo, estando en un pedido es posible cargar como referencia una cotización para que así no sea necesario volver a registrar todos los productos nuevamente. El manejo de referencias aplica para las operaciones relacionadas con compras y ventas en inventarios.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdoper, fsoport, ireferencia, bsaldos; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: listaproductos, iinventario, qrecurso, qporcdescuento, qporciva, mvrtotal
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/130-GetProductosPorReferencia.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/130-GetProductosPorReferencia.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/130-GetProductosPorReferencia.md)

##### `GetListaClasesOperacion`

- **Nombre:** `GetListaClasesOperacion`
- **Firma documentada:** `GetListaClasesOperacion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaClasesOperacion"/`
- **Objetivo:** Esta función es la encargada de retornar el código y nombre de las clases de operación registradas en el sistema. La clase de operación permite clasificar las operaciones de acuerdo a las necesidades particulares del usuario.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/140-GetListaClasesOperacion.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/140-GetListaClasesOperacion.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/140-GetListaClasesOperacion.md)

##### `GetDiasHabiles`

- **Nombre:** `GetDiasHabiles`
- **Firma documentada:** `GetDiasHabiles (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetDiasHabiles"/`
- **Objetivo:** Esta función es la encargada de calcular y retornar la cantidad de días hábiles que hay entre dos fechas o la fecha hábil de acuerdo a un número de días dados.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: fechainicial, dias, fechafinal; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: dato
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/150-GetDiasHabiles.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/150-GetDiasHabiles.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/150-GetDiasHabiles.md)

##### `GetListaMonedas`

- **Nombre:** `GetListaMonedas`
- **Firma documentada:** `GetListaMonedas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaMonedas"/`
- **Objetivo:** Esta función es la encargada de retornar la información de las monedas configuradas en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: imoneda, nmonedaseleccion, simbolo
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/160-GetListaMonedas.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/160-GetListaMonedas.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/160-GetListaMonedas.md)

##### `GetNumeroDocumento`

- **Nombre:** `GetNumeroDocumento`
- **Firma documentada:** `GetNumeroDocumento (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetNumeroDocumento"/`
- **Objetivo:** Esta función es la encargada de retornar el número de documento de una operación ya formateado según la configuración del tipo de documento de soporte.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: fsoport, inumoper, itdsop, olditdsop, inumsop, oldinumsop, snumsop, oldsnumsop; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: inumsop, snumsop
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/170-GetNumeroDocumento.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/170-GetNumeroDocumento.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/170-GetNumeroDocumento.md)

##### `GetSimboloMoneda`

- **Nombre:** `GetSimboloMoneda`
- **Firma documentada:** `GetSimboloMoneda (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetSimboloMoneda"/`
- **Objetivo:** Esta función es la encargada de retornar el símbolo de una moneda especificada en la petición. Esta función es utilizada para que al momento de mostrar valores en las operaciones éstos se presenten con el símbolo de moneda correspondiente, bien sea la moneda de la operación o la moneda local por defecto.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: imoneda; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: simbolo
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/180-GetSimboloMoneda.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/180-GetSimboloMoneda.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/180-GetSimboloMoneda.md)

##### `GetTasaCambioPorMoneda`

- **Nombre:** `GetTasaCambioPorMoneda`
- **Firma documentada:** `GetTasaCambioPorMoneda (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetTasaCambioPorMoneda"/`
- **Objetivo:** Esta función es la encargada de calcular y retornar la tasa de cambio actual para la moneda que se envía en el llamado de la función.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: imoneda, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/190-GetTasaCambioPorMoneda.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/190-GetTasaCambioPorMoneda.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/190-GetTasaCambioPorMoneda.md)

##### `GetValoresAbonoPorICxX`

- **Nombre:** `GetValoresAbonoPorICxX`
- **Firma documentada:** `GetValoresAbonoPorICxX (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetValoresAbonoPorICxX"/`
- **Objetivo:** Esta función es la encargada de retornar el saldo actual de una cuenta por cobrar o por pagar de un tercero, esto con el fin de mostrar el valor de la deuda y poder registrar el abono. También retorna el código del vendedor que originó la venta (si aplica).
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdmov, icuenta, icxx, initcxx, fsoport, ITDMov; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: msaldo, initvendedor
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/200-GetValoresAbonoPorICxX.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/200-GetValoresAbonoPorICxX.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/200-GetValoresAbonoPorICxX.md)

##### `GetRefCotizaciones`

- **Nombre:** `GetRefCotizaciones`
- **Firma documentada:** `GetRefCotizaciones (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetRefCotizaciones"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de referencias de cotizaciones que tenga registradas un tercero a una fecha determinada. Esto aplica para cargar referencias en las operaciones de cotización al cliente y pedido de un cliente.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: pagina, camposderetorno, ordenarpor, datosfiltro, datospagina, cantidadregistros, ireferencia, init, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/210-GetRefCotizaciones.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/210-GetRefCotizaciones.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/210-GetRefCotizaciones.md)

##### `GetListaSaldosCxC`

- **Nombre:** `GetListaSaldosCxC`
- **Firma documentada:** `GetListaSaldosCxC (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaSaldosCxC"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de saldos de cuentas por cobrar que tiene un tercero. Es utilizada para registrar abonos a cuentas por cobrar.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, camposderetorno, ordenarpor, datosfiltro, cantidadregistros, pagina, icxx, init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/220-GetListaSaldosCxC.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/220-GetListaSaldosCxC.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/220-GetListaSaldosCxC.md)

##### `GetSaldosCuentasPorCobrar`

- **Nombre:** `GetSaldosCuentasPorCobrar`
- **Firma documentada:** `GetSaldosCuentasPorCobrar (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetSaldosCuentasPorCobrar"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de saldos de cuentas por cobrar y sus datos asociados, hasta una fecha determinada.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: bcuentasencero, blocal, fecha, init, bsolovencidas, bdatostercero, bdatoscreacion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/230-GetSaldosCuentasPorCobrar.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/230-GetSaldosCuentasPorCobrar.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/230-GetSaldosCuentasPorCobrar.md)

##### `GetListaSaldosCxP`

- **Nombre:** `GetListaSaldosCxP`
- **Firma documentada:** `GetListaSaldosCxP (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetListaSaldosCxP"/`
- **Objetivo:** Esta funciÃ³n es la encargada de retornar el listado de saldos de cuentas por pagar que se tiene con un tercero. Generalmente es utilizada para realizar abonos a cuentas por pagar, generar listados de pagos a proveedores o consultar cuentas por pagar a vencer.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, camposderetorno, ordenarpor, datosfiltro, cantidadregistros, pagina, icxx, init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/240-GetListaSaldosCxP.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/240-GetListaSaldosCxP.md`](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/240-GetListaSaldosCxP.md)

#### 030_Informes

##### `GetListaTiposImpuestos`

- **Nombre:** `GetListaTiposImpuestos`
- **Firma documentada:** `GetListaTiposImpuestos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetListaTiposImpuesto"/`
- **Objetivo:** Esta función es la encargada de retornar en formato PDF cualquier reporte disponible en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/030_Informes/010-GetPDF.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/030_Informes/010-GetPDF.md`](010_MOD_BASICO/030_Informes/010-GetPDF.md)

##### `GetTiposPapel`

- **Nombre:** `GetTiposPapel`
- **Firma documentada:** `GetTiposPapel (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetTiposPapel"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de tipos de papel que se pueden usar para generar algunos informes del sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, nombre
- **Archivo HTML de origen:** `010_MOD_BASICO/030_Informes/020-GetTiposPapel.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/030_Informes/020-GetTiposPapel.md`](010_MOD_BASICO/030_Informes/020-GetTiposPapel.md)

#### 040_Cat_Empresas

##### `GetListaEmpresas`

- **Nombre:** `GetListaEmpresas`
- **Firma documentada:** `GetListaEmpresas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatEmpresas`
- **Ruta REST:** `/datasnap/rest/TCatEmpresas/"GetListaEmpresas"/`
- **Objetivo:** Función encargada de retornar la información de las empresas de trabajo registradas en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/010-GetListaEmpresas.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/010-GetListaEmpresas.md`](010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/010-GetListaEmpresas.md)

##### `GetListaSedes`

- **Nombre:** `GetListaSedes`
- **Firma documentada:** `GetListaSedes (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatEmpresas`
- **Ruta REST:** `/datasnap/rest/TCatEmpresas/"GetListaSedes"/`
- **Objetivo:** Función encargada de retornar el código y nombre de todas las sedes asociadas a la empresa de trabajo a la que el usuario está conectado.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: isede, nsede
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/020-GetListaSedes.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/020-GetListaSedes.md`](010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/020-GetListaSedes.md)

##### `GetInfoEmpresa`

- **Nombre:** `GetInfoEmpresa`
- **Firma documentada:** `GetInfoEmpresa (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatEmpresas`
- **Ruta REST:** `/datasnap/rest/TCatEmpresas/"GetInfoEmpresa"/`
- **Objetivo:** Esta función es la encargada de retorna la información básica de la empresa de trabajo a la que el usuario se encuentra conectado.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: camposderetorno; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/030-GetInfoEmpresa.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/030-GetInfoEmpresa.md`](010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/030-GetInfoEmpresa.md)

##### `GetLogoEmpresa`

- **Nombre:** `GetLogoEmpresa`
- **Firma documentada:** `GetLogoEmpresa (datajson, controlkey, iapp, random) : TMemoryStream`
- **Clase / controlador DataSnap:** `TCatEmpresas`
- **Ruta REST:** `/datasnap/rest/TCatEmpresas/"GetLogoEmpresa"/`
- **Objetivo:** Esta función es la encargada de retornar el logo que tiene la empresa de trabajo en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/040-GetLogoEmpresa.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/040-GetLogoEmpresa.md`](010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/040-GetLogoEmpresa.md)

#### 050_Cat_CC

##### `GetListaCentrosCostos`

- **Nombre:** `GetListaCentrosCostos`
- **Firma documentada:** `GetListaCentrosCostos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatCentroCostos`
- **Ruta REST:** `/datasnap/rest/TCatCentroCostos/"GetListaCentrosCostos"/`
- **Objetivo:** Esta función es la encargada de retornar la información de todos los centros de costos registrados en el sistema. Este listado se retorna de acuerdo a la empresa de trabajo a la que el usuario esté conectado.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: ncentro
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/050_Cat_CC/010-GetListaCentrosCostos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/050_Cat_CC/010-GetListaCentrosCostos.md`](010_MOD_BASICO/010_Catalogos/050_Cat_CC/010-GetListaCentrosCostos.md)

##### `GetListaClasesCC`

- **Nombre:** `GetListaClasesCC`
- **Firma documentada:** `GetListaClasesCC (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatCentroCostos`
- **Ruta REST:** `/datasnap/rest/TCatCentroCostos/"GetListaClasesCC"/`
- **Objetivo:** Esta función es la encargada de retornar el código y nombre de las clases de centros de costos creados en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, nombre
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/050_Cat_CC/020-GetListaClasesCC.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/050_Cat_CC/020-GetListaClasesCC.md`](010_MOD_BASICO/010_Catalogos/050_Cat_CC/020-GetListaClasesCC.md)

#### 060_Cat_Paises

##### `GetListaPaises`

- **Nombre:** `GetListaPaises`
- **Firma documentada:** `GetListaPaises (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPaises`
- **Ruta REST:** `/datasnap/rest/TCatPaises/"GetListaPaises"/`
- **Objetivo:** Esta función es la encargada de retornar el código y el nombre de todos los países que se encuentren registrados en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/060_Cat_Paises/010-GetListaPaises.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/060_Cat_Paises/010-GetListaPaises.md`](010_MOD_BASICO/010_Catalogos/060_Cat_Paises/010-GetListaPaises.md)

##### `GetListaDepartamentos`

- **Nombre:** `GetListaDepartamentos`
- **Firma documentada:** `GetListaDepartamentos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPaises`
- **Ruta REST:** `/datasnap/rest/TCatPaises/"GetListaDepartamentos"/`
- **Objetivo:** Esta función es la encargada de retornar el código y nombre de todos los departamentos registrados en el sistema para un país.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ipais; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/060_Cat_Paises/020-GetListaDepartamentos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/060_Cat_Paises/020-GetListaDepartamentos.md`](010_MOD_BASICO/010_Catalogos/060_Cat_Paises/020-GetListaDepartamentos.md)

##### `GetListaMunicipios`

- **Nombre:** `GetListaMunicipios`
- **Firma documentada:** `GetListaMunicipios (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPaises`
- **Ruta REST:** `/datasnap/rest/TCatPaises/"GetListaMunicipios"/`
- **Objetivo:** Esta función es la encargada de retornar el código y nombre de los municipios registrados en el sistema para un departamento. Estos municipios se pueden obtener de dos formas: enviando el código del país y el código del departamento o enviando solo el código del país, pues hay municipios asociados a un país pero que no tienen departamento.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ipais, idep; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/060_Cat_Paises/030-GetListaMunicipios.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/060_Cat_Paises/030-GetListaMunicipios.md`](010_MOD_BASICO/010_Catalogos/060_Cat_Paises/030-GetListaMunicipios.md)

#### 070_Tipos_Doc_Sop

##### `GetListaTiposDocSop`

- **Nombre:** `GetListaTiposDocSop`
- **Firma documentada:** `GetListaTiposDocSop (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTiposDocSop`
- **Ruta REST:** `/datasnap/rest/TCatTiposDocSop/"GetListaTiposDocSop"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de tipos de documento de soporte registrados en el sistema. Retornará para cada tipo de documento la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar los tipos de documento de soporte a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, camposderetorno, totalregistros, cantidadregistros, pagina, datosfiltro, ordenarpor, itdsop; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalregistros
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/070_Tipos_Doc_Sop/010-GetListaTiposDocSop.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/070_Tipos_Doc_Sop/010-GetListaTiposDocSop.md`](010_MOD_BASICO/010_Catalogos/070_Tipos_Doc_Sop/010-GetListaTiposDocSop.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTiposDocSop`
- **Ruta REST:** `/datasnap/rest/TCatTiposDocSop/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de tipos de documento de soporte registrados en el sistema, generalmente retorna de cada tipo de documento el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de tipos de documento de soporte en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datosfiltro, ordenarpor, totalpaginas, datospagina, cantidadregistros, pagina, camposderetorno, itdsop; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalpaginas
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/070_Tipos_Doc_Sop/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/070_Tipos_Doc_Sop/020-GetListaSeleccion.md`](010_MOD_BASICO/010_Catalogos/070_Tipos_Doc_Sop/020-GetListaSeleccion.md)

#### 080_Cat_Perfiles

##### `GetListaPerfiles`

- **Nombre:** `GetListaPerfiles`
- **Firma documentada:** `GetListaPerfiles (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPerfiles`
- **Ruta REST:** `/datasnap/rest/TCatPerfiles/"GetListaPerfiles"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de perfiles de cliente o vendedor registrados en el sistema. Retornará para cada perfil la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar los perfiles de cliente a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: totalregistros, datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, itdperfil, ordenarpor, nperfil; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalregistros, datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/080_Cat_Perfiles/010-GetListaPerfiles.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/080_Cat_Perfiles/010-GetListaPerfiles.md`](010_MOD_BASICO/010_Catalogos/080_Cat_Perfiles/010-GetListaPerfiles.md)

#### 090_Cat_Observaciones_oprs

##### `GetListaObservacionesOprs`

- **Nombre:** `GetListaObservacionesOprs`
- **Firma documentada:** `GetListaObservacionesOprs (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatObservacionesOprs`
- **Ruta REST:** `/datasnap/rest/TCatObservacionesOprs/"GetListaObservacionesOprs"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de observaciones para las operaciones registradas en el sistema. Retornará para cada observación la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar las observaciones para las operaciones a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: cantidadregistros, ordenarpor, totalpaginas, datospagina, pagina, camposderetorno, sgrupo, datosfiltro, itdobs; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalpaginas
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/090_Cat_Observaciones_oprs/010-GetListaObservacionesOprs.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/090_Cat_Observaciones_oprs/010-GetListaObservacionesOprs.md`](010_MOD_BASICO/010_Catalogos/090_Cat_Observaciones_oprs/010-GetListaObservacionesOprs.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatObservacionesOprs`
- **Ruta REST:** `/datasnap/rest/TCatObservacionesOprs/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de observaciones para las operaciones registradas en el sistema, generalmente retorna de cada observación el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de observaciones para las operaciones en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: totalregistros, datospagina, cantidadregistros, pagina, camposderetorno, ordenarpor, sgrupo, datosfiltro, itdobs; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalregistros
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/090_Cat_Observaciones_oprs/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/090_Cat_Observaciones_oprs/020-GetListaSeleccion.md`](010_MOD_BASICO/010_Catalogos/090_Cat_Observaciones_oprs/020-GetListaSeleccion.md)

#### 100_Cat_Plan_cuentas

##### `GetListaCuentas`

- **Nombre:** `GetListaCuentas`
- **Firma documentada:** `GetListaCuentas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetListaCuentas"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de cuentas registradas en el sistema. Retornará para cada cuenta la información que sea solicitada. Esta función aplica seguridad de datos para retornar el listado de cuentas y seguridad de acciones para determinar si se tiene o no acceso al catálogo de Plan de cuentas. Esta función se debe utilizar cuando se van mostrar las cuentas a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, camposderetorno, datosfiltro, ordenarpor, pagina, ipadre, icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/010-GetListaCuentas.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/010-GetListaCuentas.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/010-GetListaCuentas.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de cuentas registradas en el sistema, generalmente retorna de cada cuenta el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de cuentas en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción. Esta función aplica seguridad de datos al momento de retornar el listado de cuentas para el selector. Sólo retorna las cuentas que estén marcadas como “Visible en selección”.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: pagina, datosfiltro, datospagina, cantidadregistros, camposderetorno, ipadre, ordenarpor, icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/020-GetListaSeleccion.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/020-GetListaSeleccion.md)

##### `GetInfoCuenta`

- **Nombre:** `GetInfoCuenta`
- **Firma documentada:** `GetInfoCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetInfoCuenta"/`
- **Objetivo:** Función encargada de retornar la información de una cuenta. Existen 3 formas de obtener la información de una cuenta:
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: secciones, icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/030-GetInfoCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/030-GetInfoCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/030-GetInfoCuenta.md)

##### `DoCrearCuenta` **[PELIGROSA — escritura]**

- **Nombre:** `DoCrearCuenta`
- **Firma documentada:** `DoCrearCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"DoCrearCuenta"/`
- **Objetivo:** Función encargada de registrar toda la información de una cuenta en la base de datos. Esta función retorna true cuando la cuenta se crea satisfactoriamente o false cuando no se puede crear. Esta función recibe en el parámetro “datajson” toda la información que se registrará en la base de datos, es obligatorio que dicha información vaya agrupada por secciones tal y como se explica más adelante.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: infobasica, icuenta, blocal, ncuenta, itdcuenta, inivel, iclase, isubclase, bvisible, iexigeterc, bexigeicc, bsedexdefecto, bexigeactivo, bcontrolacxx, bmanejacuotas, bmanejatercero, btemporalanio, bafecdtmte, bajustarxinf, bdisponiblegi, iexigebase, bautoactivar, ipadre, bexigevalor1, bexigevalor2; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/040-DoCrearCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/040-DoCrearCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/040-DoCrearCuenta.md)

##### `SetInfoCuenta` **[PELIGROSA — escritura]**

- **Nombre:** `SetInfoCuenta`
- **Firma documentada:** `SetInfoCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"SetInfoCuenta"/`
- **Objetivo:** Esta función es la encargada de modificar la información de una cuenta en la base de datos, recibe los datos que se actualizarán agrupados por secciones, si no se envía una sección el agente la omitirá, pero si se envía una sección vacía la información que haya almacenada en la base de datos se eliminará. Por ejemplo, si se envía la sección “Conceptos de nómina contable” así: {"conceptosnominacontable":[ ]} el sistema eliminará toda la información que haya registrada de los conceptos de nómina contable asociados a una cuenta.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: icuenta, conceptosnominacontable, infobasica, ncuenta, bmanejatercero, iconcepto, nconcepto, iinterno; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/050-SetInfoCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/050-SetInfoCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/050-SetInfoCuenta.md)

##### `GetConfigCampos`

- **Nombre:** `GetConfigCampos`
- **Firma documentada:** `GetConfigCampos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetConfigCampos"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración para cada campo del catálogo de Plan de cuentas, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” del catálogo de Plan de cuentas. Por cada campo retorna: Si es visible, requerido o de solo lectura, valor por defecto, etiqueta, tipo de lista y configuración para las listas.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: bvisible, blectura, brequerido, etiqueta, valorpordefecto, bmanejoflujoefectivo, bexigeflujoefectivo, bmonedaextranjera, bctrlestrictoconciliacionbanca; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: bvisible, blectura, brequerido, etiqueta, valorpordefecto, cfg, bmanejoflujoefectivo, bexigeflujoefectivo, bmonedaextranjera, bctrlestrictoconciliacionbanca
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/060-GetConfigCampos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/060-GetConfigCampos.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/060-GetConfigCampos.md)

##### `GetExisteCuenta`

- **Nombre:** `GetExisteCuenta`
- **Firma documentada:** `GetExisteCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetExisteCuenta"/`
- **Objetivo:** Esta función es la encargada de validar si una cuenta ya existe en la base de datos, recibe el código de la cuenta a validar y retorna True si la cuenta existe o False si la cuenta no existe en la base de datos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/070-GetExisteCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/070-GetExisteCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/070-GetExisteCuenta.md)

##### `GetValidarNuevoIdCuenta`

- **Nombre:** `GetValidarNuevoIdCuenta`
- **Firma documentada:** `GetValidarNuevoIdCuenta (datajson, controlkey, iapp, random) : Json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetValidarNuevoIdCuenta"/`
- **Objetivo:** Esta función es la encargada de validar si el código (icuenta) de una nueva cuenta es correcto, es decir, valida que no contenga caracteres especiales.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: icuentavalido
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/080-GetValidarNuevoIdCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/080-GetValidarNuevoIdCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/080-GetValidarNuevoIdCuenta.md)

##### `DoEliminarCuenta` **[PELIGROSA — escritura]**

- **Nombre:** `DoEliminarCuenta`
- **Firma documentada:** `DoEliminarCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"DoEliminarCuenta"/`
- **Objetivo:** Esta función es la encargada de eliminar una cuenta de la base de datos, esta función se debe ejecutar en dos pasos, así:
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: icuenta, accion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/090-DoEliminarCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/090-DoEliminarCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/090-DoEliminarCuenta.md)

##### `DoConsolidarCuenta` **[PELIGROSA — escritura]**

- **Nombre:** `DoConsolidarCuenta`
- **Firma documentada:** `DoConsolidarCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"DoConsolidarCuenta"/`
- **Objetivo:** Esta función es la encargada de consolidar la información de una cuenta con otra, para poder llamar la función es necesario conocer el código de la cuenta origen y el código de la cuenta destino.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: icuentaorigen, icuentadestino; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/110-DoConsolidarCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/110-DoConsolidarCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/110-DoConsolidarCuenta.md)

##### `GetListaClases`

- **Nombre:** `GetListaClases`
- **Firma documentada:** `GetListaClases (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetListaClases"/`
- **Objetivo:** Esta función es la encargada de retornar el código y la descripción de cada una de las clases que se pueden asignar a una cuenta. Una cuenta puede ser de clase normal, de efectivo, de impuestos, de ajustes por inflación o de nómina contable.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/120-GetListaClases.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/120-GetListaClases.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/120-GetListaClases.md)

##### `GetListaTiposImpuestos`

- **Nombre:** `GetListaTiposImpuestos`
- **Firma documentada:** `GetListaTiposImpuestos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetListaTiposImpuesto"/`
- **Objetivo:** Esta función es la encargada de retornar el código y la descripción de los tipos de impuestos que se pueden asignar a una cuenta, esto aplica cuando la cuenta de tipo 3 “De impuestos”.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: codigo, descripcion
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/130-GetListaTiposImpuestos.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/130-GetListaTiposImpuestos.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/130-GetListaTiposImpuestos.md)

##### `GetListaTipoCuenta`

- **Nombre:** `GetListaTipoCuenta`
- **Firma documentada:** `GetListaTipoCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetListaTipoCuenta"/`
- **Objetivo:** Esta función es la encargada de retornar el código y nombre de los tipos de cuentas disponibles en el sistema. Una cuenta puede tener uno de los siguientes tipos: Activo, Pasivo, Patrimonio, Ingresos, Egresos, De orden deudora o De orden acreedora.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/140-GetListaTipoCuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/140-GetListaTipoCuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/140-GetListaTipoCuenta.md)

##### `GetMaxNivelDetalle`

- **Nombre:** `GetMaxNivelDetalle`
- **Firma documentada:** `GetMaxNivelDetalle (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetMaxNivelDetalle"/`
- **Objetivo:** Esta función es la encargada de retornar el número máximo de niveles que tiene la estructura del plan de cuentas en el sistema.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: maxnivel
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/150-GetMaxNivelDetalle.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/150-GetMaxNivelDetalle.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/150-GetMaxNivelDetalle.md)

##### `GetIsBalance`

- **Nombre:** `GetIsBalance`
- **Firma documentada:** `GetIsBalance (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetIsBalance"/`
- **Objetivo:** Esta funciÃ³n es la encargada de validar de acuerdo al tipo de cuenta si es de balance o no. Recibe el tipo de cuenta y retorna True si es de balance o false si no lo es.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdcuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: balance
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/160-GetIsBalance.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/160-GetIsBalance.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/160-GetIsBalance.md)

##### `GetIngresoEgresoForICuenta`

- **Nombre:** `GetIngresoEgresoForICuenta`
- **Firma documentada:** `GetIngresoEgresoForICuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetIngresoEgresoForICuenta"/`
- **Objetivo:** Esta función es la encargada de retornar el tipo de concepto de liquidación de una cuenta, para ello es necesario enviar en el llamado de la función el tipo de cuenta. Una cuenta puede tener el tipo de concepto de liquidación en ingreso o en egreso.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdcuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/170-GetIngresoEgresoForICuenta.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/170-GetIngresoEgresoForICuenta.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/170-GetIngresoEgresoForICuenta.md)

##### `GetTipoContabilizacion`

- **Nombre:** `GetTipoContabilizacion`
- **Firma documentada:** `GetTipoContabilizacion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatPlanCuentas`
- **Ruta REST:** `/datasnap/rest/TCatPlanCuentas/"GetTipocontabilizacion"/`
- **Objetivo:** Esta función es la encargada de retornar el tipo de contabilización que tiene configurado el sistema a una fecha determinada. La contabilización puede ser solo LOCAL, solo NIIF o Ambas.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/180-GetTipoContabilizacion.html`
- **Archivo local (Markdown):** [`010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/180-GetTipoContabilizacion.md`](010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/180-GetTipoContabilizacion.md)

### Módulo contabilidad

#### 010_Estados_financieros

##### `GetInfoEstadosFinancieros`

- **Nombre:** `GetInfoEstadosFinancieros`
- **Firma documentada:** `GetInfoEstadosFinancieros (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetInfoEstadosFinancieros"/`
- **Objetivo:** Esta función es la encargada de retornar la información de saldos y movimientos contables de una cuenta o conjunto de cuentas. Retorna datos como: Saldo anterior, movimientos débito y crédito del período consultado, nuevo saldo, porcentaje de variación del saldo, entre otros. Con esta función es posible obtener la información contable de un conjunto de cuentas hijas o solo de las cuentas padre del PUC.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: inode, icuentapadre, fini, itdsaldo, btotalizar; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: icuenta, ncuenta, mdebito, pvariacion, mvarabsoluta, btienehijas
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/010-GetInfoEstadosFinancieros.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/010-GetInfoEstadosFinancieros.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/010-GetInfoEstadosFinancieros.md)

##### `GetSerieEstadosFinancieros`

- **Nombre:** `GetSerieEstadosFinancieros`
- **Firma documentada:** `GetSerieEstadosFinancieros (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetSerieEstadosFinancieros"/`
- **Objetivo:** Esta función es la encargada de retornar el movimiento de una o varias cuentas en una serie de tiempo. La serie de tiempo puede ser: diaria, semanal, mensual, bimestral, trimestral, cuatrimestral, semestral o anual. De acuerdo a la serie de tiempo solicitada, la función retornará los movimientos de la cuenta discriminados por día, semana, mes entre otros.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: inode, icuentas, periodicidad, fini, ffin, bsaldoinicial; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/020-GetSerieEstadosFinancieros.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/020-GetSerieEstadosFinancieros.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/020-GetSerieEstadosFinancieros.md)

##### `GetCategoriasFavoritos`

- **Nombre:** `GetCategoriasFavoritos`
- **Firma documentada:** `GetCategoriasFavoritos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetCategoriasFavoritos"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de categorías en las cuales se pueden agrupar las cuentas favoritas del usuario, es decir, las cuentas que consulta frecuentemente. Una categoría puede ser por ejemplo “Ingresos” y asociar a ella las cuentas de ingresos que el usuario consulta constantemente y así obtener los saldos de las cuentas de manera rápida y efectiva
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: idinternoperfil
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/030-GetCategoriasFavoritos.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/030-GetCategoriasFavoritos.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/030-GetCategoriasFavoritos.md)

##### `SetCuentasFavoritas` **[PELIGROSA — escritura]**

- **Nombre:** `SetCuentasFavoritas`
- **Firma documentada:** `SetCuentasFavoritas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"SetCuentasFavoritas"/`
- **Objetivo:** Esta función es la encargada de registrar las cuentas favoritas de un usuario en la base de datos. Se utiliza tanto para registrar cuentas nuevas como para modificar las cuentas ya asociadas a favoritos.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: idato, ccs, nombre, categoria, cuentas, orden, iclasifop; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/040-SetCuentasFavoritas.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/040-SetCuentasFavoritas.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/040-SetCuentasFavoritas.md)

##### `GetSaldosFavoritosCuentas`

- **Nombre:** `GetSaldosFavoritosCuentas`
- **Firma documentada:** `GetSaldosFavoritosCuentas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetSaldosFavoritosCuentas"/`
- **Objetivo:** Esta función es la encargada de retornar los saldos de las cuentas favoritas asociadas a una categoría.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: categoria, fecha; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: idato, categoria, nombre, orden, iclasifop, cuentas, ccs, naturaleza, itdcuenta, mdebito, mcredito, msaldo
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/050-GetSaldosFavoritosCuentas.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/050-GetSaldosFavoritosCuentas.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/050-GetSaldosFavoritosCuentas.md)

##### `GetFIniInfoContable`

- **Nombre:** `GetFIniInfoContable`
- **Firma documentada:** `GetFIniInfoContable (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetFIniInfoContable"/`
- **Objetivo:** Esta función es la encargada de retornar la fecha de inicio de registro de la información contable de la empresa a la que se encuentra conectado el usuario.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/060-GetFIniInfoContable.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/060-GetFIniInfoContable.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/060-GetFIniInfoContable.md)

##### `GetSaldoCuentas`

- **Nombre:** `GetSaldoCuentas`
- **Firma documentada:** `GetSaldoCuentas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetSaldoCuentas"/`
- **Objetivo:** Función que retorna el saldo total de los movimientos contables realizados para un conjunto de cuentas en centros de costos indicados opcionalmente.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: inode, init, itdopers, cuentas, ccs, itdoper, fecha, css; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/070-GetSaldoCuentas.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/070-GetSaldoCuentas.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/070-GetSaldoCuentas.md)

##### `GetDetalleFicha`

- **Nombre:** `GetDetalleFicha`
- **Firma documentada:** `GetDetalleFicha (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetDetalleFicha"/`
- **Objetivo:** Esta función es la encargada de retornar la información financiera de un tercero (movimientos y saldos) discriminada por periodos y de acuerdo a la cuenta que se envía por parámetro. Esta función es utilizada en la consulta de los favoritos financieros de un tercero de la aplicación ContaPyme móvil.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: inode, init, itdtercero, idato, itdficha, fecha; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: mcredito, pvariacion, mvarabsoluta
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/080-GetDetalleFicha.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/080-GetDetalleFicha.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/080-GetDetalleFicha.md)

##### `GetInfoMovimientosPorCuentas`

- **Nombre:** `GetInfoMovimientosPorCuentas`
- **Firma documentada:** `GetInfoMovimientosPorCuentas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"GetInfoMovimientosPorCuentas"/`
- **Objetivo:** Esta función es la encargada de retornar la información de las operaciones que generaron los movimientos de una cuenta.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: fini, ffin, cuentas, init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: itdoper, fsoport, mvalor, tdetalle, init, ntdsop
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/090-GetInfoMovimientosPorCuentas.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/090-GetInfoMovimientosPorCuentas.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/090-GetInfoMovimientosPorCuentas.md)

##### `SetCategoriaFavorita` **[PELIGROSA — escritura]**

- **Nombre:** `SetCategoriaFavorita`
- **Firma documentada:** `SetCategoriaFavorita (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"SetCategoriaFavorita"/`
- **Objetivo:** Esta función es la encargada de renombrar una categoría de favoritos financieros en la base de datos.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: oldcategoria, newcategoria; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/100-SetCategoriaFavorita.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/100-SetCategoriaFavorita.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/100-SetCategoriaFavorita.md)

##### `DoEliminarFavoritoCuentas` **[PELIGROSA — escritura]**

- **Nombre:** `DoEliminarFavoritoCuentas`
- **Firma documentada:** `DoEliminarFavoritoCuentas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TEstadosFinancieros`
- **Ruta REST:** `/datasnap/rest/TEstadosFinancieros/"DoEliminarFavoritoCuentas"/`
- **Objetivo:** Esta función es la encargada de eliminar una cuenta favorita, es decir la borra de las cuentas favoritas pero sigue como una cuenta del plan de cuentas.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: idato; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/110-DoEliminarFavoritoCuentas.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/110-DoEliminarFavoritoCuentas.md`](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/110-DoEliminarFavoritoCuentas.md)

#### 020_CatConceptosLiquidacion

##### `GetListaConceptosLiquidacion`

- **Nombre:** `GetListaConceptosLiquidacion`
- **Firma documentada:** `GetListaConceptosLiquidacion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatConceptosLiquidacion`
- **Ruta REST:** `/datasnap/rest/TCatConceptosLiquidacion/"GetListaConceptosLiquidacion"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de conceptos de liquidación en ingreso o egreso registrados en el sistema. Si se requiere obtener sólo los conceptos de liquidación en ingreso, se debe enviar en "datosfiltro":{"itdconc":"I"}, si por el contrario se requiere obtener sólo los conceptos de liquidación en egreso, se debe enviar en "datosfiltro":{"itdconc":"E"} Esta función retorna para cada concepto de liquidación la información que sea solicitada.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, itdconc, ordenarpor, iconcepto; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/010-GetListaConceptosLiquidacion.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/010-GetListaConceptosLiquidacion.md`](020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/010-GetListaConceptosLiquidacion.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatConceptosLiquidacion`
- **Ruta REST:** `/datasnap/rest/TCatConceptosLiquidacion/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de conceptos de liquidación en ingreso o egreso registrados en el sistema, generalmente retorna de cada concepto el código y el nombre. Si se requiere obtener sólo los conceptos de liquidación en ingreso, se debe enviar en "datosfiltro":{"itdconc":"I"}, si por el contrario se requiere obtener sólo los conceptos de liquidación en egreso, se debe enviar en "datosfiltro":{"itdconc":"E"} Esta función se debe utilizar cuando se requiera mostrar un listado de conceptos de liquidación en ingreso o egreso en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ordenarpor, totalpaginas, datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, itdconc, iconcepto; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalpaginas, datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/020-GetListaSeleccion.md`](020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/020-GetListaSeleccion.md)

#### 030_CatFlujosEfectivo

##### `GetListaFlujosEfectivo`

- **Nombre:** `GetListaFlujosEfectivo`
- **Firma documentada:** `GetListaFlujosEfectivo (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatFlujosEfectivo`
- **Ruta REST:** `/datasnap/rest/TCatFlujosEfectivo/"GetListaFlujosEfectivo"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de conceptos de flujos de efectivo registrados en el sistema. Retornará para cada concepto la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar los conceptos de flujos de efectivo a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, totalpaginas, ordenarpor, iflujo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalpaginas
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/010-GetListaFlujosEfectivo.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/010-GetListaFlujosEfectivo.md`](020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/010-GetListaFlujosEfectivo.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatFlujosEfectivo`
- **Ruta REST:** `/datasnap/rest/TCatFlujosEfectivo/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de conceptos de flujos de efectivo registrados en el sistema, generalmente retorna de cada concepto el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de conceptos de flujos de efectivo en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, totalpaginas, ordenarpor, iflujo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalpaginas
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/020-GetListaSeleccion.md`](020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/020-GetListaSeleccion.md)

#### 040_CatMonedas

##### `GetListaMonedas`

- **Nombre:** `GetListaMonedas`
- **Firma documentada:** `GetListaMonedas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatMonedas`
- **Ruta REST:** `/datasnap/rest/TCatMonedas/"GetListaMonedas"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de monedas registradas en el sistema. Retornará para cada moneda la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar las monedas a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ordenarpor, datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, imoneda; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/040_CatMonedas/010-GetListaMonedas.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/040_CatMonedas/010-GetListaMonedas.md`](020_CONTABILIDAD/010_Catalogos/040_CatMonedas/010-GetListaMonedas.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatMonedas`
- **Ruta REST:** `/datasnap/rest/TCatMonedas/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de monedas registradas en el sistema, generalmente retorna de cada moneda código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de monedas en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ordenarpor, datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, imoneda; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/040_CatMonedas/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/040_CatMonedas/020-GetListaSeleccion.md`](020_CONTABILIDAD/010_Catalogos/040_CatMonedas/020-GetListaSeleccion.md)

#### 050_CatTiposMovBancarios

##### `GetListaTiposMovBancarios`

- **Nombre:** `GetListaTiposMovBancarios`
- **Firma documentada:** `GetListaTiposMovBancarios (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTiposMovBancarios`
- **Ruta REST:** `/datasnap/rest/TCatTiposMovBancarios/"GetListaTiposMovBancarios"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de tipos de movimientos bancarios registrados en el sistema. Retornará para cada tipo de movimiento la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar los tipos de movimientos bancarios a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ordenarpor, datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, ntdmovimiento; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/010-GetListaTiposMovBancarios.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/010-GetListaTiposMovBancarios.md`](020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/010-GetListaTiposMovBancarios.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatTiposMovBancarios`
- **Ruta REST:** `/datasnap/rest/TCatTiposMovBancarios/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de tipos de movimientos bancarios registrados en el sistema, generalmente retorna de cada tipo de movimiento código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de tipos de movimientos bancarios en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: ordenarpor, datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, ntdmovimiento; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/020-GetListaSeleccion.md`](020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/020-GetListaSeleccion.md)

### Módulo cartera y proveedores

#### 020_Operaciones

##### `GetConfigOprMOV4`

- **Nombre:** `GetConfigOprMOV4`
- **Firma documentada:** `GetConfigOprMOV4 (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetConfigOprMOV4"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración de los campos de la operación de Abono a cuenta por cobrar (CxC), dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” de dicha operación.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdsop, biclaseoprro, ncampofiltro, beditable, icc, iflujoefec, itipomovcaja, cxc, icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: biclaseoprro, ncampofiltro, beditable, datosprincipales, formacobro, icc, iflujoefec, itipomovcaja, cxc, icuenta
- **Archivo HTML de origen:** `030_CARTERA/020_Operaciones/010-GetConfigOprMOV4.html`
- **Archivo local (Markdown):** [`030_CARTERA/020_Operaciones/010-GetConfigOprMOV4.md`](030_CARTERA/020_Operaciones/010-GetConfigOprMOV4.md)

### Módulo inventarios

#### 010_ElemInv

##### `GetListaElemInv`

- **Nombre:** `GetListaElemInv`
- **Firma documentada:** `GetListaElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListaElemInv"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de elementos de inventario registrados en el sistema. Esta función retorna para cada elemento de inventario la información que sea solicitada, se debe tener en cuenta que el Agente retornará solo los elementos de inventario que tengan activa la opción de “Visible en internet (programa Agente)”.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, camposderetorno, datosfiltro, Idnrecurso, ilistapreciosdef, filtroletra, ordenarpor, totalpaginas, totalregistros, pagina, nrecurso, ibodega, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalpaginas, totalregistros
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/010-GetListaElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/010-GetListaElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/010-GetListaElemInv.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de elementos de inventario registrados en el sistema, generalmente retorna de cada elemento el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de elementos de inventario en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción. Esta función aplica seguridad de datos al momento de retornar el listado de elementos para el selector. Solo retorna los elementos de inventario que estén marcados como “Visible en selección” y “Visible en internet (programa Agente)”.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, camposderetorno, datosfiltro, Idnrecurso, ilistapreciosdef, filtroletra, ordenarpor, totalpaginas, totalregistros, pagina, bproducto, nrecurso; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: totalpaginas, totalregistros
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/020-GetListaSeleccion.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/020-GetListaSeleccion.md)

##### `GetInfoElemInv`

- **Nombre:** `GetInfoElemInv`
- **Firma documentada:** `GetInfoElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetInfoElemInv"/`
- **Objetivo:** Función encargada de retornar la información de un elemento de inventario. Existen 3 formas de obtener la información de un elemento:
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/030-GetInfoElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/030-GetInfoElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/030-GetInfoElemInv.md)

##### `DoCrearElemInv` **[PELIGROSA — escritura]**

- **Nombre:** `DoCrearElemInv`
- **Firma documentada:** `DoCrearElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"DoCrearElemInv"/`
- **Objetivo:** Función encargada de registrar toda la información de un elemento de inventario en la base de datos. Esta función retorna true cuando el elemento se crea satisfactoriamente o false cuando no se puede crear. Esta función recibe en el parámetro “datajson” toda la información que se registrará en la base de datos, es obligatorio que dicha información vaya agrupada por secciones tal y como se explica más adelante.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: listaproductosequivalentes, irecurso, infobasica, nrecurso, bvisible, bvisibleinternet, igrupoinv, idepinv, smarca, bcompuesto, parteseleminv, iinterno, irecursodet, qcant, stockymargen, qstockmin, qstockmax, pmargen1, pmargen2, pmargen3, subicacion, listaprecios, isede, ilista, imetodo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/040-DoCrearElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/040-DoCrearElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/040-DoCrearElemInv.md)

##### `SetInfoElemInv` **[PELIGROSA — escritura]**

- **Nombre:** `SetInfoElemInv`
- **Firma documentada:** `SetInfoElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"SetInfoElemInv"/`
- **Objetivo:** Esta función es la encargada de modificar la información de un elemento de inventario en la base de datos, recibe los datos que se actualizarán agrupados por secciones, si no se envía una sección el agente la omitirá, pero si se envía una sección vacía la información que haya almacenada en la base de datos se eliminará. Por ejemplo, si se envía la sección “lista de precios” así: {"listaprecios":[ ]} el sistema eliminará toda la información que haya registrada de las listas de precios que tenga asignadas el elemento de inventario.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: parteseleminv, stockymargen, irecurso, infobasica, sreffabricante, listaprecios, iinterno, iemp, isede, ilista, imetodo, mprecio, fasignacion, fvigenciahasta, listaproductosequivalentes, nrecurso, nunidad, irecursoequivalente; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/050-SetInfoElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/050-SetInfoElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/050-SetInfoElemInv.md)

##### `GetConfigCampos`

- **Nombre:** `GetConfigCampos`
- **Firma documentada:** `GetConfigCampos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetConfigCampos"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración para cada campo del catálogo de elementos de inventarios, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” del catálogo de elementos de inventario. Por cada campo retorna: Si es visible, requerido o de solo lectura, valor por defecto, etiqueta, tipo de lista y configuración para las listas.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: bvisible, blectura, brequerido, etiqueta, valorpordefecto, Autolistacatalogo, ncampofiltro, ncatalogo, beditable, bpersonalizarcuentasmanejo, bpersonalizarcuentainventario, bcalcimpfromcuentaing, bpermitirelemcompuestos, qprecisionelemcompuesto, bmanejoalias, bpermitiralquilerelemcontrol, bundcompra, binventarioestricto, bundventa, qprecisioncosto, itipocostosdefmargenneg, ilistapreciosdefmargenneg, ilistapreciosdefault, itdlistaprecios; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos; campos documentados: bvisible, blectura, brequerido, etiqueta, valorpordefecto, Autolistacatalogo, ncampofiltro, ncatalogo, beditable, bpersonalizarcuentasmanejo, bpersonalizarcuentainventario, bcalcimpfromcuentaing, bpermitirelemcompuestos, qprecisionelemcompuesto, bmanejoalias, bpermitiralquilerelemcontrol, bundcompra, binventarioestricto, bundventa, qprecisioncosto
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/060-GetConfigCampos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/060-GetConfigCampos.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/060-GetConfigCampos.md)

##### `GetFotoTercero`

- **Nombre:** `GetFotoTercero`
- **Firma documentada:** `GetFotoTercero (datajson, controlkey, iapp, random) : TMemoryStream`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetFotoElemInv"/`
- **Objetivo:** Esta función es la encargada de retornar la imagen que tenga asignada un elemento de inventario en el sistema. Un elemento de inventario puede tener asignadas varias imágenes, por lo cual es necesario enviar el código de la imagen que se desea obtener.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: Irecurso, Codimg; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[]
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/070-GetFotoElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/070-GetFotoElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/070-GetFotoElemInv.md)

##### `GetExisteElemInv`

- **Nombre:** `GetExisteElemInv`
- **Firma documentada:** `GetExisteElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetExisteElemInv"/`
- **Objetivo:** Esta función es la encargada de validar si un elemento de inventario ya existe en la base de datos, recibe el código del elemento de inventario a validar y retorna True si el elemento existe o false si el elemento no existe en la base de datos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/080-GetExisteElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/080-GetExisteElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/080-GetExisteElemInv.md)

##### `GetValidarNuevoIdElemInv`

- **Nombre:** `GetValidarNuevoIdElemInv`
- **Firma documentada:** `GetValidarNuevoIdElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetValidarNuevoIdElemInv"/`
- **Objetivo:** Esta función es la encargada de validar si el código (irecurso) de un nuevo elemento de inventario es correcto, valida que dicho código no contenga caracteres especiales.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/090-GetValidarNuevoIdElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/090-GetValidarNuevoIdElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/090-GetValidarNuevoIdElemInv.md)

##### `DoEliminarElemInv` **[PELIGROSA — escritura]**

- **Nombre:** `DoEliminarElemInv`
- **Firma documentada:** `DoEliminarElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"DoEliminarElemInv"/`
- **Objetivo:** Esta función es la encargada de eliminar un elemento de inventario de la base de datos, esta función se debe ejecutar en dos pasos, así:
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso, accion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/100-DoEliminarElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/100-DoEliminarElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/100-DoEliminarElemInv.md)

##### `DoConsolidarElemInv` **[PELIGROSA — escritura]**

- **Nombre:** `DoConsolidarElemInv`
- **Firma documentada:** `DoConsolidarElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"DoConsolidarElemInv"/`
- **Objetivo:** Esta función es la encargada de consolidar la información de un elemento de inventario con otro, para poder llamar la función es necesario conocer el código del elemento origen y el código del elemento destino.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: irecursoorigen, irecursodestino; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/110-DoConsolidarElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/110-DoConsolidarElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/110-DoConsolidarElemInv.md)

##### `DoRecodificarElemInv` **[PELIGROSA — escritura]**

- **Nombre:** `DoRecodificarElemInv`
- **Firma documentada:** `DoRecodificarElemInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"DoRecodificarElemInv"/`
- **Objetivo:** Esta función es la encargada de recodificar un elemento de inventario, es decir, cambia el código de identificación del elemento tanto en el catálogo de elementos de inventario como en las operaciones en las que esté relacionado.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso, newirecurso; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/120-DoRecodificarElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/120-DoRecodificarElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/120-DoRecodificarElemInv.md)

##### `GetAutoLista`

- **Nombre:** `GetAutoLista`
- **Firma documentada:** `GetAutoLista (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetAutoLista"/`
- **Objetivo:** Esta función es la encargada de retornar la lista de valores que tiene registrados un campo en el catálogo de elementos de inventario. Por ejemplo, permite obtener las diferentes unidades de medida registradas para los elementos de inventario, los listados para los campos Clase 1, Clase 2, Dato 1, Dato 2, entre otros. Las auto-listas retornan datos que generalmente son presentados en campos de tipo Combobox. Esta función se debe llamar en cada campo que presente un listado de datos; para saber cuáles campos son de tipo auto-lista, la función GetConfigCampos retornará en estos campos el parámetro “itdlista” en 1.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: campo, seccion, filtro, secccion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/130-GetAutoLista.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/130-GetAutoLista.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/130-GetAutoLista.md)

##### `GetListItdCotizarCompuesto`

- **Nombre:** `GetListItdCotizarCompuesto`
- **Firma documentada:** `GetListItdCotizarCompuesto (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListItdCotizarCompuesto"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de acciones que puede realizar el sistema al momento de imprimir la cotización de un elemento compuesto. Cuando un elemento de inventario es marcado como “compuesto” se debe elegir del listado de acciones que retorna esta función, la acción que el sistema debe realizar al imprimir la cotización de un elemento compuesto.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/140-GetListItdCotizarCompuesto.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/140-GetListItdCotizarCompuesto.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/140-GetListItdCotizarCompuesto.md)

##### `GetListItdDescarga`

- **Nombre:** `GetListItdDescarga`
- **Firma documentada:** `GetListItdDescarga (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListItdDescarga"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de comportamientos que tendrán las partes de un elemento compuesto ante las acciones que se realicen con él en el sistema. Cuando un elemento es marcado como “compuesto” se debe elegir de la lista que retorna esta función, el comportamiento que tendrán las partes del elemento cuando se venda o se consuma.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: bcontrolinv, bconsumo, bventa, bproducto, bservicio; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/150-GetListItdDescarga.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/150-GetListItdDescarga.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/150-GetListItdDescarga.md)

##### `GetListItdFacturarCompuesto`

- **Nombre:** `GetListItdFacturarCompuesto`
- **Firma documentada:** `GetListItdFacturarCompuesto (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListItdFacturarCompuesto"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de acciones que puede realizar el sistema al momento de imprimir la factura de un elemento compuesto. Cuando un elemento de inventario es marcado como “compuesto” se debe elegir del listado de acciones que retorna esta función, la acción que el sistema debe realizar al imprimir la factura de un elemento compuesto.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/160-GetListItdFacturarCompuesto.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/160-GetListItdFacturarCompuesto.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/160-GetListItdFacturarCompuesto.md)

##### `GetListAfectaCuenta`

- **Nombre:** `GetListAfectaCuenta`
- **Firma documentada:** `GetListAfectaCuenta (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListAfectaCuenta"/`
- **Objetivo:** Esta función es la encargada de retornar la lista de opciones de afectación de cuentas que se pueden manejar en elementos de inventario compuestos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/170-GetListAfectaCuenta.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/170-GetListAfectaCuenta.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/170-GetListAfectaCuenta.md)

##### `GetListTiempoAlquilerElemInv`

- **Nombre:** `GetListTiempoAlquilerElemInv`
- **Firma documentada:** `GetListTiempoAlquilerElemInv (datajson, controlkey, iapp, random) : jsons`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListTiempoAlquilerElemInv"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de opciones configuradas en el sistema para realizar el cálculo de tiempo cuando se alquila un elemento de inventario. Esta función se debe llamar cuando el elemento de inventario esté marcado como “Es elemento de alquiler”. Dentro de las opciones disponibles se encuentran: Calcular el tiempo del alquiler en minutos, en horas o por noches.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/180-GetListTiempoAlquilerElemInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/180-GetListTiempoAlquilerElemInv.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/180-GetListTiempoAlquilerElemInv.md)

##### `GetListaPreciosUsuario`

- **Nombre:** `GetListaPreciosUsuario`
- **Firma documentada:** `GetListaPreciosUsuario (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetListaPreciosUsuario"/`
- **Objetivo:** Esta función es la encargada de retornar las listas de precios que tenga asignadas el usuario en el sistema. Un usuario puede tener una lista de precios por defecto y varias listas de precios permitidas. Si el usuario no tiene listas de precios permitidas esta función solo retornará la lista de precios que tenga por defecto, pero si el usuario no tiene definida lista de precios por defecto ni listas de precios permitidas, esta función retornará todas las listas de precios registradas en el sistema (asumirá que el usuario tiene acceso a todas).
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/190-GetListaPreciosUsuario.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/190-GetListaPreciosUsuario.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/190-GetListaPreciosUsuario.md)

##### `GetPrecioCalculado`

- **Nombre:** `GetPrecioCalculado`
- **Firma documentada:** `GetPrecioCalculado (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetPrecioCalculado"/`
- **Objetivo:** Esta función es la encargada de calcular y retornar el precio de un elemento de inventario. El cálculo de dicho precio está dado por el método de cálculo definido para el elemento, es decir, al elemento de inventario se le pueden agregar varias listas de precios y en ellas el precio del elemento puede ser calculado por el sistema dependiendo del método definido. El único método que no calcula el precio del elemento es el “Manual asignado por el usuario”,
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso, imetodo, ilista; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/010_ElemInv/200-GetPrecioCalculado.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/010_ElemInv/200-GetPrecioCalculado.md`](040_INVENTARIOS/010_Catalogos/010_ElemInv/200-GetPrecioCalculado.md)

#### 020_GruposInv

##### `GetListaGrupoInv`

- **Nombre:** `GetListaGrupoInv`
- **Firma documentada:** `GetListaGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"GetListaGrupoInv"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de grupos de inventario registrados en el sistema. Esta función retorna para cada grupo de inventario la información que sea solicitada.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, ordenarpor, ngrupo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/010-GetListaGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/010-GetListaGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/010-GetListaGrupoInv.md)

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de grupos de inventario registrados en el sistema, generalmente retorna de cada grupo el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de grupos de inventario en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, ordenarpor, ngrupo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/020-GetListaSeleccion.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/020-GetListaSeleccion.md)

##### `GetInfoGrupoInv`

- **Nombre:** `GetInfoGrupoInv`
- **Firma documentada:** `GetInfoGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"GetInfoGrupoInv"/`
- **Objetivo:** Función encargada de retornar la información de un grupo de inventario.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: igrupoinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/030-GetInfoGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/030-GetInfoGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/030-GetInfoGrupoInv.md)

##### `DoCrearGrupoInv` **[PELIGROSA — escritura]**

- **Nombre:** `DoCrearGrupoInv`
- **Firma documentada:** `DoCrearGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"DoCrearGrupoInv"/`
- **Objetivo:** Función encargada de registrar toda la información de un grupo de inventario en la base de datos. Esta función retorna true cuando el grupo se crea satisfactoriamente o false cuando no se puede crear. Esta función recibe en el parámetro “datajson” toda la información que se registrará en la base de datos, es obligatorio que dicha información vaya agrupada por secciones tal y como se explica más adelante.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: igrupoinv, infobasica, bconsumo, bcontrolinv, bproducto, bservicio, bventa, ngrupo, icuentavta, bicuentasporclaseing, icuentacostos, icuentadeterioro, icuentaingreversiondet, iconceptocompra1, iconceptocompra2, iconceptocompra3, iconceptocompra5, iconceptocompra4, iconceptocompra6, bivamayorvalor, listacuentasingresos, iinterno, itdcc, icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/040-DoCrearGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/040-DoCrearGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/040-DoCrearGrupoInv.md)

##### `SetInfoGrupoInv` **[PELIGROSA — escritura]**

- **Nombre:** `SetInfoGrupoInv`
- **Firma documentada:** `SetInfoGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"SetInfoGrupoInv"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de grupos de inventario registrados en el sistema. Esta función retorna para cada grupo de inventario la información que sea solicitada.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: igrupoinv, infobasica, bconsumo, icuentaegr, bicuentasporclaseegr, listacuentasegresos, iinterno, itdcc, icuenta; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/050-SetInfoGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/050-SetInfoGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/050-SetInfoGrupoInv.md)

##### `GetConfigCampos`

- **Nombre:** `GetConfigCampos`
- **Firma documentada:** `GetConfigCampos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"GetConfigCampos"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración que tienen asignada los campos de impuestos, descuentos o cargos que se solicitan tanto para los impuestos en compras como los impuestos en ventas de la edición de un grupo de inventario. Por cada campo retorna: Si es visible, requerido o de solo lectura y etiqueta; las etiquetas pueden ser definidas libremente por el usuario en la configuración del catálogo de conceptos de liquidación.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/060-GetConfigCampos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/060-GetConfigCampos.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/060-GetConfigCampos.md)

##### `GetExisteGrupoInv`

- **Nombre:** `GetExisteGrupoInv`
- **Firma documentada:** `GetExisteGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"GetExisteGrupoInv"/`
- **Objetivo:** Esta función es la encargada de validar si un grupo de inventario ya existe en la base de datos, recibe el código del grupo de inventario a validar y retorna True si el grupo existe o false si el grupo no existe en la base de datos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: igrupoinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/070-GetExisteGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/070-GetExisteGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/070-GetExisteGrupoInv.md)

##### `GetValidarNuevoIdGrupoInv`

- **Nombre:** `GetValidarNuevoIdGrupoInv`
- **Firma documentada:** `GetValidarNuevoIdGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"GetValidarNuevoIdGrupoInv"/`
- **Objetivo:** Esta función es la encargada de validar si el código (igrupoinv) de un nuevo grupo de inventario es correcto, valida que dicho código no contenga caracteres especiales.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: igrupoinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/080-GetValidarNuevoIdGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/080-GetValidarNuevoIdGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/080-GetValidarNuevoIdGrupoInv.md)

##### `DoEliminarGrupoInv` **[PELIGROSA — escritura]**

- **Nombre:** `DoEliminarGrupoInv`
- **Firma documentada:** `DoEliminarGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"DoEliminarGrupoInv"/`
- **Objetivo:** Esta función es la encargada de eliminar un grupo de inventario de la base de datos, esta función se debe ejecutar en dos pasos, así:
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: igrupoinv, accion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/090-DoEliminarGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/090-DoEliminarGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/090-DoEliminarGrupoInv.md)

##### `DoRecodificarGrupoInv` **[PELIGROSA — escritura]**

- **Nombre:** `DoRecodificarGrupoInv`
- **Firma documentada:** `DoRecodificarGrupoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"DoRecodificarGrupoInv"/`
- **Objetivo:** Esta función es la encargada de recodificar un grupo de inventario, es decir, cambia el código de identificación del grupo tanto en el catálogo de grupos de inventario como en las operaciones en las que esté relacionado.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: igrupoinv, newigrupoinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/100-DoRecodificarGrupoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/100-DoRecodificarGrupoInv.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/100-DoRecodificarGrupoInv.md)

##### `GetInfoVisible`

- **Nombre:** `GetInfoVisible`
- **Firma documentada:** `GetInfoVisible (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatGrupoInv`
- **Ruta REST:** `/datasnap/rest/TCatGrupoInv/"GetInfoVisible"/`
- **Objetivo:** En la edición de un grupo de inventario la información está agrupada por pasos y dichos pasos son visibles de acuerdo a la configuración que tenga asignada el grupo de inventario, esta función se encarga de analizar y retornar los pasos visibles en la edición del grupo de inventario.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: bcontrolinv, bconsumo, bventa, bproducto, bservicio; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/020_GruposInv/110-GetInfoVisible.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/020_GruposInv/110-GetInfoVisible.md`](040_INVENTARIOS/010_Catalogos/020_GruposInv/110-GetInfoVisible.md)

#### 025_Movimientos

##### `GetListaMovInventario`

- **Nombre:** `GetListaMovInventario`
- **Firma documentada:** `GetListaMovInventario (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TInventarios`
- **Ruta REST:** `/datasnap/rest/TInventarios/"GetListaMovInventario"/`
- **Objetivo:** Esta función es la encargada de retornar la información de los movimientos de inventarios registrados en el sistema. Retornará por cada movimiento la información que sea solicitada. Esta función aplica seguridad de datos ya que pueden haber terceros, elementos de inventario y centros de costos que el usuario no pueda visualizar. Esta función se debe utilizar cuando se quieren obtener los movimientos de inventarios contables como compras, ventas, ajustes de inventarios entre otros. No incluye los movimientos de inventarios plus, como: Pedidos, Remisiones, Recepción de materiales, órdenes de compra, entre otros.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, sql, ordenarpor, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/025_Movimientos/010-GetListaMovInventario.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/025_Movimientos/010-GetListaMovInventario.md`](040_INVENTARIOS/025_Movimientos/010-GetListaMovInventario.md)

##### `GetSaldoFisicoProductoEnBodegas`

- **Nombre:** `GetSaldoFisicoProductoEnBodegas`
- **Firma documentada:** `GetSaldoFisicoProductoEnBodegas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TInventarios`
- **Ruta REST:** `/datasnap/rest/TInventarios/"GetSaldoFisicoProductoEnBodegas"/`
- **Objetivo:** Retorna un json con la lista de bodegas y cantidad del elemento, donde éste tiene saldo físico (Saldo contable + Recepciones - Remisiones).
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso, iinventario; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/025_Movimientos/020_GetSaldoFisicoProductoEnBodegas.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/025_Movimientos/020_GetSaldoFisicoProductoEnBodegas.md`](040_INVENTARIOS/025_Movimientos/020_GetSaldoFisicoProductoEnBodegas.md)

##### `GetListaElementosMovsPeriodo`

- **Nombre:** `GetListaElementosMovsPeriodo`
- **Firma documentada:** `GetListaElementosMovsPeriodo (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TInventarios`
- **Ruta REST:** `/datasnap/rest/TInventarios/"GetListaElementosMovsPeriodo"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de productos que han sido movidos en un periodo dado, es decir, productos que han sido vendidos, comprados, trasladados entre otros.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: iinventario, fini, ffin, init, itdmov; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/025_Movimientos/030_GetListaElementosMovsPeriodo.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/025_Movimientos/030_GetListaElementosMovsPeriodo.md`](040_INVENTARIOS/025_Movimientos/030_GetListaElementosMovsPeriodo.md)

##### `GetMovimientosProducto`

- **Nombre:** `GetMovimientosProducto`
- **Firma documentada:** `GetMovimientosProducto (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TInventarios`
- **Ruta REST:** `/datasnap/rest/TInventarios/"GetMovimientosProducto"/`
- **Objetivo:** Esta función es la encargada de retornar el listado con los movimientos de un tipo particular para un producto dado en un periodo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso, itdmov; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/025_Movimientos/040_GetMovimientosProducto.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/025_Movimientos/040_GetMovimientosProducto.md`](040_INVENTARIOS/025_Movimientos/040_GetMovimientosProducto.md)

##### `GetListaUltPrecioCompraProducto`

- **Nombre:** `GetListaUltPrecioCompraProducto`
- **Firma documentada:** `GetListaUltPrecioCompraProducto (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TInventarios`
- **Ruta REST:** `/datasnap/rest/TInventarios/"GetListaUltPrecioCompraProducto"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de los últimos precios de compra para un producto por cada bodega y proveedor.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso, init; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/025_Movimientos/050_GetListaUltPrecioCompraProducto.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/025_Movimientos/050_GetListaUltPrecioCompraProducto.md`](040_INVENTARIOS/025_Movimientos/050_GetListaUltPrecioCompraProducto.md)

##### `GetSaldosProductosEnBodegas`

- **Nombre:** `GetSaldosProductosEnBodegas`
- **Firma documentada:** `GetSaldosProductosEnBodegas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatElemInv`
- **Ruta REST:** `/datasnap/rest/TCatElemInv/"GetSaldosProductosEnBodegas"/`
- **Objetivo:** Retorna un json con el sado fÃ­sico, contable y proyectado de los productos en cada bodega, esto siempre y cuando se indique quÃ© tipo de saldo se desea obtener, si no se envÃ­a ningÃºn tipo de saldo, retornarÃ¡ por defecto el saldo fÃ­sico del producto. EntiÃ©ndase saldo fÃ­sico como: Saldo contable + Recepciones - Remisiones. Se puede solicitar el saldo para un producto especÃ­fico o para todos los productos. Cuando el producto no tenga saldo no se retornarÃ¡ nada en el json de respuesta.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: irecurso; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/025_Movimientos/060_GetSaldosProductosEnBodegas.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/025_Movimientos/060_GetSaldosProductosEnBodegas.md`](040_INVENTARIOS/025_Movimientos/060_GetSaldosProductosEnBodegas.md)

#### 030_LineasProductos

##### `GetListaLineasProductos`

- **Nombre:** `GetListaLineasProductos`
- **Firma documentada:** `GetListaLineasProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"GetListaLineasProductos"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de líneas de productos registradas en el sistema. Esta función retorna para cada línea de productos la información que sea solicitada.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, ordenarpor, ndepinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/010-GetListaLineasProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/010-GetListaLineasProductos.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/010-GetListaLineasProductos.md)

##### `GetListaLineasSeleccion`

- **Nombre:** `GetListaLineasSeleccion`
- **Firma documentada:** `GetListaLineasSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de líneas de productos registradas en el sistema, generalmente retorna de cada línea el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de líneas de productos en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, ordenarpor, ndepinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/020-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/020-GetListaSeleccion.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/020-GetListaSeleccion.md)

##### `DoCrearLineaProductos` **[PELIGROSA — escritura]**

- **Nombre:** `DoCrearLineaProductos`
- **Firma documentada:** `DoCrearLineaProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"DoCrearLineaProductos"/`
- **Objetivo:** Función encargada de registrar toda la información de una línea de productos en la base de datos. Esta función retorna true cuando la línea se crea satisfactoriamente o false cuando no se puede crear. Esta función recibe en el parámetro “datajson” toda la información que se registrará en la base de datos, es obligatorio que dicha información vaya agrupada por secciones tal y como se explica más adelante.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: idepinv, infobasica, ndepinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/040-DoCrearLineaProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/040-DoCrearLineaProductos.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/040-DoCrearLineaProductos.md)

##### `SetInfoLineaProductos` **[PELIGROSA — escritura]**

- **Nombre:** `SetInfoLineaProductos`
- **Firma documentada:** `SetInfoLineaProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"SetInfoLineaProductos"/`
- **Objetivo:** Esta función es la encargada de modificar la información de una línea de productos en la base de datos, recibe los datos que se actualizarán agrupados en la sección “infobasica”. En esta función el único dato que se puede modificar es el nombre de la línea de productos, pues los demás son campos de auditoría que el sistema asigna automáticamente.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: idepinv, infobasica, ndepinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/050-SetInfoLineaProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/050-SetInfoLineaProductos.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/050-SetInfoLineaProductos.md)

##### `GetExisteLineaProductos`

- **Nombre:** `GetExisteLineaProductos`
- **Firma documentada:** `GetExisteLineaProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"GetExisteLineaProductos"/`
- **Objetivo:** Esta función es la encargada de validar si una línea de productos ya existe en la base de datos, recibe el código de la línea de productos a validar y retorna True si la línea existe o false si la línea no existe en la base de datos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: idepinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/060-GetExisteLineaProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/060-GetExisteLineaProductos.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/060-GetExisteLineaProductos.md)

##### `GetValidarNuevoIdLineaProductos`

- **Nombre:** `GetValidarNuevoIdLineaProductos`
- **Firma documentada:** `GetValidarNuevoIdLineaProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"GetValidarNuevoIdLineaProductos"/`
- **Objetivo:** Esta función es la encargada de validar si el código (idepinv) de una nueva línea de productos es correcto, valida que dicho código no contenga caracteres especiales.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: idepinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/070-GetValidarNuevoIdLineaProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/070-GetValidarNuevoIdLineaProductos.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/070-GetValidarNuevoIdLineaProductos.md)

##### `DoEliminarLineaProductos` **[PELIGROSA — escritura]**

- **Nombre:** `DoEliminarLineaProductos`
- **Firma documentada:** `DoEliminarLineaProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"DoEliminarLineaProductos"/`
- **Objetivo:** Esta función es la encargada de eliminar una línea de productos de la base de datos, esta función se debe ejecutar en dos pasos, así:
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: idepinv, accion; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/080-DoEliminarLineaProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/080-DoEliminarLineaProductos.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/080-DoEliminarLineaProductos.md)

##### `DoRecodificarLineaProductos` **[PELIGROSA — escritura]**

- **Nombre:** `DoRecodificarLineaProductos`
- **Firma documentada:** `DoRecodificarLineaProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLineasProductos`
- **Ruta REST:** `/datasnap/rest/TCatLineasProductos/"DoRecodificarLineaProductos"/`
- **Objetivo:** Esta función es la encargada de recodificar una línea de productos, es decir, cambia el código de identificación de la línea tanto en el catálogo de Departamento o líneas de productos como en las operaciones en las que esté relacionado.
- **Tipo de operación:** escritura
- **Parámetros principales:** dataJSON claves/campos documentados: idepinv, newidepinv; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/030_LineasProductos/090-DoRecodificarLineaProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/030_LineasProductos/090-DoRecodificarLineaProductos.md`](040_INVENTARIOS/010_Catalogos/030_LineasProductos/090-DoRecodificarLineaProductos.md)

#### 040_Bodegas

##### `GetListaBodegas`

- **Nombre:** `GetListaBodegas`
- **Firma documentada:** `GetListaBodegas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatBodegas`
- **Ruta REST:** `/datasnap/rest/TCatBodegas/"GetListaBodegas"/`
- **Objetivo:** Esta función es la encargada de retornar la información de las bodegas registradas en el sistema por cada empresa, para determinar de cual empresa se retornarán las bodegas, se toma la configuración de la empresa activa que tiene asignada el usuario. Esta función retornará para cada bodega la información que sea solicitada. Se debe utilizar cuando se van a mostrar las bodegas a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, ordenarpor, iinventario; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/040_Bodegas/010-GetListaBodegas.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/040_Bodegas/010-GetListaBodegas.md`](040_INVENTARIOS/010_Catalogos/040_Bodegas/010-GetListaBodegas.md)

#### 050_MetodosCalculoInv

##### `GetListaMetodosCalculoInv`

- **Nombre:** `GetListaMetodosCalculoInv`
- **Firma documentada:** `GetListaMetodosCalculoInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatMetodosCalculoInv`
- **Ruta REST:** `/datasnap/rest/TCatMetodosCalculoInv/"GetListaMetodosCalculoInv"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de métodos de cálculo (métodos para calcular el precio de los elementos de inventario) registrados en el sistema. Esta función retorna para cada método de cálculo la información que sea solicitada.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, idnfiltro, ordenarpor, nmetodo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/010_Catalogos/050_MetodosCalculoInv/010-GetListaMetodosCalculoInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/010_Catalogos/050_MetodosCalculoInv/010-GetListaMetodosCalculoInv.md`](040_INVENTARIOS/010_Catalogos/050_MetodosCalculoInv/010-GetListaMetodosCalculoInv.md)

#### DocApoyo

##### `GetListaClase1Inventarios`

- **Nombre:** `GetListaClase1Inventarios`
- **Firma documentada:** `GetListaClase1Inventarios (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaClase1Inventarios"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de valores que tiene el campo Clase 1 registrados en el sistema, este campo pertenece al catálogo de elementos de inventario.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/010-GetListaClase1Inventarios.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/010-GetListaClase1Inventarios.md`](040_INVENTARIOS/030_Informes/DocApoyo/010-GetListaClase1Inventarios.md)

##### `GetListaTipo1Inventarios`

- **Nombre:** `GetListaTipo1Inventarios`
- **Firma documentada:** `GetListaTipo1Inventarios (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaTipo1Inventarios"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de valores que tiene el campo Clase 1 registrados en el sistema, este campo pertenece al catálogo de elementos de inventario.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/020-GetListaTipo1Inventarios.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/020-GetListaTipo1Inventarios.md`](040_INVENTARIOS/030_Informes/DocApoyo/020-GetListaTipo1Inventarios.md)

##### `GetListaAgruparPorInventarios`

- **Nombre:** `GetListaAgruparPorInventarios`
- **Firma documentada:** `GetListaAgruparPorInventarios (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaAgruparPorInventarios"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de opciones por las cuales se puede agrupar la información de los siguientes reportes de inventarios:
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/030-GetListaAgruparPorInventarios.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/030-GetListaAgruparPorInventarios.md`](040_INVENTARIOS/030_Informes/DocApoyo/030-GetListaAgruparPorInventarios.md)

##### `GetListaOrdenarPorInventarios`

- **Nombre:** `GetListaOrdenarPorInventarios`
- **Firma documentada:** `GetListaOrdenarPorInventarios (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaOrdenarPorInventarios"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de campos que se pueden usar para ordenar la información de los siguientes informes de inventarios:
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: bporcc; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/040-GetListaOrdenarPorInventarios.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/040-GetListaOrdenarPorInventarios.md`](040_INVENTARIOS/030_Informes/DocApoyo/040-GetListaOrdenarPorInventarios.md)

##### `GetCamposFiltroInventarios`

- **Nombre:** `GetCamposFiltroInventarios`
- **Firma documentada:** `GetCamposFiltroInventarios (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetCamposFiltroInventarios"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de campos disponibles para filtrar los informes de:
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/050-GetCamposFiltroInventarios.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/050-GetCamposFiltroInventarios.md`](040_INVENTARIOS/030_Informes/DocApoyo/050-GetCamposFiltroInventarios.md)

##### `GetOrdenInformeRotacionProductos`

- **Nombre:** `GetOrdenInformeRotacionProductos`
- **Firma documentada:** `GetOrdenInformeRotacionProductos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetOrdenInformeRotacionProductos"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de campos disponibles para ordenar el informe de rotación de productos.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/060-GetOrdenInformeRotacionProductos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/060-GetOrdenInformeRotacionProductos.md`](040_INVENTARIOS/030_Informes/DocApoyo/060-GetOrdenInformeRotacionProductos.md)

##### `GetAgruparPorControlDocumentos`

- **Nombre:** `GetAgruparPorControlDocumentos`
- **Firma documentada:** `GetAgruparPorControlDocumentos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetAgruparPorControlDocumentos"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de campos disponibles para agrupar la información de los informes de control de documentos del módulo de Inventarios. Para poder obtener los campos de cada reporte es necesario enviar el código identificador del reporte (keyaction).
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: keyaction; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/070-GetAgruparPorControlDocumentos.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/070-GetAgruparPorControlDocumentos.md`](040_INVENTARIOS/030_Informes/DocApoyo/070-GetAgruparPorControlDocumentos.md)

##### `GetListaPreciosInforme`

- **Nombre:** `GetListaPreciosInforme`
- **Firma documentada:** `GetListaPreciosInforme (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaPreciosInforme"/`
- **Objetivo:** Esta función es la encargada de retornar el código y nombre de las listas de precios disponibles en el sistema, para así generar los informes de listas de precios del módulo de inventarios. Esta función también retorna los métodos de cálculo de las listas de precios.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdinforme; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/080-GetListaPreciosInforme.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/080-GetListaPreciosInforme.md`](040_INVENTARIOS/030_Informes/DocApoyo/080-GetListaPreciosInforme.md)

##### `GetEtiquetasRepInv`

- **Nombre:** `GetEtiquetasRepInv`
- **Firma documentada:** `GetEtiquetasRepInv (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetEtiquetasRepInv"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración de los campos: Clase 1, Clase 2, Tipo 1, Tipo 2, Dato 1, Dato 2 y Dato 3, que se presentan en la ventana de generación de algunos informes de inventarios. Retorna para cada campo si es visible, de solo lectura y la etiqueta que el usuario haya asignado a cada campo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `040_INVENTARIOS/030_Informes/DocApoyo/090-GetEtiquetasRepInv.html`
- **Archivo local (Markdown):** [`040_INVENTARIOS/030_Informes/DocApoyo/090-GetEtiquetasRepInv.md`](040_INVENTARIOS/030_Informes/DocApoyo/090-GetEtiquetasRepInv.md)

### Módulo inventarios plus

#### 020_Operaciones

##### `GetConfigOprORD1`

- **Nombre:** `GetConfigOprORD1`
- **Firma documentada:** `GetConfigOprORD1 (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetConfigOprOrd1"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración de los campos de la operación de pedido, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” de dicha operación.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdsop; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `050_INV_PLUS/020_Operaciones/010-GetConfigOprORD1.html`
- **Archivo local (Markdown):** [`050_INV_PLUS/020_Operaciones/010-GetConfigOprORD1.md`](050_INV_PLUS/020_Operaciones/010-GetConfigOprORD1.md)

##### `GetConfigOprORD4`

- **Nombre:** `GetConfigOprORD4`
- **Firma documentada:** `GetConfigOprORD4 (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatOperaciones`
- **Ruta REST:** `/datasnap/rest/TCatOperaciones/"GetConfigOprOrd4"/`
- **Objetivo:** Esta función es la encargada de retornar la configuración de los campos de la operación de pedido, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” de dicha operación.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: itdsop; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `050_INV_PLUS/020_Operaciones/020-GetConfigOprORD4.html`
- **Archivo local (Markdown):** [`050_INV_PLUS/020_Operaciones/020-GetConfigOprORD4.md`](050_INV_PLUS/020_Operaciones/020-GetConfigOprORD4.md)

### Módulo costos

#### DocApoyo

##### `GetDocumentosSoporte`

- **Nombre:** `GetDocumentosSoporte`
- **Firma documentada:** `GetDocumentosSoporte (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetDocumentosSoporte"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de documentos de soporte usados en las operaciones durante un período de tiempo determinado.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: fini, ffin; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/010-GetDocumentosSoporte.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/010-GetDocumentosSoporte.md`](060_COSTOS/030_Informes/DocApoyo/010-GetDocumentosSoporte.md)

##### `GetListaEtapas`

- **Nombre:** `GetListaEtapas`
- **Firma documentada:** `GetListaEtapas (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaEtapas"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de etapas de un centro de costos de producción, disponibles para generar informes de estados financieros por actividad y etapa.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/020-GetListaEtapas.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/020-GetListaEtapas.md`](060_COSTOS/030_Informes/DocApoyo/020-GetListaEtapas.md)

##### `GetListaCiclosCostosPorICC`

- **Nombre:** `GetListaCiclosCostosPorICC`
- **Firma documentada:** `GetListaCiclosCostosPorICC (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaCiclosCostosPorICC"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de ciclos de costos que tiene un centro de costos hasta una fecha dada. Para los informes de costos de producción, se deben pedir los ciclos en etapa de producción; y para los informes de costos de desarrollo, los ciclos en etapa de desarrollo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: icc, ietapa, fsoport; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/030-GetListaCiclosCostosPorICC.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/030-GetListaCiclosCostosPorICC.md`](060_COSTOS/030_Informes/DocApoyo/030-GetListaCiclosCostosPorICC.md)

##### `GetListaGruposInformeProduccionPorPeriodo`

- **Nombre:** `GetListaGruposInformeProduccionPorPeriodo`
- **Firma documentada:** `GetListaGruposInformeProduccionPorPeriodo (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaGruposInformeProduccionPorPeriodo"/`
- **Objetivo:** Esta función es la encargada de retorna el listado de opciones por los cuales se puede agrupar la información de los informes de:
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/040-GetListaGruposInformeProdPeriodo.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/040-GetListaGruposInformeProdPeriodo.md`](060_COSTOS/030_Informes/DocApoyo/040-GetListaGruposInformeProdPeriodo.md)

##### `GetListaProductosInformesCostos`

- **Nombre:** `GetListaProductosInformesCostos`
- **Firma documentada:** `GetListaProductosInformesCostos (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaProductosInformesCostos"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de productos producidos y embodegados en una empresa y hasta una fecha determinada.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: iactividad, fecharef, bembodegados; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/050-GetListaProducInformesCostos.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/050-GetListaProducInformesCostos.md`](060_COSTOS/030_Informes/DocApoyo/050-GetListaProducInformesCostos.md)

##### `GetListaClasificadoresCC`

- **Nombre:** `GetListaClasificadoresCC`
- **Firma documentada:** `GetListaClasificadoresCC (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetListaClasificadoresCC"/`
- **Objetivo:** Esta función es la encargada de retornar el listado de clasificadores de centros de costos que se pueden utilizar para generar el informe de: Costos de la producción de actividad empresarial.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: No documentado; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/060-GetListaClasificadoresCC.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/060-GetListaClasificadoresCC.md`](060_COSTOS/030_Informes/DocApoyo/060-GetListaClasificadoresCC.md)

##### `GetCuentaProductoProcesoCC`

- **Nombre:** `GetCuentaProductoProcesoCC`
- **Firma documentada:** `GetCuentaProductoProcesoCC (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetCuentaProductoProcesoCC"/`
- **Objetivo:** Esta función es la encargada de validar si un centro de costos tiene definida la cuenta de producto en proceso, si es así, retorna el código de dicha cuenta.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: icc, fecharef; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/070-GetCuentaProductoProcesoCC.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/070-GetCuentaProductoProcesoCC.md`](060_COSTOS/030_Informes/DocApoyo/070-GetCuentaProductoProcesoCC.md)

##### `GetMovCntCostosProduccionActEmpr`

- **Nombre:** `GetMovCntCostosProduccionActEmpr`
- **Firma documentada:** `GetMovCntCostosProduccionActEmpr (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TReportes`
- **Ruta REST:** `/datasnap/rest/TReportes/"GetMovCntCostosProduccionActEmpr"/`
- **Objetivo:** Esta función es la encargada de validar si la actividad seleccionada en el informe de “Costos de producción de actividad empresarial” tiene movimientos contables para los centros de costos que tienen asociada dicha actividad.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: iactividad, fini, ffin; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `060_COSTOS/030_Informes/DocApoyo/080-GetMovCntCostosProduccionActEmpr.html`
- **Archivo local (Markdown):** [`060_COSTOS/030_Informes/DocApoyo/080-GetMovCntCostosProduccionActEmpr.md`](060_COSTOS/030_Informes/DocApoyo/080-GetMovCntCostosProduccionActEmpr.md)

### Módulo activos

#### 010_CatalogoActivos

##### `GetListaSeleccion`

- **Nombre:** `GetListaSeleccion`
- **Firma documentada:** `GetListaSeleccion (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatActivos`
- **Ruta REST:** `/datasnap/rest/TCatActivos/"GetListaSeleccion"/`
- **Objetivo:** Esta función es la encargada de retornar un listado de activos registrados en el sistema, retorna de cada activo el código y el nombre. Esta función se debe utilizar cuando se requiera mostrar un listado de activos en un selector, es decir, una lista de selección en la que el usuario pueda elegir una opción.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, FSoport, ordenarpor, iactivo; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `070_ACTIVOS/010_Catalogos/010_CatalogoActivos/010-GetListaSeleccion.html`
- **Archivo local (Markdown):** [`070_ACTIVOS/010_Catalogos/010_CatalogoActivos/010-GetListaSeleccion.md`](070_ACTIVOS/010_Catalogos/010_CatalogoActivos/010-GetListaSeleccion.md)

### Módulo actividades

#### 010_CatActividades

##### `GetListaActividades`

- **Nombre:** `GetListaActividades`
- **Firma documentada:** `GetListaActividades (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatActividades`
- **Ruta REST:** `/datasnap/rest/TCatActividades/"GetListaActividades"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de actividades empresariales registradas en el sistema. Retornará para cada actividad la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar las actividades a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, ordenarpor, iactividad; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `080_ACTIVIDADES/010_Catalogos/010_CatActividades/010-GetListaActividades.html`
- **Archivo local (Markdown):** [`080_ACTIVIDADES/010_Catalogos/010_CatActividades/010-GetListaActividades.md`](080_ACTIVIDADES/010_Catalogos/010_CatActividades/010-GetListaActividades.md)

#### 020_CatLabores

##### `GetListaLabores`

- **Nombre:** `GetListaLabores`
- **Firma documentada:** `GetListaLabores (datajson, controlkey, iapp, random) : json`
- **Clase / controlador DataSnap:** `TCatLabores`
- **Ruta REST:** `/datasnap/rest/TCatLabores/"GetListaLabores"/`
- **Objetivo:** Esta función es la encargada de retornar la información de un listado de labores registradas en el sistema. Retornará para cada labor la información que sea solicitada. Esta función se debe utilizar cuando se van mostrar las labores a manera de catálogo.
- **Tipo de operación:** lectura
- **Parámetros principales:** dataJSON claves/campos documentados: datospagina, cantidadregistros, pagina, camposderetorno, datosfiltro, ordenarpor, ilabor; controlkey, iapp, random
- **Estructura principal de la respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **Archivo HTML de origen:** `080_ACTIVIDADES/010_Catalogos/020_CatLabores/010-GetListaLabores.html`
- **Archivo local (Markdown):** [`080_ACTIVIDADES/010_Catalogos/020_CatLabores/010-GetListaLabores.md`](080_ACTIVIDADES/010_Catalogos/020_CatLabores/010-GetListaLabores.md)

### Módulo automatización de documentos

Según [`modulo-automatizacion.md`](modulo-automatizacion.md): no hay catálogos de funciones HTML en este módulo; la documentación disponible es de apoyo (PDF) para JSON de operaciones (p. ej. DocJsonOprING2, DocJsonOprCOM5, DocJsonOprING3) y referencia a `DoExecuteOprAction()`. No hay páginas de función individuales indexables aquí más allá de esos PDF.

## 6. Sección especial: módulo de inventarios

El módulo de inventarios agrupa catálogos (elementos, grupos, líneas, bodegas), movimientos e informes. Fuentes de overview: [`modulo-inventarios.md`](modulo-inventarios.md) y carpeta `040_INVENTARIOS/`.

**Total de funciones de inventarios en el índice:** 58

### Módulo inventarios / 010_ElemInv

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetListaElemInv` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/010-GetListaElemInv.html` |
| `GetListaSeleccion` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/020-GetListaSeleccion.html` |
| `GetInfoElemInv` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/030-GetInfoElemInv.html` |
| `DoCrearElemInv` | escritura ⚠ | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/040-DoCrearElemInv.html` |
| `SetInfoElemInv` | escritura ⚠ | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/050-SetInfoElemInv.html` |
| `GetConfigCampos` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/060-GetConfigCampos.html` |
| `GetFotoTercero` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/070-GetFotoElemInv.html` |
| `GetExisteElemInv` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/080-GetExisteElemInv.html` |
| `GetValidarNuevoIdElemInv` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/090-GetValidarNuevoIdElemInv.html` |
| `DoEliminarElemInv` | escritura ⚠ | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/100-DoEliminarElemInv.html` |
| `DoConsolidarElemInv` | escritura ⚠ | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/110-DoConsolidarElemInv.html` |
| `DoRecodificarElemInv` | escritura ⚠ | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/120-DoRecodificarElemInv.html` |
| `GetAutoLista` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/130-GetAutoLista.html` |
| `GetListItdCotizarCompuesto` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/140-GetListItdCotizarCompuesto.html` |
| `GetListItdDescarga` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/150-GetListItdDescarga.html` |
| `GetListItdFacturarCompuesto` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/160-GetListItdFacturarCompuesto.html` |
| `GetListAfectaCuenta` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/170-GetListAfectaCuenta.html` |
| `GetListTiempoAlquilerElemInv` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/180-GetListTiempoAlquilerElemInv.html` |
| `GetListaPreciosUsuario` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/190-GetListaPreciosUsuario.html` |
| `GetPrecioCalculado` | lectura | `TCatElemInv` | `040_INVENTARIOS/010_Catalogos/010_ElemInv/200-GetPrecioCalculado.html` |

### Módulo inventarios / 020_GruposInv

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetListaGrupoInv` | lectura | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/010-GetListaGrupoInv.html` |
| `GetListaSeleccion` | lectura | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/020-GetListaSeleccion.html` |
| `GetInfoGrupoInv` | lectura | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/030-GetInfoGrupoInv.html` |
| `DoCrearGrupoInv` | escritura ⚠ | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/040-DoCrearGrupoInv.html` |
| `SetInfoGrupoInv` | escritura ⚠ | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/050-SetInfoGrupoInv.html` |
| `GetConfigCampos` | lectura | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/060-GetConfigCampos.html` |
| `GetExisteGrupoInv` | lectura | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/070-GetExisteGrupoInv.html` |
| `GetValidarNuevoIdGrupoInv` | lectura | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/080-GetValidarNuevoIdGrupoInv.html` |
| `DoEliminarGrupoInv` | escritura ⚠ | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/090-DoEliminarGrupoInv.html` |
| `DoRecodificarGrupoInv` | escritura ⚠ | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/100-DoRecodificarGrupoInv.html` |
| `GetInfoVisible` | lectura | `TCatGrupoInv` | `040_INVENTARIOS/010_Catalogos/020_GruposInv/110-GetInfoVisible.html` |

### Módulo inventarios / 025_Movimientos

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetListaMovInventario` | lectura | `TInventarios` | `040_INVENTARIOS/025_Movimientos/010-GetListaMovInventario.html` |
| `GetSaldoFisicoProductoEnBodegas` | lectura | `TInventarios` | `040_INVENTARIOS/025_Movimientos/020_GetSaldoFisicoProductoEnBodegas.html` |
| `GetListaElementosMovsPeriodo` | lectura | `TInventarios` | `040_INVENTARIOS/025_Movimientos/030_GetListaElementosMovsPeriodo.html` |
| `GetMovimientosProducto` | lectura | `TInventarios` | `040_INVENTARIOS/025_Movimientos/040_GetMovimientosProducto.html` |
| `GetListaUltPrecioCompraProducto` | lectura | `TInventarios` | `040_INVENTARIOS/025_Movimientos/050_GetListaUltPrecioCompraProducto.html` |
| `GetSaldosProductosEnBodegas` | lectura | `TCatElemInv` | `040_INVENTARIOS/025_Movimientos/060_GetSaldosProductosEnBodegas.html` |

### Módulo inventarios / 030_LineasProductos

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetListaLineasProductos` | lectura | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/010-GetListaLineasProductos.html` |
| `GetListaLineasSeleccion` | lectura | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/020-GetListaSeleccion.html` |
| `DoCrearLineaProductos` | escritura ⚠ | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/040-DoCrearLineaProductos.html` |
| `SetInfoLineaProductos` | escritura ⚠ | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/050-SetInfoLineaProductos.html` |
| `GetExisteLineaProductos` | lectura | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/060-GetExisteLineaProductos.html` |
| `GetValidarNuevoIdLineaProductos` | lectura | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/070-GetValidarNuevoIdLineaProductos.html` |
| `DoEliminarLineaProductos` | escritura ⚠ | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/080-DoEliminarLineaProductos.html` |
| `DoRecodificarLineaProductos` | escritura ⚠ | `TCatLineasProductos` | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/090-DoRecodificarLineaProductos.html` |

### Módulo inventarios / 040_Bodegas

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetListaBodegas` | lectura | `TCatBodegas` | `040_INVENTARIOS/010_Catalogos/040_Bodegas/010-GetListaBodegas.html` |

### Módulo inventarios / 050_MetodosCalculoInv

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetListaMetodosCalculoInv` | lectura | `TCatMetodosCalculoInv` | `040_INVENTARIOS/010_Catalogos/050_MetodosCalculoInv/010-GetListaMetodosCalculoInv.html` |

### Módulo inventarios / DocApoyo

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetListaClase1Inventarios` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/010-GetListaClase1Inventarios.html` |
| `GetListaTipo1Inventarios` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/020-GetListaTipo1Inventarios.html` |
| `GetListaAgruparPorInventarios` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/030-GetListaAgruparPorInventarios.html` |
| `GetListaOrdenarPorInventarios` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/040-GetListaOrdenarPorInventarios.html` |
| `GetCamposFiltroInventarios` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/050-GetCamposFiltroInventarios.html` |
| `GetOrdenInformeRotacionProductos` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/060-GetOrdenInformeRotacionProductos.html` |
| `GetAgruparPorControlDocumentos` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/070-GetAgruparPorControlDocumentos.html` |
| `GetListaPreciosInforme` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/080-GetListaPreciosInforme.html` |
| `GetEtiquetasRepInv` | lectura | `TReportes` | `040_INVENTARIOS/030_Informes/DocApoyo/090-GetEtiquetasRepInv.html` |

### Módulo inventarios plus / 020_Operaciones

| Función | Tipo | Controlador | HTML origen |
| --- | --- | --- | --- |
| `GetConfigOprORD1` | lectura | `TCatOperaciones` | `050_INV_PLUS/020_Operaciones/010-GetConfigOprORD1.html` |
| `GetConfigOprORD4` | lectura | `TCatOperaciones` | `050_INV_PLUS/020_Operaciones/020-GetConfigOprORD4.html` |

### Relacionado: Módulo inventarios plus

Hay 2 función(es) bajo inventarios plus (carpeta `050_INV_PLUS/`).

- `GetConfigOprORD1` — `TCatOperaciones` — `050_INV_PLUS/020_Operaciones/010-GetConfigOprORD1.html` — lectura
- `GetConfigOprORD4` — `TCatOperaciones` — `050_INV_PLUS/020_Operaciones/020-GetConfigOprORD4.html` — lectura

## 7. Autenticación, cierre de sesión y verificación del Agente

### `GetAuth`

- **Objetivo:** Esta función es la encargada de loguear a un usuario en el Agente y retornar un código único para la sesión del usuario (keyAgente). Ésta es la primera función que se debe ejecutar para establecer comunicación con el Agente.
- **Clase DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"GetAuth"/`
- **Tipo:** lectura
- **Respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **HTML origen:** `005_INTRODUCCION/020-GetAuth.html`
- **Eventualidades documentadas:**
  - 0: Error en la aplicación, errores no controlados.
  - 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
  - 10: No se ingresó un Json como parámetro.
  - 1000: El nombre de usuario y/o contraseña son incorrectos.
  - 1001: No se ingresó el nombre de usuario y/o contraseña.
  - 1007: Ingrese el id de la aplicación "IAPP".
  - 1008: El código de la aplicación es incorrecto, informar de este error.

### `Logout`

- **Objetivo:** Esta función es la encargada de cerrar la sesión del usuario en el Agente, es decir elimina los datos del usuario de la lista de usuarios logueados.
- **Clase DataSnap:** `TBasicoGeneral`
- **Ruta REST:** `/datasnap/rest/TBasicoGeneral/"Logout"/`
- **Tipo:** escritura
- **Respuesta:** result[] → encabezado (resultado, imensaje, mensaje, tiempo) → respuesta.datos
- **HTML origen:** `005_INTRODUCCION/130-Logout.html`
- **Eventualidades documentadas:**
  - 0: Error en la aplicación, errores no controlados.
  - 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
  - 40: Usuario no logueado.

### `Test`

- **Objetivo:** Esta función es la encargada de retornar mediante un código el estado en el que se encuentra el Agente de servicios web.
- **Clase DataSnap:** `No documentado`
- **Ruta REST:** `No documentado`
- **Tipo:** lectura
- **Respuesta:** result[]: string de estado (código|mensaje)
- **HTML origen:** `005_INTRODUCCION/010-Test.html`
- **Eventualidades documentadas:**
  - 0: Error en la aplicación, errores no controlados.
  - 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
  - 40: Usuario no logueado.
  - 130: No se ingresó el parámetro "X".

## 8. Códigos de error y eventualidades

Códigos recopilados de las secciones *Eventualidades* / *Específicas* de las páginas de función. Un mismo código puede repetirse en varias funciones; aquí se muestra el mensaje asociado encontrado.

| Código | Mensaje (según documentación) |
| --- | --- |
| 0 | Error en la aplicación, errores no controlados. |
| 1 | Mensaje que le indica al usuario que debe corregir errores (errores controlados). |
| 10 | No se ingresó un Json como parámetro. |
| 40 | Usuario no logueado. |
| 50 | El usuario no posee permisos para: “abrir el catálogo de terceros”. |
| 80 | No se ingresaron los campos de retorno. |
| 90 | No se ingresó la cantidad de registros a retornar. |
| 100 | El número de la página es incorrecto. |
| 110 | No se ingresaron los parámetros de paginación. |
| 130 | No se ingresó el parámetro "X". |
| 140 | No se ingresó el nombre de la tabla. |
| 160 | El nombre de la tabla ingresado es incorrecto. |
| 170 | No se ingresaron los parámetros de filtro. |
| 180 | No se ingresaron los campos de los cuales desea obtener la configuración. |
| 190 | El registro no existe. |
| 200 | El tercero no se puede eliminar. |
| 210 | Nuevo identificador no valido |
| 220 | El registro ya existe. |
| 240 | No hay configurado un precio de venta para el elemento de inventario. |
| 250 | No se tiene permisos sobre el registro por seguridad de datos, por lo tanto no se puede ejecutar la acción |
| 1000 | El nombre de usuario y/o contraseña son incorrectos. |
| 1001 | No se ingresó el nombre de usuario y/o contraseña. |
| 1005 | Ingrese la acción que desea ejecutar "Verificar" tercero ó "Eliminar" tercero. |
| 1007 | Ingrese el id de la aplicación "IAPP". |
| 1008 | El código de la aplicación es incorrecto, informar de este error. |

Estructura típica de respuesta ante eventualidad (ejemplo repetido en varias páginas):

```json
{
  "result": [{
    "encabezado": {"resultado": "false", "imensaje": "40", "mensaje": "Usuario no logueado."},
    "respuesta": {"datos": ""}
  }]
}
```

## 9. Funciones peligrosas (modifican información)

⚠️ **PELIGROSAS:** funciones clasificadas como escritura porque su nombre y/o descripción documentada indican creación, modificación, eliminación, ejecución de acciones, envío, SQL de escritura o cierre de sesión. No ejecutarlas sin solicitud explícita.

| Función | Motivo | Módulo | HTML origen |
| --- | --- | --- | --- |
| `Logout` | Cierra la sesión del usuario en el Agente | Introducción / Autenticación y Agente | `005_INTRODUCCION/130-Logout.html` |
| `DoConsolidarCuenta` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo básico | `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/110-DoConsolidarCuenta.html` |
| `DoConsolidarTercero` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo básico | `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/110-DoConsolidarTercero.html` |
| `DoCrearCuenta` | Crea registros en el sistema | Módulo básico | `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/040-DoCrearCuenta.html` |
| `DoCrearTercero` | Crea registros en el sistema | Módulo básico | `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/040-DoCrearTercero.html` |
| `DoEliminarCuenta` | Elimina o puede eliminar información | Módulo básico | `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/090-DoEliminarCuenta.html` |
| `DoEliminarTercero` | Elimina o puede eliminar información | Módulo básico | `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/090-DoEliminarTercero.html` |
| `DoEnviarEmail` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo básico | `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/080-DoEnviarEmail.html` |
| `DoExecuteOprAction` | Ejecuta acciones sobre operaciones (incluye New y otras) | Módulo básico | `010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/030-DoExecuteOprAction.html` |
| `DoRecodificarTercero` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo básico | `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/100-DoRecodificarTercero.html` |
| `SetDatosTrabajo` | Modifica información existente | Módulo básico | `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/040-SetDatosTrabajo.html` |
| `SetEmailFavoritos` | Modifica información existente | Módulo básico | `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/070-SetEmailFavoritos.html` |
| `SetInfoCuenta` | Modifica información existente | Módulo básico | `010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/050-SetInfoCuenta.html` |
| `SetInfoTercero` | Modifica información existente | Módulo básico | `010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/050-SetInfoTercero.html` |
| `SetSQL` | Ejecuta SQL de escritura (Insert/Update/Delete) | Módulo básico | `010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/100-SetSQL.html` |
| `DoEliminarFavoritoCuentas` | Elimina o puede eliminar información | Módulo contabilidad | `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/110-DoEliminarFavoritoCuentas.html` |
| `SetCategoriaFavorita` | Modifica información existente | Módulo contabilidad | `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/100-SetCategoriaFavorita.html` |
| `SetCuentasFavoritas` | Modifica información existente | Módulo contabilidad | `020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/040-SetCuentasFavoritas.html` |
| `DoConsolidarElemInv` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/010_ElemInv/110-DoConsolidarElemInv.html` |
| `DoCrearElemInv` | Crea registros en el sistema | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/010_ElemInv/040-DoCrearElemInv.html` |
| `DoCrearGrupoInv` | Crea registros en el sistema | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/020_GruposInv/040-DoCrearGrupoInv.html` |
| `DoCrearLineaProductos` | Crea registros en el sistema | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/040-DoCrearLineaProductos.html` |
| `DoEliminarElemInv` | Elimina o puede eliminar información | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/010_ElemInv/100-DoEliminarElemInv.html` |
| `DoEliminarGrupoInv` | Elimina o puede eliminar información | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/020_GruposInv/090-DoEliminarGrupoInv.html` |
| `DoEliminarLineaProductos` | Elimina o puede eliminar información | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/080-DoEliminarLineaProductos.html` |
| `DoRecodificarElemInv` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/010_ElemInv/120-DoRecodificarElemInv.html` |
| `DoRecodificarGrupoInv` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/020_GruposInv/100-DoRecodificarGrupoInv.html` |
| `DoRecodificarLineaProductos` | Modifica o ejecuta acciones con efecto sobre datos/sesión | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/090-DoRecodificarLineaProductos.html` |
| `SetInfoElemInv` | Modifica información existente | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/010_ElemInv/050-SetInfoElemInv.html` |
| `SetInfoGrupoInv` | Modifica información existente | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/020_GruposInv/050-SetInfoGrupoInv.html` |
| `SetInfoLineaProductos` | Modifica información existente | Módulo inventarios | `040_INVENTARIOS/010_Catalogos/030_LineasProductos/050-SetInfoLineaProductos.html` |

**Total peligrosas:** 31

## 10. Validación de referencias a archivos de origen

- Funciones en el índice: **182**
- Funciones con referencia HTML de origen: **182**
- Validación: **todas** las funciones incluidas tienen referencia al archivo HTML de origen.

Los archivos HTML originales en el servidor remoto no se modifican; localmente la documentación está en Markdown (`.md`) generado desde HTML.

---

*Índice generado automáticamente por `scripts/build_contapyme_index.py` a partir de la documentación local. No inventa parámetros, rutas ni códigos de error ausentes en las páginas fuente.*
