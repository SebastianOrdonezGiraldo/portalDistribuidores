# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/050-SetInfoCuenta.html

Â¿CÃ³mo establecer la informaciÃ³n de la cuenta?

SetInfoCuenta (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de modificar la información de una cuenta en la base de datos, recibe los datos que se actualizarán agrupados por secciones, si no se envía una sección el agente la omitirá, pero si se envía una sección vacía la información que haya almacenada en la base de datos se eliminará. Por ejemplo, si se envía la sección “Conceptos de nómina contable” así: {"conceptosnominacontable":[ ]} el sistema eliminará toda la información que haya registrada de los conceptos de nómina contable asociados a una cuenta.

#### Resultado

Retorna un Json con la confirmaciÃ³n de la modificaciÃ³n de los datos de una cuenta.

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
| dataJSON | JSON | Json que contiene en su interior la información a modificar de una cuenta, contiene la siguiente estructura.  **icuenta**: Identificador de la cuenta (requerido).  **infobasica:** Contiene la información básica de la cuenta. La documentación de esta sección se encuentra en el documento “InfoBasica" de la zona “Documentación de apoyo”.  **conceptosnominacontable**: Arreglo que contiene en su interior objetos con la información de los conceptos de liquidación en egreso que aplican a las cuentas de nómina contable. La documentación de esta sección se encuentra en el documento “ConceptosNominaContable" de la zona “Documentación de apoyo”. | { "icuenta": "510516", "infobasica": { "ncuenta": "Horas extras y bonificaciones", "bmanejatercero": "F" }, "conceptosnominacontable": [ { "icuenta": "510516", "iconcepto": "RANPEPS", "nconcepto": "Aportes a salud trabajador", "iinterno": "0" }, { "icuenta": "510516", "iconcepto": "RANPEPSP", "nconcepto": "Aportes a salud patrono", "iinterno": "1" }, { "icuenta": "510516", "iconcepto": "RANPERP", "nconcepto": "Aportes a pensiones trabajador", "iinterno": "2" }, { "icuenta": "510516", "iconcepto": "RANPICBF", "nconcepto": "Aportes al ICBF", "iinterno": "3" }, { "iconcepto": "RANPRCE", "nconcepto": "Cesantias", "iinterno": "4" }, { "iconcepto": "RANPRARP", "nconcepto": "Aportes al ARP", "iinterno": "5" } ] } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript[Ir arriba](#arriba)

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatPlanCuentas/"SetInfoCuenta"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"icuenta": "510516",
"infobasica": {
"ncuenta": "Horas extras y bonificaciones",
"bmanejatercero": "F"
},
"conceptosnominacontable": [
{
"icuenta": "510516",
"iconcepto": "RANPEPS",
"nconcepto": "Aportes a salud trabajador",
"iinterno": "0"
},
{
"icuenta": "510516",
"iconcepto": "RANPEPSP",
"nconcepto": "Aportes a salud patrono",
"iinterno": "1"
},
{
"icuenta": "510516",
"iconcepto": "RANPERP",
"nconcepto": "Aportes a pensiones trabajador",
"iinterno": "2"
},
{
"icuenta": "510516",
"iconcepto": "RANPICBF",
"nconcepto": "Aportes al ICBF",
"iinterno": "3"
},
{
"iconcepto": "RANPRCE",
"nconcepto": "Cesantias",
"iinterno": "4"
},
{
"iconcepto": "RANPRARP",
"nconcepto": "Aportes al ARP",
"iinterno": "5"
}
]
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/050-SetInfoCuenta.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "9"
},
"respuesta": {
"datos": {
"modificar": "true"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **modificar (varchar):** contiene true cuando la cuenta se modifica satisfactoriamente y false cuando no se pudo modificar. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 50: El usuario no posee permisos para: "abrir el catÃ¡logo de plan de cuentas".
- 130: No se ingresÃ³ el parÃ¡metro "X".
- 190: El registro no existe.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
