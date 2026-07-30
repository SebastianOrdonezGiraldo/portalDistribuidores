# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/120-GetListaTitulos.html

Â¿CÃ³mo obtener la lista de los titulos?

GetListaTitulos (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar los valores para las listas de selección del sistema, como por ejemplo: Tipos de unidad de medida, Clases de operaciones, Tipos de documentos de los terceros, entre otros.  
Estos listados de selección están almacenados en el sistema como tablas virtuales, es decir, tabla de tablas. En una tabla de la base de datos se almacena información de muchas tablas, esto aplica cuando las tablas son pequeñas.  
Las listas de selección pueden tener código y nombre o pueden contener otros valores adicionales, para conocer la información de cada lista de selección disponible en el sistema, consulte el “Anexo 1” de este documento.

#### Resultado

Retorna un Json con los valores para las listas de selecciÃ³n del sistema.

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
| dataJSON | JSON | Este Json contiene en su interior la siguiente estructura:  **datospagina:** Json que contiene: cantidadregistros: cantidad de registros a retornar por página (opcional) pagina: número de página desde la cual se retornará la información (opcional)  Si no se envía ninguno de estos campos el sistema toma sus valores por defecto los cuales son 20 registros y la página 1.  **camposderetorno:** Arreglo que contiene los nombres de los campos de los cuales se desea obtener la información, si no se envía ningún campo el sistema retorna todos los campos (opcional). Para conocer los nombres de los campos que se pueden solicitar en la petición, consultar el “Anexo 1” de este documento.  **itabla:** Nombre de lista de selección de la cual se desean solicitar sus valores. Para conocer los “itabla” disponibles en el sistema consulte el “Anexo 1” de este documento. (opcional)  **ordenarpor:** Json que contiene la información por la cual se va a ordenar la consulta, se pueden ordenar tanto de forma ascendente como descendente, los campos por los cuales se puede ordenar la consulta los puede encontrar en el “Anexo 1” de este documento (opcional) | { "datospagina": { "cantidadregistros": 999, "pagina": 1 }, "camposderetorno": [ "ititulo", "ntitulo", "b" ], "itabla": "ABATDDOC", "ordenarpor": { "ititulo": "asc" } } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatTitulos/"GetListaTitulos"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"datospagina": {
"cantidadregistros": 999,
"pagina": 1
},
"camposderetorno": [
"ititulo",
"ntitulo",
"b"
],
"itabla": "ABATDDOC",
"ordenarpor": {
"ititulo": "asc"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/120-GetListaTitulos.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "46"
},
"respuesta": {
"paginacion": {
"totalpaginas": "1",
"totalregistros": "10"
},
"datos": [{
"ititulo": "13",
"ntitulo": "CÃ©dula",
"b": ""
}, {
"ititulo": "22",
"ntitulo": "CÃ©dula de extranjerÃ­a",
"b": ""
}, {
"ititulo": "31",
"ntitulo": "Nit",
"b": "T"
}, {
"ititulo": "42",
"ntitulo": "Tipo de documento extranjero",
"b": ""
}, {
"ititulo": "43",
"ntitulo": "Sin identificaciÃ³n del exterior",
"b": ""
}, {
"ititulo": "90",
"ntitulo": "RUC",
"b": ""
}, {
"ititulo": "96",
"ntitulo": "RNC",
"b": "N"
}, {
"ititulo": "97",
"ntitulo": "Registro de informaciÃ³n fiscal - RIF",
"b": ""
}, {
"ititulo": "98",
"ntitulo": "RFC",
"b": "N"
}, {
"ititulo": "99",
"ntitulo": "CÃ³digo interno",
"b": ""
}]
}
}]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. tiempo (varchar): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “paginación” y el arreglo de objetos “datos” los cuales serán descritos a continuación.  **paginación (json):** contiene en su interior:  **totalpaginas:** Es el total de páginas que calcula el Agente con base a la cantidad de registros y la cantidad a mostrar por página. **totalregistros:** Es el total de registros que hay disponibles para mostrar en el listado.  **datos (arreglo de objetos):** contiene en su interior objetos cuyas llaves son los campos de retorno y su valor es el valor que arrojó la consulta de cada campo. |

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
