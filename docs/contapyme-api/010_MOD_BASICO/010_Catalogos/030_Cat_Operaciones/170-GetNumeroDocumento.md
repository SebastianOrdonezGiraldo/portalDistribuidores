# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/170-GetNumeroDocumento.html

Â¿CÃ³mo verificar el nÃºmero de documento?

GetNumeroDocumento (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar el número de documento de una operación ya formateado según la configuración del tipo de documento de soporte.

Esta función se debe llamar cada vez que se cambie alguno de los siguientes datos de una operación:

- Número autoincremental asignado a la operación (inumoper)
- Tipo de documento de soporte de la operación (itdsop)
- Fecha de soporte de la operación (fsoport)
- Número consecutivo de acuerdo al tipo de documento de soporte (inumsop)
- Número del documento formateado de acuerdo al tipo de documento de soporte (snumsop).

#### Resultado

Retorna un Json con el nÃºmero de documento de una operaciÃ³n.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente información.  **inumoper:** Número autoincremental asignado a una operación. (requerido) **itdsop:** Código del tipo de documento de soporte actual. (requerido) **olditdsop:** Código de tipo de documento de soporte anterior. (requerido) **fsoport**: Fecha de soporte de la operación. (requerido) **inumsop:** Numero consecutivo actual del tipo de documento de soporte de la operación. (requerido) **oldinumsop:** Numero consecutivo anterior del tipo de documento de la operación. (requerido) **snumsop:** Numero de documento formateado actual de la operación. (requerido) oldsnumsop: Numero de documento formateado anterior de la operación. (requerido) | { "inumoper": "34473", "itdsop": "34", "olditdsop": "34", "fsoport": "04/04/2014", "inumsop": "5", "oldinumsop": "4", "snumsop": "CTC-1404004", "oldsnumsop": "CTC-1404004" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetNumeroDocumento"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"inumoper": "34473",
"itdsop": "34",
"olditdsop": "34",
"fsoport": "04/04/2014",
"inumsop": "5",
"oldinumsop": "4",
"snumsop": "CTC-1404004",
"oldsnumsop": "CTC-1404004"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/170-GetNumeroDocumento.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "4"
},
"respuesta": {
"datos": {
"inumsop": "4",
"snumsop": "CTC-1404004"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar)**: Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **inumsop (varchar)**: Número consecutivo actual del tipo de documento de soporte de la operación. **snumsop (varchar)**: Nuevo número de documento formateado para la operación. |

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
