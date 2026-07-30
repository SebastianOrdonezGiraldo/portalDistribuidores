# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/030-GetInfoCuenta.html

DocumentaciÃ³n API.



Â¿CÃ³mo verificar la informaciÃ³n de la cuenta?

GetInfoCuenta (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Función encargada de retornar la información de una cuenta. Existen 3 formas de obtener la información de una cuenta:

- Obtener toda la información de la cuenta: Para ello se envía solo el código de la cuenta.
- Obtener la información de la cuenta pero de una sección (es) en particular: Para ello se envía el código de la cuenta y el nombre de la sección de la cual se desea obtener la información.
- Obtener la información de la cuenta de una sección en particular y solo unos campos de la sección: Para ello se envía el código de la cuenta, el nombre de la sección y el nombre de los campos que se desean obtener.

#### Resultado

Retorna un Json con la informaciÃ³n de una cuenta.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **icuenta:** Identificador único de la cuenta. (requerido)  **secciones**: Json que contiene los nombres de las secciones de información que se desean obtener, dentro de cada sección es posible solicitar datos particulares.  Las secciones que se pueden obtener son:  **"infobasica" =>** Retorna la información básica de la cuenta, la documentación de los campos que retorna esta sección los puede encontrar en el documento “InfoBasica" que se encuentra en la zona de “Documentación de apoyo”  **"conceptosnominacontable" =>** Retorna la información de los conceptos de nómina contable que la cuenta tiene configurados, esto para cuando la clase de la cuenta es “De nómina contable”. La documentación de los campos que retorna esta sección los puede encontrar en el documento “ConceptosNominaContable" que se encuentra en la zona de “Documentación de apoyo”. | { "icuenta": "110505", "secciones": { "infobasica": [ ] } } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript[Ir arriba](#arriba)

Solicitar InformaciÃ³n
Solicitar InformaciÃ³n basica
Solicitar datos personales

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatPlanCuentas/"GetInfoCuenta"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"icuenta": "110505"
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
var URLFuncion = '/datasnap/rest/TCatPlanCuentas/"GetInfoCuenta"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"icuenta": "110505",
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
var URLFuncion = '/datasnap/rest/TCatPlanCuentas/"GetInfoCuenta"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"icuenta": "110505",
"secciones": {
"infobasica": [
"ncuenta",
"itdcuenta",
"iexigeterc"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/030-GetInfoCuenta.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "27"
},
"respuesta": {
"datos": {
"infobasica": {
"icuenta": "110505",
"ncuenta": "Cajageneral",
"blocal": "T",
"bniif": "",
"ncuentaniif": "",
"icuentaniif": "",
"icuentaalterna": "",
"itdcuenta": "1",
"inivel": "4",
"iclase": "2",
"isubclase": "",
"bvisible": "T",
"iexigeterc": "0",
"bexigeicc": "F",
"bsedexdefecto": "F",
"bexigeactivo": "F",
"bcontrolacxx": "F",
"bmanejacuotas": "F",
"bmanejatercero": "F",
"btemporalanio": "F",
"bafecdtmte": "T",
"bajustarxinf": "F",
"bdisponiblegi": "F",
"iexigebase": "0",
"bautoactivar": "F",
"bmanejaotramoneda": "F",
"imoneda": "",
"idescuento": "",
"icargo": "",
"icargo2": "",
"icargo3": "",
"sobserv": "",
"ipadre": "1105",
"bexigevalor1": "F",
"bexigevalor2": "F",
"bexigeclase1": "F",
"bexigeclase2": "F",
"iusuarioult": "",
"iusuario": "",
"fultima": "12/05/201314:40:41",
"iwsult": "",
"fcreacion": "03/28/200815:47:56",
"iws": "",
"qregdconc": "",
"isucursal": "",
"icargo4": "",
"icargo5": "",
"bppto": "",
"icuentaorigen": "",
"ntdcuenta": "Activo",
"nclase": "Deefectivo",
"nsubclase": ""
}
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior cada uno de los campos con la respectiva información de la cuenta solicitada. Para conocer la descripción de cada campo, consultar la documentación de “InfoPlanDeCuentas” |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 50: El usuario no posee permisos para: "abrir el catÃ¡logo de plan de cuentas".
- 130: No se ingresÃ³ el parÃ¡metro "X".
- 250: No se tiene permisos sobre el registro por seguridad de datos, por lo tanto no se puede ejecutar la acciÃ³n.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
