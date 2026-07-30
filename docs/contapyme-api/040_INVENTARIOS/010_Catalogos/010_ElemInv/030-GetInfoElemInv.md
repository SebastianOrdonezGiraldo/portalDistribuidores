# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/010_Catalogos/010_ElemInv/030-GetInfoElemInv.html

Â¿CÃ³mo obtener la informaciÃ³n del elemento enviado?

GetInfoElemInv (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Función encargada de retornar la información de un elemento de inventario. Existen 3 formas de obtener la información de un elemento:

- Obtener toda la información del elemento de inventario: Para ello se envía solo el código del elemento.
- Obtener la información del elemento de inventario pero de una sección (es) en particular: Para ello se envía el código del elemento y el nombre de la sección de la cual se desea obtener la información.
- Obtener la información de una sección en particular y solo unos campos de la sección: Para ello se envía el código del elemento de inventario, el nombre de la sección y el nombre de los campos que desean obtener

#### Resultado

Retorna un Json con la informaciÃ³n de un elemento de inventario.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **irecurso:** Identificador único del elemento de inventario. (requerido)  **secciones:** Json que contiene los nombres de las secciones de información que se desean obtener, dentro de cada sección es posible solicitar datos particulares.  Las secciones que se pueden obtener son:  **"infobasica" =>** Retorna la información básica del elemento de inventario, la documentación de los campos que retorna esta sección los puede encontrar en el documento “InfoBasica” que se encuentra en la zona de “Documentación de apoyo”.  **"parteseleminv" =>** Retorna la información de las partes que componen un elemento, aplica cuando el elemento es un compuesto. La documentación de los campos que retorna esta sección los puede encontrar en el documento “PartesElemInv” que se encuentra en la zona de “Documentación de apoyo”.  **"stockymargen" =>** Retorna la información de stock y margen de utilidad del elemento de inventario. La documentación de los campos que retorna esta sección los puede encontrar en el documento “StockYMargen” que se encuentra en la zona de “Documentación de apoyo”  **"listaprecios" =>** Retorna la información de las listas de precios que tiene asignadas un elemento de inventario. La documentación de los campos que retorna esta sección los puede encontrar en el documento “ListaPrecios” que se encuentra en la zona de “Documentación de apoyo”.  **"listaproductosequivalentes" =>** Retorna la información de los productos que son equivalentes al elemento de inventario. La documentación de los campos que retorna esta sección los puede encontrar en el documento “ListaProductosEquivalentes” que se encuentra en la zona de “Documentación de apoyo”. | { "irecurso": "06120", "secciones": { "infobasica": [ ], "listaprecios": [ ] } } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

Solicitar InformaciÃ³n
Solicitar InformaciÃ³n basica
Solicitar datos personales

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatElemInv/"GetInfoElemInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"irecurso": "06120"
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
var URLFuncion = '/datasnap/rest/TCatElemInv/"GetInfoElemInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"irecurso": "06120",
"secciones": {
"infobasica": [
],
"listaprecios": [
]
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
var URLFuncion = '/datasnap/rest/TCatElemInv/"GetInfoElemInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"irecurso": "06120",
"secciones": {
"infobasica": [
"nrecurso",
"sdescrip",
"sreffabricante"
]
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/030-GetInfoElemInv.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "120"
},
"respuesta": {
"datos": {
"infobasica": {
"irecurso": "06120",
"nrecurso": "DISCO DURO 120 GB",
"nunidad": "Und",
"igrupoinv": "G0010",
"besalias": "F",
"irecursobase": "",
"bcontrolinv": "T",
"bconsumo": "T",
"bventa": "T",
"bproducto": "T",
"bservicio": "F",
"bvisible": "T",
"bvisibleinternet": "T",
"bdescfinanciero": "F",
"bescomisionable": "F",
"bcodbarras": "T",
"sdescrip": "de 7200 RPM",
"nunidadcompra": "Und",
"qfactor": "1",
"balquiler": "F",
"sreffabricante": "DD234234",
"smarca": "",
"idepinv": "",
"clase1": "DISCOS DUROS",
"sobservaciones": "",
"bcompuesto": "F",
"itddescarga": "E",
"bafectacuentapadre": "F",
"itdfactcompuesto": "C",
"itdcotizcompuesto": "C",
"bcuentainv": "F",
"icuentainv": "140501",
"bcuentadevcompra": "F",
"icuentadevcompra": "529595",
"bcuentadevventa": "F",
"icuentadevventa": "417501",
"bcuentacostos": "F",
"icuentacostos": "613554",
"bcuentaegr": "F",
"icuentaegr": "710101",
"bicuentasporclaseegr": "F",
"bcuentavta": "F",
"icuentavta": "413554",
"bicuentasporclaseing": "F",
"bconceptocompra": "F",
"iconceptocompra1": "IVAC",
"iconceptocompra2": "RETCOMAF",
"iconceptocompra3": "",
"iconceptocompra4": "",
"bconceptoventa": "F",
"qregpartescompuesto": "0",
"qregtrasladoegr": "0",
"qregtrasladoing": "0",
"bexigedato1": "F",
"bexigedato2": "F",
"bexigedato3": "F",
"bexigedato4": "F",
"bexigedato5": "F",
"bexigedato6": "F",
"bexigevalor1": "F",
"bexigevalor2": "F",
"iws": "",
"fcreacion": "05/04/2007 08:27:26",
"iusuario": "",
"iwsult": "",
"fultima": "12/05/2013 14:40:53",
"iusuarioult": "ADMIN",
"bivamayorvalor": ""
},
"stockymargen": [
{
"iemp": "0",
"isede": "",
"irecurso": "06120",
"qstockmin": "10",
"qstockmax": "30",
"pmargen1": "10",
"pmargen2": "20",
"pmargen3": "5",
"subicacion": "Bodega de materiales",
"qtreposicion": "",
"fultima": "",
"iwsult": "",
"iusuarioult": "",
"pincremento1": "",
"pincremento2": "",
"pincremento3": ""
}
],
"listaprecios": [
{
"iemp": "0",
"isede": "",
"irecurso": "06120",
"ilista": "1",
"imetodo": "2",
"mprecio": "115000",
"fasignacion": "",
"fvigenciahasta": "12-31-2012"
},
{
"iemp": "0",
"isede": "",
"irecurso": "06120",
"ilista": "2",
"imetodo": "1",
"mprecio": "276000",
"fasignacion": "",
"fvigenciahasta": ""
},
{
"iemp": "0",
"isede": "",
"irecurso": "06120",
"ilista": "3",
"imetodo": "2",
"mprecio": "1650000",
"fasignacion": "",
"fvigenciahasta": ""
},
{
"iemp": "0",
"isede": "",
"irecurso": "06120",
"ilista": " 4 ",
"imetodo": "4",
"mprecio": "150000",
"fasignacion": "",
"fvigenciahasta": ""
}
]
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar)**: Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior cada uno de los campos con la respectiva información del elemento de inventario solicitado. Para conocer la descripción de cada campo, consultar la documentación de “InfoElemInv” |

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
