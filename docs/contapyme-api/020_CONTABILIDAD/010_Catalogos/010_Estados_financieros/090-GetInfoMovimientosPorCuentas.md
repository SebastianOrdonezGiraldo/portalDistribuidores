# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/090-GetInfoMovimientosPorCuentas.html

Â¿CÃ³mo obtener la informaciÃ³n de movimientos en cuenta?

GetInfoMovimientosPorCuentas (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la información de las operaciones que generaron los movimientos de una cuenta.

#### Resultado

Retorna un Json con la informaciÃ³n de las operaciones que generaron los movimientos de una cuenta.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **fini**: Fecha inicial de cálculo. (requerido)  **ffin**: Fecha final de cálculo. (requerido)  **cuentas**: Arreglo con los códigos de las cuentas para las cuales se obtendrán las operaciones que generaron los movimientos. (requerido)  **init**: Código del tercero del cual se desea obtener los documentos. (opcional). | { "fini": "01/01/2013", "ffin": "01/31/2013", "cuentas": [ "1105" ], "init": "30402491" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TEstadosFinancieros/"GetInfoMovimientosPorCuentas"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"fini": "01/01/2013",
"ffin": "01/31/2013",
"cuentas": [
"1105"
],
"init": "30402491"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/090-GetInfoMovimientosPorCuentas.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "47"
},
"respuesta": {
"datos": [
{
"inumoper": "161",
"itdoper": "ING1",
"snumsop": "FV-000050",
"fsoport": "12-09-2013",
"mvalor": "473280",
"tdetalle": "Ventas 09-12-2013",
"init": "75080200",
"ntdsop": "Factura de venta",
"mdebito": "0",
"mcredito": "408000"
},
{
"inumoper": "171",
"itdoper": "ING1",
"snumsop": "FV-000054",
"fsoport": "12-23-2013",
"mvalor": "243600",
"tdetalle": "Ventas 23-12-2013",
"init": "75080200",
"ntdsop": "Factura de venta",
"mdebito": "0",
"mcredito": "210000"
},
{
"inumoper": "178",
"itdoper": "CIE9",
"snumsop": "CA-000001",
"fsoport": "12-31-2013",
"mvalor": "",
"tdetalle": "Cierre de final del aÃ±o 2013",
"init": "",
"ntdsop": "Cierre fin de aÃ±o",
"mdebito": "3194000",
"mcredito": "0"
},
{
"inumoper": "222",
"itdoper": "ING1",
"snumsop": "FV-000071",
"fsoport": "12-30-2013",
"mvalor": "2227200",
"tdetalle": "Ventas 30-12-2013",
"init": "75080200",
"ntdsop": "Factura de venta",
"mdebito": "0",
"mcredito": "1920000"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar)**: Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” que se describe a continuación   **inumoper (varchar):** número único asignado a la operación. **itdoper (varchar)**: Tipo de operación. **snumsop (varchar):** Número de documento formateado según la máscara del tipo de documento. **fsoport (varchar)**: Fecha de soporte de la operación **mvalor (double)**: Valor total de la operación **tdetalle (varchar)**: Detalle o descripción de la operación. **init (varchar)**: Código identificador del tercero principal de la operación **ntdsop (varchar)**: Nombre del documento de soporte de la operación **mdebito (double):** Débitos de la operación **mcredito (double):** Créditos de la operación |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 130: No se ingresÃ³ el parÃ¡metro "X".

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
