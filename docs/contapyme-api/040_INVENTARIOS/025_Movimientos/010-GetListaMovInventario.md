# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/025_Movimientos/010-GetListaMovInventario.html

Â¿CÃ³mo obtener la lista de un movimiento del inventario?

GetListaMovInventario (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la información de los movimientos de inventarios registrados en el sistema. Retornará por cada movimiento la información que sea solicitada.  
Esta función aplica seguridad de datos ya que pueden haber terceros, elementos de inventario y centros de costos que el usuario no pueda visualizar.  
Esta función se debe utilizar cuando se quieren obtener los movimientos de inventarios contables como compras, ventas, ajustes de inventarios entre otros.  
No incluye los movimientos de inventarios plus, como: Pedidos, Remisiones, Recepción de materiales, órdenes de compra, entre otros.

#### Resultado

Retorna un Json con el listado de los movimientos de inventarios (corresponde al inventario contable).

Seguridad

Aplica todas las configuraciones de seguridad de datos y de seguridad de acciones de ContaPyme / AgroWin.

Compatibilidad de la API

FunciÃ³n disponible desde ContaPyme/AgroWin VersiÃ³n 4 - Release 7.

## PeticiÃ³n

Requisitos

Debe haber realizado el logueo en el agente a travÃ©s de la funciÃ³n [GETAUTH().](../../005_INTRODUCCION/020-GetAuth.html)

ParÃ¡metros

| Nombre parÃ¡metro | Tipo | DescripciÃ³n | Ejemplo |
| --- | --- | --- | --- |
| dataJSON | JSON | Este Json contiene en su interior la siguiente estructura:Este Json contiene en su interior la siguiente **estructura:** **datospagina:** Json que contiene: **cantidadregistros:** cantidad de registros a retornar por página **(opcional)pagina:** número de página desde la cual se retornará la información (opcional)Si no se envía ninguno de estos campos el sistema toma sus valores por defecto los cuales son 20 registros y la página **1.** **camposderetorno:** Arreglo que contiene los nombres de los campos de los cuales se desea obtener la información, si no se envía ningún campo el sistema retorna todos los campos (opcional).Los nombres de los campos que se pueden solicitar en la petición los podrá encontrar en el anexo 1 del **documento.** **datosfiltro:** Json que contiene la información por la cual se desea filtrar la búsqueda, los campos que se pueden utilizar como filtro los puede encontrar en el anexo 1 del documento.(opcional). En datosfiltro también se puede enviar el parámetro “sql”, el cual contiene el fragmento Where de una consulta SQL que se desea agregar a la consulta principal, por ejemplo, si se desean obtener todos los movimientos de inventarios contable entre un rango de fechas y de un tercero dado, datosfiltro tendría la siguiente estructura: "datosfiltro":{"sql":"init = '63344538' fsoport between '01/01/2013' and '30/11/2014'"} ordenarpor: Json que contiene la información por la cual se va a ordenar la consulta, se pueden ordenar tanto de forma ascendente como descendente, los campos por los cuales se puede ordenar la consulta los puede encontrar en el anexo 1 del documento.(opcional) | { "datospagina": { "cantidadregistros": "20", "pagina": "1" }, "camposderetorno": [ "itdoper", "snumsop", "init", "initvendedor" ], "datosfiltro": { "sql": "init = '63344538' and fsoport between '06/01/2013' and '06/25/2013'" }, "ordenarpor": { "fsoport": "asc" } } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TInventarios/"GetListaMovInventario"/';
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
"snumsop",
"init",
"initvendedor"
],
"datosfiltro": {
"sql": "init = '63344538' and fsoport between '06/01/2013' and '06/25/2013'"
},
"ordenarpor": {
"fsoport": "asc"
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
[PHP](../../ejemplo/PHP.html) ,
[JAVA](../../ejemplo/JAVA.html),
[C#](../../ejemplo/Csharp.html),
[Visual Basic.net](../../ejemplo/visualBasic.html),
[Visual Basic 6](../../ejemplo/visualBasic6.html),
[Delphi.](../../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/010-GetListaMovInventario.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "2618"
},
"respuesta": {
"paginacion": {
"totalpaginas": "3",
"totalregistros": "58"
},
"datos": [
{
"itdoper": "ING1",
"snumsop": "FV-011207",
"init": "63344538",
"initvendedor": "1"
},
{
"itdoper": "ING1",
"snumsop": "FV-011207",
"init": "63344538",
"initvendedor": "1"
},
{
"itdoper": "ING1",
"snumsop": "FV-011208",
"init": "63344538",
"initvendedor": "1"
},
{
"itdoper": "ING1",
"snumsop": "FV-011208",
"init": "63344538",
"initvendedor": "1"
},
{
"itdoper": "ING1",
"snumsop": "FV-011229",
"init": "63344538",
"initvendedor": "1"
},
{
"itdoper": "ING1",
"snumsop": "FV-011229",
"init": "63344538",
"initvendedor": "1"
},
{
"itdoper": "ING1",
"snumsop": "FV-011298",
"init": "63344538",
"initvendedor": "1"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje** (**varchar**): Código del mensaje de eventualidad o error en caso de presentarse. **mensaje** (**varchar**): Mensaje de eventualidad o error en caso de presentarse. **tiempo** (**varchar**): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “paginación” y el arreglo de objetos “datos” los cuales serán descritos a continuación.  **paginación (json):** contiene en su interior:  **totalpaginas:** Es el total de páginas que calcula el Agente con base a la cantidad de registros y la cantidad a mostrar por **página.** **totalregistros:** Es el total de registros que hay disponibles para mostrar en el catálogo.  **datos (arreglo de objetos):** contiene en su interior objetos cuyas llaves son los campos de retorno y su valor es el valor que arrojó la consulta de cada campo. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
