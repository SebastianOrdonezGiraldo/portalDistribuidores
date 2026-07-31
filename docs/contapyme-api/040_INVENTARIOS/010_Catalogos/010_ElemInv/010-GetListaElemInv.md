# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/010_Catalogos/010_ElemInv/010-GetListaElemInv.html

Â¿CÃ³mo obtener la lista de los elementos del inventario?

GetListaElemInv (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la información de un listado de elementos de inventario registrados en el sistema.  
Esta función retorna para cada elemento de inventario la información que sea solicitada, se debe tener en cuenta que el Agente retornará solo los elementos de inventario que tengan activa la opción de “Visible en internet (programa Agente)”.

#### Resultado

Retorna un Json con el listado de elementos de inventario

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
| dataJSON | JSON | Este Json contiene en su interior la siguiente estructura.  **datospagina**: Json que contiene: **cantidadregistros**: cantidad de registros a retornar por petición (requerido) **pagina:** número de página desde la cual se retornará la información (requerido)  **camposderetorno**: Arreglo que contiene los nombres de los campos de los cuales se desea obtener la información. (requerido). Para conocer los nombres de los campos que se pueden solicitar en la petición, consultar el documento “InfoBasica” que se encuentra en la zona de “Documentación de apoyo”.  Si desea obtener el saldo fÃ­sico del producto en determinada bodega, agregue el parÃ¡metro "msaldo" en los campos de retorno. **datosfiltro**: Json que contiene la información por la cual se filtrará la búsqueda de elementos de inventario. Los campos que se pueden utilizar como filtro los puede encontrar en el documento “InfoBasica” que se encuentra en la zona de “Documentación de apoyo”. Algunos campos que aplican como parámetros de filtro y no como parámetros de retorno son:  **Idnrecurso**: Este parámetro de filtro nos permite buscar por irecurso y nrecurso al mismo tiempo. (opcional).  En datosfiltro también se puede enviar el parámetro “sql”, el cual contiene el fragmento Where de una consulta SQL que se desea agregar a la consulta principal, por ejemplo, si se desean obtener todos los elementos de inventario que en el nombre contengan la palabra “**computador**” y que a su vez sean productos o servicios, datosfiltro tendría la siguiente estructura:  "**datosfiltro**":{"sql":"nrecurso like '%computador%' and (bproducto = 'T' or bservicio = 'T')"}  **ilistapreciosdef**: Código de la lista de precios que se usará para retornar el precio de cada elemento de inventario. (opcional).  **isede:** Código de la sede, este campo es requerido cuando el nivel de precios se maneja a nivel de sedes.  **filtroletra**: Este parámetro hace una consulta de tipo like sobre el campo nrecurso y retorna todos los elementos de inventario cuyo nombre inicie con esa letra. Recibe sólo una letra. Si la letra es “a” es equivalente a like de la siguiente forma: like 'a%'. (opcional).  **ordenarpor**: Json que contiene la información por la cual se va a ordenar la consulta, se pueden ordenar tanto de forma ascendente como descendente, los campos por los cuales se puede ordenar la consulta los puede encontrar en el documento “InfoBasica” que se encuentra en la zona de “Documentación de apoyo”. (opcional)  Para obtener el saldo fÃ­sico de los productos de una bodega en particular, se debe enviar el parÃ¡metro "ibodega" y la fecha de consulta de los saldos en el parÃ¡metro "fsoport". | { "datospagina": { "cantidadregistros": "20", "pagina": "1" }, "camposderetorno": [ "irecurso", "nrecurso", "nunidad", "msaldo" ], "datosfiltro": { }, "ilistapreciosdef": "1", "filtroletra": "a", "ordenarpor": { "nrecurso": "asc" }, "ibodega":"1", "fsoport":"11/26/2020" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatElemInv/"GetListaElemInv"/';
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
"irecurso",
"nrecurso",
"nunidad",
"msaldo"
],
"datosfiltro": {
},
"ilistapreciosdef": "1",
"filtroletra": "a",
"ordenarpor": {
"nrecurso": "asc"
},
"ibodega":"1",
"fsoport":"11/26/2020"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/010-GetListaElemInv.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "23"
},
"respuesta": {
"paginacion": {
"totalpaginas": "26",
"totalregistros": "520"
},
"datos": [
{
"irecurso": "0109010",
"nrecurso": "Computador portÃ¡til",
"nunidad": "Und",
"ibodega": "1",
"msaldo": "25"
},
{
"irecurso": "0109020",
"nrecurso": "Disco duro 120 Gb",
"nunidad": "Und",
"ibodega": "1",
"msaldo": "10"
},
{
"irecurso": "0109030",
"nrecurso": "Memoria USD",
"nunidad": "Und",
"ibodega": "1",
"msaldo": "6"
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
| respuesta | JSON | Json que contiene en su interior el objeto “paginación” y el arreglo de objetos “datos” los cuales serán descritos a continuación.  **paginación** (**json**): contiene en su interior:  **totalpaginas**: Es el total de páginas que calcula el Agente con base a la cantidad de registros y la cantidad a mostrar por página. **totalregistros**: Es el total de registros que hay disponibles para el seleccionador.  **datos** (**arreglo** **de** **objetos**): contiene en su interior objetos cuyas llaves son los campos de retorno y su valor es el valor que arrojo la consulta de cada campo. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 80: No se ingresaron los campos de retorno.
- 90: No se ingresÃ³ la cantidad de registros a retornar.
- 100: El nÃºmero de la pÃ¡gina es incorrecto.
- 110: No se ingresaron los parÃ¡metros de paginaciÃ³n.
- 130: No se ingresÃ³ el parÃ¡metro "X".

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
