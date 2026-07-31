# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/030-GetInfoTercero.html

Â¿CÃ³mo obtener la informaciÃ³n de terceros?

GetInfoTercero (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Función encargada de retornar la información de un tercero. Existen 3 formas de obtener la información de un tercero:

- Obtener toda la información del tercero: Para ello se envía solo el código del tercero.
- Obtener la información del tercero pero de una sección (es) en particular: Para ello se envía el código del tercero y el nombre de la sección de la cual se desea obtener la información.
- Obtener la información del tercero de una sección en particular y solo unos campos de la sección: Para ello se envía el código del tercero, el nombre de la sección y el nombre de los campos que se desean obtener.

#### Resultado

Retorna un Json con la informaciÃ³n de un tercero.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura:  **init:** Identificador único del tercero. (requerido)  **secciones:** Json que contiene los nombres de las secciones de información que se desean obtener, dentro de cada sección es posible solicitar datos particulares.  Las secciones que se pueden obtener son: **"infobasica" =>** Retorna la información básica del tercero, la documentación de los campos que retorna esta sección se encuentra en el documento "InfoBasica" de la zona de “Documentación de apoyo”.  **"tipotercero" =>** Retorna los tipos de tercero que tenga asignados el tercero en el sistema, la documentación de los campos que retorna esta sección se encuentra en el documento "TipoTercero" de la zona de “Documentación de apoyo”.  **"listacontactos" =>** Retorna el listado de contactos asociados al tercero, la documentación de los campos que retorna esta sección se encuentra en el documento “ListaContactos" de la zona de “Documentación de apoyo”.  **"conceptosnominacontable" =>** Retorna el listado de conceptos de nómina contable que tiene asociados el tercero, la documentación de los campos que retorna esta sección se encuentra en el documento "ConceptosNominaContable" de la zona de “Documentación de apoyo”.  **"entidadesempleado" =>** Retorna el listado de entidades a las que está afiliado el tercero (cuando es de tipo empleado), la documentación de los campos que retorna esta sección se encuentra en el documento "EntidadesEmpleado" de la zona de “Documentación de apoyo”.  **"datosvendedor" =>** Retorna la información de datos vendedor, la documentación de los campos que retorna esta sección se encuentra en el documento "DatosVendedor" de la zona de “Documentación de apoyo”.  **"lineasproductos" =>** Retorna la información de las líneas de productos que tiene asociadas el tercero (cuando es de tipo proveedor), la documentación de los campos que retorna esta sección se encuentra en el documento "LineasProductos" de la zona de “Documentación de apoyo”  **"listaeleminvproveedor" =>** Retorna la información de los elementos de inventario que ofrece el proveedor, la documentación de los campos que retorna esta sección se encuentra en el documento "ListaElemInvProveedor" de la zona de “Documentación de apoyo”.  **"listadirecciones" =>** Retorna la información de las direcciones que tenga asociadas el tercero, la documentación de los campos que retorna esta sección se encuentra en el documento "ListaDirecciones" de la zona de “Documentación de apoyo”  **"listaproductoscomprados" =>** Retorna la información de los productos que se han comprado al proveedor, la documentación de los campos que retorna esta sección se encuentra en el documento "ListaProductosComprados" de la zona de “Documentación de apoyo” | { "init":"810000630" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

Solicitar InformaciÃ³n
Solicitar InformaciÃ³n basica
Solicitar datos personales

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatTerceros/"GetInfoTercero"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"init":"810000630"
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
var URLFuncion = '/datasnap/rest/TCatTerceros/"GetInfoTercero"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"init":"810000630",
"secciones": {
"infobasica":[],
"listacontactos":[]
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
var URLFuncion = '/datasnap/rest/TCatTerceros/"GetInfoTercero"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"init": "810000630",
"secciones": {
"infobasica": [
"ntercero",
"napellido",
"itddocum"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/030-GetInfoTercero.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "174"
},
"respuesta": {
"datos": {
"infobasica": {
"init": "10212121",
"ntercero": "ALBERTO GARCÃA",
"idigchequeo": "",
"bempresa": "F",
"napellido": "",
"itddocum": "13",
"isexo": "M",
"fnacimiento": "03-22-1979",
"stratamiento": "SeÃ±or",
"sprofesion": "Contador pÃºblico",
"nempresa": "Grupo empresarial",
"scargo": "Contador",
"swww": "",
"semail": "agarcia@todopcs.com",
"smsn": "",
"sskype": "",
"bvisible": "T",
"sclasiflegal": "PN;RS",
"ipais": "169",
"idep": "17",
"imun": "001",
"tdireccion": "Calle 45 # 21 - 03",
"sbarrio": "Centro",
"ttelefono": "8752369",
"tcelular": "3217581402",
"tfax": "",
"itdcategoria": "Cliente",
"itdtercero": "",
"scodigoalterno": "",
"sobservaciones": "",
"ivendedor": "14856296",
"iactividadeconomica": "",
"itiponegocio": "",
"bperfil": "F",
"iperfil": "",
"ilistaprecios": "",
"icccliente": "",
"pdescuentoventasfijo": "",
"berrorexcesocupocredito": "F",
"qdiasplazocxc": "",
"bbloquearcreditos": "F",
"mcupocredito": "",
"iccempleado": "",
"iautorizacionblqcreditos": "",
"bbloquearpagos": "F",
"iautorizacionblqpagos": "",
"bmarcadian": "F",
"berrdian": "F",
"calc\_ipaisidep": "16917",
"calc\_ipaisidepimun": "16917001",
"iws": "PC-MCIFUENTES",
"fcreacion": "07/02/2005 12:30:11",
"iwsult": "SERVER",
"fultima": "03/22/2014 11:37:49",
"iusuario": "ADMIN",
"iusuarioult": "PALZATE",
"qregdirecciones": "1",
"qregcontactos": "1",
"qregdatosvendedor": "0",
"qreglineas": "0",
"qregproductos": "0",
"calc\_nterceronapellido": "ALBERTO GARCÃA",
"calc\_napellidontercero": "ALBERTO GARCÃA",
"qregnomcnt": "0",
"qregentidades": "0",
"isucursal": "",
"bempleadonomina": "T",
"iingresossup": "",
"baportespension": "F",
"baportessalud": "F",
"baportesarp": "F",
"maportespension": "",
"maportespensionvol": "",
"maportessalud": "",
"maportesarp": "",
"mcuentasafc": "",
"binteresado": "F",
"itdinteresado": "",
"bverifyvencimientomaximocxc": "",
"qdiasvencimientomaximocxc": "",
"berrorvencimientomaximocxc": "",
"scodigopostal": "",
"bautorizaenvioemail": "T",
"qnumfallos": "",
"bplataformaweb": "F",
"bregistroweb": "F",
"itdregistroweb": "",
"ibanco": "",
"itdctabanco": "",
"ibanco2": "",
"itdctabanco2": "",
"npais": "COLOMBIA",
"ndep": "CALDAS",
"nmun": "MANIZALES",
"ndocumento": "CÃ©dula"
},
"tipotercero": [
{
"base": "2",
"codigo": "2"
}
],
"listacontactos": [
{
"init": "10212121",
"ilinea": "0",
"nnombre": "Paola",
"napellido": "GÃ³mez Restrepo",
"sobservaciones": " ",
"stratamiento": "SeÃ±ora",
"sprofesion": "",
"scargo": "",
"ttelefono": "8919874",
"tcelular": "3217698854",
"semail": "paolagr@gmail.com",
"smsn": "",
"sskype": "",
"sclasificador": "",
"fultima": "03/22/2014 10:37:37",
"isexo": "F",
"stipo": ""
}
],
"listadirecciones": [
{
"init": "10212121",
"ilinea": "0",
"nsucursal": "Sur",
"ipais": "169",
"idep": "17",
"imun": "001",
"tdireccion": "Calle 63 # 24-51",
"sbarrio": "Palogrande",
"ncontacto": "",
"ttelefono": "",
"tfax": "",
"tcelular": "",
"fultima": "03/22/2014 10:37:37"
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
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior cada uno de los campos con la respectiva información del tercero solicitado. Para conocer la descripción de cada campo, consultar la documentación de “InfoTercero” que se encuentra en la zona de “Documentación de apoyo”. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 50: El usuario no posee permisos para: âabrir el catÃ¡logo de tercerosâ.
- 130: No se ingresÃ³ el parÃ¡metro "X".

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
