# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/090-GetSQL.html

Â¿CÃ³mo obtener el SQL?

GetSQL (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de ejecutar un SQL de tipo “Select” directamente en la base de datos, a través de esta función se puede obtener la información de cualquier tabla del sistema. Para poder ejecutar esta función es necesario tener conocimiento del modelo entidad relación del sistema, es decir, conocer los nombres de las tablas, campos y relaciones entre tablas.  
Para poder ejecutar esta función se debe contar con un permiso específico, el cual se configura en el sistema ContaPyme entrando por la pestaña Básico – Móvil – Perfiles de seguridad para clientes móviles – Usuario a configurar – API abierta (licencia desarrollador) – Opciones.

#### Resultado

Retorna un Json con la informaciÃ³n de solicitada a travÃ©s de la consulta Select.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente información:  **sql:** contiene la consulta SQL de tipo “Select” que se desea ejecutar para obtener información. | { "sql": "select \* from abanits where ABANITS.init='810000630'" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TBasicoGeneral/"GetSql"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"sql": "select \* from abanits where ABANITS.init='810000630'"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/090-GetSQL.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "58"
},
"respuesta": {
"datos": [
{
"init": "1053814720",
"ntercero": "Paola",
"idigchequeo": "",
"bempresa": "F",
"napellido": "Sanchez Lopez",
"itddocum": "13",
"botrotercero": "F",
"itdotrotercero": "",
"bproveedor": "F",
"itdproveedor": "",
"bcliente": "T",
"itdcliente": "8",
"bvendedor": "T",
"itdvendedor": "5",
"bempleado": "T",
"itdempleado": "4",
"ncomercial": "",
"bncomercial": "F",
"isexo": "F",
"fnacimiento": "09-28-1991",
"stratamiento": "SeÃ±orita",
"sprofesion": "Administrador de empresas",
"nempresa": "MP Computadores",
"scargo": "Administrador",
"swww": "",
"semail": "psanchez@mpcomputadores.com",
"smsn": "",
"sskype": "p.sanchez25",
"bvisible": "T",
"sclasiflegal": "PN;RS;TI",
"ipais": "169",
"idep": "17",
"imun": "001",
"tdireccion": "Cra 23 # 5 - 21",
"sbarrio": "Centro",
"ttelefono": "036 8947742",
"ttelefono2": "885 0854",
"ttelefono3": "",
"tcelular": "3207452110",
"tfax": "",
"itdcategoria": "",
"itdtercero": "",
"scodigoalterno": "",
"nzona": "",
"clase1": "",
"clase2": "",
"dato1": "",
"dato2": "",
"qvalor1": "",
"qvalor2": "",
"sobservaciones": "",
"ivendedor": "10532588",
"iactividadeconomica": "2",
"itiponegocio": "",
"bperfil": "T",
"iperfil": "",
"ilistaprecios": "1",
"icccliente": "",
"pdescuentoventasfijo": "",
"berrorexcesocupocredito": "F",
"qdiasplazocxc": "30",
"bbloquearcreditos": "F",
"mcupocredito": "",
"iccempleado": "1120",
"iautorizacionblqcreditos": "",
"ictanombre": "",
"ictanumero": "",
"ictaformapago": "",
"ictanombre2": "",
"ictanumero2": "",
"ictaformapago2": "",
"bbloquearpagos": "F",
"iautorizacionblqpagos": "",
"bmarcadian": "F",
"berrdian": "F",
"calc\_ipaisidep": "16917",
"calc\_ipaisidepimun": "16917001",
"iws": "PC-AJIMENEZ",
"fcreacion": "02/28/2011 11:56:13",
"iwsult": "PC-PALZATE",
"fultima": "06/07/2014 12:07:19",
"iusuario": "CIDARRAGA",
"iusuarioult": "ADMIN",
"calc\_nterceronapellido": "Paola Sanchez Lopez",
"calc\_napellidontercero": "Sanchez Lopez Paola",
"qregdirecciones": "0",
"qregcontactos": "3",
"qregdatosvendedor": "0",
"qreglineas": "0",
"qregproductos": "0",
"qregnomcnt": "0",
"qregentidades": "0",
"isucursal": "",
"scodigopostal": "",
"binteresado": "F",
"itdinteresado": "",
"bverifyvencimientomaximocxc": "F",
"qdiasvencimientomaximocxc": "1",
"berrorvencimientomaximocxc": "F",
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
"bautorizaenvioemail": "F",
"qnumfallos": "",
"bplataformaweb": "T",
"spassword": "b48fd460ca5924b8f5553d3e95cd4c50",
"bregistroweb": "F",
"itdregistroweb": "",
"ibanco": "",
"itdctabanco": "",
"ibanco2": "",
"itdctabanco2": "",
"iacteconomica": "",
"qdiasplazocxp": ""
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
| respuesta | JSON | Json que contiene en su interior el objeto “datos” el cual posee la información solicitada en la consulta SQL que se envía en la petición. . |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
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
