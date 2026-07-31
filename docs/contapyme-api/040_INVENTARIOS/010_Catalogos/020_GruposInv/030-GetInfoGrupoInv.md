# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/010_Catalogos/020_GruposInv/030-GetInfoGrupoInv.html

Â¿CÃ³mo verificar la informaciÃ³n de un grupo del inventario?

GetInfoGrupoInv (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Función encargada de retornar la información de un grupo de inventario.

Existen 3 formas de obtener la información de un grupo:

- Obtener toda la información del grupo de inventario: Para ello se envía solo el código del grupo.
- Obtener la información del grupo de inventario pero de una sección (es) en particular: Para ello se envía el código del grupo y el nombre de la sección de la cual se desea obtener la información.
- Obtener la información de una sección en particular y solo unos campos de la sección: Para ello se envía el código del grupo de inventario, el nombre de la sección y el nombre de los campos que desean obtener

#### Resultado

Retorna un Json con la informaciÃ³n de un grupo de inventario.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura:  **igrupoinv:** Identificador único del grupo de inventario. (requerido)  **secciones:** Json que contiene los nombres de las secciones de información que se desean obtener, dentro de cada sección es posible solicitar datos particulares.  Las secciones que se pueden obtener son:  **"infobasica"** => Retorna la información básica del grupo de inventario y la configuración de cuentas e impuestos que tiene asignados el grupo de inventario, la documentación de los campos que retorna esta sección los puede encontrar en el documento “InfoBasica” que se encuentra en la zona “Documentación de apoyo”.  **"listacuentasegresos"** => Retorna la información de las cuentas de egreso que se han configurado según la clase contable del centro de costos, esto aplica cuando además de tener la cuenta de egresos del grupo de inventario definida, se configuran otras cuentas para que los egresos se imputen a ellas según la clase contable del centro que se afecta.  La documentación de los campos que retorna esta sección los puede encontrar en el documento “InfoCuentasIngresosEgresos” que se encuentra en la zona “Documentación de apoyo”.  **"listacuentasingresos"** => Retorna la información de las cuentas de ingreso que se han configurado según la clase contable del centro de costos, esto aplica cuando además de tener la cuenta de ingresos del grupo de inventario definida, se configuran otras cuentas para que los ingresos se imputen a ellas según la clase contable del centro que se afecta.  La documentación de los campos que retorna esta sección los puede encontrar en el documento “InfoCuentasIngresosEgresos” que se encuentra en la zona “Documentación de apoyo”. | { "igrupoinv": "016025" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

Solicitar InformaciÃ³n
Solicitar InformaciÃ³n basica
Solicitar datos personales

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatGrupoInv/"GetInfoGrupoInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"igrupoinv": "016025"
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
var URLFuncion = '/datasnap/rest/TCatGrupoInv/"GetInfoGrupoInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"igrupoinv": "016025",
"secciones": {
"infobasica": [
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
var URLFuncion = '/datasnap/rest/TCatGrupoInv/"GetInfoGrupoInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"igrupoinv": "016025",
"secciones": {
"infobasica": [
"ngrupo",
"icuentaegr",
"icuentavta"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/030-GetInfoGrupoInv.pdf)

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
"datos": {
"infobasica": {
"igrupoinv": "016025",
"ngrupo": "EQUIPOS",
"bcontrolinv": "T",
"icuentacostos": "613554",
"bconsumo": "F",
"icuentaegr": "710101",
"bventa": "T",
"bproducto": "T",
"bservicio": "T",
"icuentavta": "413554",
"iconceptocompra1": "IVAC",
"iconceptocompra2": "RETCOMPG",
"iconceptocompra3": "",
"iconceptocompra4": "",
"iconceptoventa1": "IVAV16V",
"iconceptoventa2": "RETVTAG",
"iconceptoventa3": "",
"iconceptoventa4": "",
"bicuentasporclaseegr": "F",
"bicuentasporclasein g ": " F ",
"iws ": " PC - MCIFUENTES ",
"fcreacion ": " 09 / 02 / 2005 11: 52: 00 ",
"iwsult": "PC-MCIFUENTES",
"fultima": "12/05/2013 14:40:53",
"iusuario": "ADMIN",
"iusuarioult": "ADMIN",
"qregtrasladoegr": "0",
"qregtrasladoing": "0",
"bcuentainv": "",
"icuentainv": "",
"iconceptoventa5": "RCREE",
"bccporbodega": ""
},
"listacuentasegresos": [
{
"igrupoinv": "A26TT",
"itipocuenta": "E",
"itdcc": "3",
"icuenta": "410520",
"fultima": "07/22/2013 10:46:37",
"ntdcc": "ProducciÃ³n",
"ncuenta": "Cultivo de cafÃ©"
},
{
"igrupoinv": "A26TT",
"itipocuenta": "E",
"itdcc": "0",
"icuenta": "410525",
"fultima": "07/22/2013 10:46:32",
"ntdcc": "",
"ncuenta": "Cultivo de flores"
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
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje** (**varchar**): Código del mensaje de eventualidad o error en caso de presentarse. **mensaje** (**varchar**): Mensaje de eventualidad o error en caso de presentarse. **tiempo** (**varchar**): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior cada uno de los campos con la respectiva información del grupo de inventario solicitado. Para conocer la descripción de cada campo, consultar la documentación de “InfoGrupoInv” |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 50: El usuario no posee permisos para: âabrir el catÃ¡logo de grupos de inventariosâ.
- 130: No se ingresÃ³ el parÃ¡metro "X".

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
