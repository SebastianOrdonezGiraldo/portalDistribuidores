# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/010-GetListaOperaciones.html

Â¿CÃ³mo obtener la lista de operaciones?

GetListaOperaciones (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar el listado de las operaciones registradas en el catálogo de operaciones del sistema, retorna para cada operación la información que sea solicitada.  
Con esta función es posible obtener todas las operaciones registradas u obtener un tipo de operación en particular, por ejemplo: obtener todos los pedidos o facturas registradas en el sistema.

#### Resultado

Retorna un Json con el listado de operaciones registradas en el sistema.

Seguridad

Aplica todas las configuraciones de seguridad de datos y de seguridad de acciones de ContaPyme / AgroWin.

Compatibilidad de la API

FunciÃ³n disponible desde ContaPyme/AgroWin VersiÃ³n 4 - Release 7.

## PeticiÃ³n

Requisitos

Debe haber realizado el logueo en el agente a travÃ©s de la funciÃ³n [GETAUTH().](../../../005_INTRODUCCION/020-GetAuth.html)

ParÃ¡metros

| Nombre parÃ¡metro | Tipo | DescripciÃ³n | Ejemplo |
| --- | --- | --- | --- |
| dataJSON | JSON | Este Json contiene en su interior la siguiente estructura.  **datospagina**: Json que contiene: cantidadregistros: cantidad de registros a retornar por petición (requerido) pagina: número de página desde la cual se retornará la información (requerido)  **camposderetorno:** Arreglo que contiene los nombres de los campos de los cuales se desea obtener la información. Para conocer los nombres de los campos que se pueden solicitar en la petición, consultar el documento: “InfoBasicaOperacion” que se encuentra en la zona de “Documentación de apoyo”  También es posible obtener los siguientes campos calculados: **“ncorto”:** Nombre del corto del tipo de documento de soporte de la operación. **“ntdsop”:** Nombre del largo del tipo de documento de soporte de la operación. **“ntercero”:** Nombre del tercero principal de la operación.  Si no se especifica ningún campo de retorno la consulta devolverá todos los campos de la tabla, incluyendo los campos calculados.  (requerido)  **datosfiltro:** Json que contiene la información por la cual se desea filtrar la búsqueda, los campos que se pueden utilizar como filtro se encuentran en el documento: “InfoBasicaOperacion” de la zona de “Documentación de apoyo” (opcional).  **ordenarpor:** Json que contiene la información por la cual se va a ordenar la consulta, se pueden ordenar tanto de forma ascendente como descendente, los campos por los cuales se puede ordenar la consulta se encuentran en el documento “InfoBasicaOperacion“ de la zona de “Documentación de apoyo”. (opcional)  **itdoper:** Arreglo que permite obtener el listado de operaciones de uno o varios tipos de operación. Por ejemplo, es posible solicitar solo los pedidos y las facturas, para ello itdoper tendría la siguiente estructura: "itdoper":¨["ORD1",”ING4”] La información de los tipos de operación que tiene disponibles el sistema se encuentran en el documento: “TiposDeOperaciones” de la zona de “Documentación de apoyo”. (opcional)  **init**: Arreglo que contiene códigos de identificación de terceros, es utilizado cuando se requiera obtener las operaciones de terceros en particular y sin necesidad de que sean los principales de la operación. | { "camposderetorno": [ "itdoper", "inumoper", "tdetalle", "fcreacion", "ncorto", "iestado", "ntdsop", "ntercero", "iprocess", "fsoport", "snumsop", "qerror", "qwarning", "banulada", "mingresos", "megresos", "mtotaloperacion" ], "datospagina": { "cantidadregistros": "20", "pagina": "1" }, "datosfiltro": { "fanio": "2013", "fmes": "8" }, "ordenarpor": { "fsoport": "desc" } } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

Todas las operaciones
Operaciones de pedidos y facturaciÃ³n
Operaciones de un tercero

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetListaOperaciones"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"datospagina": {
"cantidadregistros": "20",
"pagina": "1"
},
"camposderetorno": [
"itdoper",
"inumoper",
"tdetalle",
"fcreacion",
"ncorto",
"iestado",
"ntdsop",
"ntercero",
"iprocess",
"fsoport",
"snumsop",
"qerror",
"qwarning",
"banulada",
"mingresos",
"megresos",
"mtotaloperacion"
],
"ordenarpor": {
"fsoport": "desc"
},
"datosfiltro": {
}
};
//Se arma los 4 parÃ¡metros de entrada de la funcion
var JSONSend ={ "\_parameters" : [ JSON.stringify(dataJSON), controlkey, iapp ,"0" ] };
//se constuye objeto para realizar la peticiÃ³n desde JavaScript
var xhr = new XMLHttpRequest();
//Se inicializa la solicitud enviando el verbo y la URL a invocar
xhr.open("POST",URL);
//Se define el evento que se dispararÃ¡ cuando se resuelva la peticiÃ³n
xhr.onreadystatechange = function() {
//se verifica que la peticiÃ³n se hubiese terminado
if (xhr.readyState == 4 && xhr.status == 200) {
//se envia la respuesta del servidor para que se imprima
imprimirRespuesta(xhr.responseText)
}
};
//EnvÃ­a la solicitud adjuntando el JSONSend que contiene los 4 parametros de la funciÃ³n
xhr.send(JSON.stringify(JSONSend));

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetListaOperaciones"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"datospagina": {
"cantidadregistros": "20",
"pagina": "1"
},
"camposderetorno": [
"itdoper",
"inumoper",
"tdetalle",
"fcreacion",
"ncorto",
"iestado",
"ntdsop",
"ntercero",
"iprocess",
"fsoport",
"snumsop",
"qerror",
"qwarning",
"banulada",
"mingresos",
"megresos",
"mtotaloperacion"
],
"ordenarpor": {
"fsoport": "desc"
},
"itdoper": [
"ORD1",
"ING1"
]
};
//Se arma los 4 parÃ¡metros de entrada de la funcion
var JSONSend ={ "\_parameters" : [ JSON.stringify(dataJSON), controlkey, iapp ,"0" ] };
//se constuye objeto para realizar la peticiÃ³n desde JavaScript
var xhr = new XMLHttpRequest();
//Se inicializa la solicitud enviando el verbo y la URL a invocar
xhr.open("POST",URL);
//Se define el evento que se dispararÃ¡ cuando se resuelva la peticiÃ³n
xhr.onreadystatechange = function() {
//se verifica que la peticiÃ³n se hubiese terminado
if (xhr.readyState == 4 && xhr.status == 200) {
//se envia la respuesta del servidor para que se imprima
imprimirRespuesta(xhr.responseText)
}
};
//EnvÃ­a la solicitud adjuntando el JSONSend que contiene los 4 parametros de la funciÃ³n
xhr.send(JSON.stringify(JSONSend));

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetListaOperaciones"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"datospagina": {
"cantidadregistros": "20",
"pagina": "1"
},
"camposderetorno": [
"itdoper",
"inumoper",
"tdetalle",
"fcreacion",
"ncorto",
"iestado",
"ntdsop",
"ntercero",
"iprocess",
"fsoport",
"snumsop",
"qerror",
"qwarning",
"banulada",
"mingresos",
"megresos",
"mtotaloperacion"
],
"ordenarpor": {
"fsoport": "desc"
},
"datosfiltro": {
"init":"856956254"
}
};
//Se arma los 4 parÃ¡metros de entrada de la funcion
var JSONSend ={ "\_parameters" : [ JSON.stringify(dataJSON), controlkey, iapp ,"0" ] };
//se constuye objeto para realizar la peticiÃ³n desde JavaScript
var xhr = new XMLHttpRequest();
//Se inicializa la solicitud enviando el verbo y la URL a invocar
xhr.open("POST",URL);
//Se define el evento que se dispararÃ¡ cuando se resuelva la peticiÃ³n
xhr.onreadystatechange = function() {
//se verifica que la peticiÃ³n se hubiese terminado
if (xhr.readyState == 4 && xhr.status == 200) {
//se envia la respuesta del servidor para que se imprima
imprimirRespuesta(xhr.responseText)
}
};
//EnvÃ­a la solicitud adjuntando el JSONSend que contiene los 4 parametros de la funciÃ³n
xhr.send(JSON.stringify(JSONSend));

âº EJECUTAR CODIGO

Ver otros ejemplos en:
[PHP](../../../ejemplo/PHP.html) ,
[JAVA](../../../ejemplo/JAVA.html),
[C#](../../../ejemplo/Csharp.html),
[Visual Basic.net](../../../ejemplo/visualBasic.html),
[Visual Basic 6](../../../ejemplo/visualBasic6.html),
[Delphi.](../../../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/010-GetListaOperaciones.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "93"
},
"respuesta": {
"paginacion": {
"totalpaginas": "9",
"totalregistros": "173"
},
"datos": [
{
"itdoper": "ORD1",
"inumoper": "225",
"tdetalle": "Pedido de un cliente en 15-03-2014",
"fcreacion": "15/03/2014 06:28:25 a.m.",
"iestado": "1",
"iprocess": "0",
"fsoport": "03/15/2014",
"snumsop": "PED-1403001",
"qerror": "0",
"qwarning": "0",
"banulada": "F",
"mingresos": "0",
"megresos": "0",
"mtotaloperacion": "3000000",
"ncorto": "PedCli",
"ntdsop": "Pedido de un cliente",
"ntercero": "FRANCISCO GÃMEZ"
},
{
"itdoper": "ORD4",
"inumoper": "226",
"tdetalle": "CotizaciÃ³n de un cliente en 15-03-2014",
"fcreacion": "15/03/2014 09:51:21 a.m.",
"iestado": "1",
"iprocess": "0",
"fsoport": "03/15/2014",
"snumsop": "CTC-1403001",
"qerror": "",
"qwarning": "",
"banulada": "F",
"mingresos": "",
"megresos": "",
"mtotaloperacion": "0",
"ncorto": "CotCli",
"ntdsop": "CotizaciÃ³n al cliente",
"ntercero": ""
},
{
"itdoper": "ING1",
"inumoper": "194",
"tdetalle": "Ventas 20-01-2014",
"fcreacion": "19/02/2014 06:27:44 p.m.",
"iestado": "1",
"iprocess": "2",
"fsoport": "01/20/2014",
"snumsop": "FV-000062",
"qerror": "0",
"qwarning": "0",
"banulada": "F",
"mingresos": "598560",
"megresos": "0",
"mtotaloperacion": "",
"ncorto": "FacVen",
"ntdsop": "Factura de venta",
"ntercero": "JESÃS HUMBERTO MARÃN"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “paginación” y el arreglo de objetos “datos” los cuales serán descritos a continuación.  **paginación (json):** contiene en su interior:  **totalpaginas:** Es el total de páginas que calcula el Agente con base a la cantidad de registros y la cantidad a mostrar por página. **totalregistros:** Es el total de registros que hay disponibles para el selector.  **datos (arreglo de objetos):** contiene en su interior objetos cuyas llaves son los campos de retorno y su valor es el valor que arrojó la consulta de cada campo. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 50: El usuario no posee permisos para: âabrir el catÃ¡logo de tercerosâ.
- 80: No se ingresaron los campos de retorno.
- 90: No se ingresÃ³ la cantidad de registros a retornar.
- 100: El nÃºmero de la pÃ¡gina es incorrecto
- 110: No se ingresaron los parÃ¡metros de paginaciÃ³n.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
