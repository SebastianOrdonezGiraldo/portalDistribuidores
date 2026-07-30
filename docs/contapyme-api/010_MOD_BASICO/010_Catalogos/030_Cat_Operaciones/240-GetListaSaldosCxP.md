# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/240-GetListaSaldosCxP.html

Â¿CÃ³mo verificar la lista de saldos por cartera?

GetListaSaldosCxP (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta funciÃ³n es la encargada de retornar el listado de saldos de cuentas por pagar que se tiene con un
tercero. Generalmente es utilizada para realizar abonos a cuentas por pagar, generar listados de pagos a
proveedores o consultar cuentas por pagar a vencer.

#### Resultado

Retorna un Json con la lista de saldos de cuentas por pagar de un tercero.

Seguridad

Aplica todas las configuraciones de seguridad de datos y de seguridad de acciones de ContaPyme / AgroWin.

Compatibilidad de la API

FunciÃ³n disponible desde ContaPyme/AgroWin VersiÃ³n 4 - Release 8.

## PeticiÃ³n

Requisitos

Debe haber realizado el logueo en el agente a travÃ©s de la funciÃ³n [GETAUTH().](../../../005_INTRODUCCION/020-GetAuth.html)

ParÃ¡metros

| Nombre parÃ¡metro | Tipo | DescripciÃ³n | Ejemplo |
| --- | --- | --- | --- |
| dataJSON | JSON | Este Json contiene en su interior la siguiente estructura.  **datospagina**: Json que contiene: **cantidadregistros:** cantidad de registros a retornar por petición (requerido) **pagina:** número de página desde la cual se retornará la información (requerido)  **camposderetorno**: Arreglo que contiene los nombres de los campos de los cuales se desea obtener la información. Para conocer los nombres de los campos que se pueden solicitar en la petición, consultar el documento: “InfoSaldosCxC” de la zona de “Documentación de apoyo”. (requerido)  **ordenarpor**: Json que contiene la información por la cual se va a ordenar la consulta, se pueden ordenar tanto de forma ascendente como descendente, los campos por los cuales se puede ordenar la consulta se encuentran en el documento “InfoSaldosCxC“ en la zona de “Documentación de apoyo”. (opcional)  **datosfiltro**: Json que contiene la información por la cual se desea filtrar la búsqueda, los campos que se pueden utilizar como filtro se encuentran en el **documento:** “InfoSaldosCxC” en la zona de “Documentación de apoyo”. Si se desean obtener los saldos de cuentas por cobrar de un tercero en particular, en este parámetro se debe enviar el campo “init” con el código del tercero del cual se desean obtener los saldos, así: **"datosfiltro"**:{"init":"1053774102" (opcional). | { "datospagina": { "cantidadregistros": 20, "pagina": 1 }, "camposderetorno": [ "fsoport", "fpago", "msaldo", "ncuenta", "icuenta", "icxx" ], "ordenarpor": { "icxx": "asc" }, "datosfiltro": { "init": "1053810102" } } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "428917919991424" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1034" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "5267348865782469" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetListaSaldosCxP"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"datospagina": {
"cantidadregistros": 20,
"pagina": 1
},
"camposderetorno": [
"fsoport",
"fpago",
"msaldo",
"ncuenta",
"icuenta",
"icxx"
],
"ordenarpor": {
"icxx": "asc"
},
"datosfiltro": {
"init": "1053810102"
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

âº EJECUTAR
CODIGO

Ver otros ejemplos en:
[PHP](../../../ejemplo/PHP.html) ,
[JAVA](../../../ejemplo/JAVA.html),
[C#](../../../ejemplo/Csharp.html),
[Visual Basic.net](../../../ejemplo/visualBasic.html),
[Visual Basic 6](../../../ejemplo/visualBasic6.html),
[Delphi.](../../../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/240-GetListaSaldosCxP.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "110"
},
"respuesta": {
"paginacion": {
"totalpaginas": "1",
"totalregistros": "3"
},
"datos": [
{
"fsoport": "07/11/2013",
"fpago": "31/12/2013",
"msaldo": "200000",
"ncuenta": "Sobregiros",
"icuenta": "210505",
"icxx": "CE-000948"
},
{
"fsoport": "07/11/2013",
"fpago": "30/01/2014",
"msaldo": "500000",
"ncuenta": "PagarÃ©s",
"icuenta": "210510",
"icxx": "NT-000254"
},
{
"fsoport": "07/11/2013",
"fpago": "28/02/2014",
"msaldo": "5000000",
"ncuenta": "Proveedores",
"icuenta": "22050501",
"icxx": "FC-000667"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar)**: Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “paginación” y el arreglo de objetos “datos” los cuales serán descritos a continuación.  **paginación (json)**: contiene en su interior:  **totalpaginas:** Es el total de páginas que calcula el Agente con base a la cantidad de registros y la cantidad a mostrar por página. **totalregistros:** Es el total de registros que hay disponibles para el seleccionador.  **datos (arreglo de objetos):** contiene en su interior objetos cuyas llaves son los campos de retorno y su valor es el valor que arrojó la consulta de cada campo. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 90: No se ingresÃ³ la cantidad de registros a retornar.
- 100: El nÃºmero de la pÃ¡gina es incorrecto.
- 110: No se ingresaron los parÃ¡metros de paginaciÃ³n.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
