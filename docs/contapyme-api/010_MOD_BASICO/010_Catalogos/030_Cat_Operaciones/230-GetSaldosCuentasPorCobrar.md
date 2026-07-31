# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/230-GetSaldosCuentasPorCobrar.html

Â¿CÃ³mo verificar los saldos de cuenta por cobrar?

GetSaldosCuentasPorCobrar (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar el listado de saldos de cuentas por cobrar y sus datos asociados, hasta una fecha determinada.

#### Resultado

Retorna un arreglo de objetos que contienen la cuenta, tercero, referencia, saldo en moneda local y extranjera (si es del caso), fecha de Ãºltimo pago, fecha de vencimiento, etc.

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
| dataJSON | JSON | Este Json contiene en su interior la siguiente estructura.   **fecha:** Fecha hasta la cual se calcularán los saldos de las cuentas por cobrar. (requerido)  **init:** Código del tercero para el cual se calcularán las cuentas por cobrar, de no darse el código del tercero se calcula para todos los terceros. (opcional)  **bsolovencidas:** T si se debe calcular sólo las cuentas por cobrar que ya están vencidas. (opcional)  **bdatostercero:** T si se debe retornar datos relevantes de cada tercero, como: teléfono, dirección, ciudad, país, nombre comercial, etc. (opcional)  **bdatoscreacion:** T si se debe retornar datos de la operación con la cual se originó la cuenta por cobrar, como: número de documento, fecha, saldo original, detalle y clase operación, etc. (opcional)  **bcuentasencero**: T si se debe retornar las cuentas por cobrar que ya han sido canceladas. (opcional)  **blocal**: T si el cálculo de saldo se hace a partir de la contabilización local y F para la contabilización NIIF, de no ser enviado se asume F. (opcional). | { "fecha": "11-04-2016", "blocal": "T", "init": "1053770003", "bsolovencidas": "T", "bdatostercero": "T", "bdatoscreacion": "T" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetSaldosCuentasPorCobrar"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"fecha": "11-04-2016",
"blocal": "T",
"init": "1053770003",
"bsolovencidas": "T",
"bdatostercero": "T",
"bdatoscreacion": "T"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/230-GetSaldosCuentasPorCobrar.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result" : [{
"encabezado" : {
"resultado" : "true",
"imensaje" : "",
"mensaje" : "",
"tiempo" : "314"
},
"respuesta" : {
"datos" : [{
"iemp" : "1",
"icc" : "",
"icuenta" : "130505",
"initcxx" : "1053770",
"icxx" : "FC-024099:1",
"fsoport" : "11-03-2016",
"fultpago" : "11-03-2016",
"inumoper" : "715",
"ilinea" : "5",
"itdsop" : "2",
"inumsop" : "FC-024099",
"init" : "1053770003",
"tdetalle" : "CxC (en 3 cuotas)(Cuota No. 1 de 3)",
"iclasifop" : "1",
"msaldo" : "1153250",
"mextranjera" : "0",
"fpago" : "11-03-2016",
"ttelefono" : "8756853",
"ncomercial" : "",
"tfax" : "",
"tdireccion" : "Bosques del Norte",
"nciudad" : "MEDELLIN",
"npais" : "COLOMBIA",
"nzona" : "",
"itdcategoria" : "",
"itdtercero" : "Cliente",
"ncontacto" : "",
"ttelefonocontacto" : "",
"semailcontacto" : "",
"faniopago" : "2016",
"fmespago" : "11",
"fdiapago" : "3",
"initvendedor" : "810000",
"initvendedor2" : "",
"msaldooriginal" : "11250",
"mvalorpagado" : "0",
"fsemanapago" : "44"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el “datos” los cuales serán descritos a continuación.  **datos (arreglo de objetos):** contiene en su interior objetos cuyas llaves son los campos de retorno y su valor es el valor que arrojó la consulta de cada campo. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 130: Ingrese el parÃ¡metro âxxxxxâ.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
