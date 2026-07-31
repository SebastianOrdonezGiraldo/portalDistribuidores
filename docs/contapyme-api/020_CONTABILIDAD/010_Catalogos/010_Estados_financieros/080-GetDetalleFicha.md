# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/080-GetDetalleFicha.html

Â¿CÃ³mo verificar el detalle de la ficha?

GetDetalleFicha (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la información financiera de un tercero (movimientos y saldos) discriminada por periodos y de acuerdo a la cuenta que se envía por parámetro.  
Esta función es utilizada en la consulta de los favoritos financieros de un tercero de la aplicación ContaPyme móvil.

#### Resultado

Retorna un Json con la informaciÃ³n financiera de un tercero.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **inode:** especifica el código de la empresa sobre la cual se calculará la información. (opcional)  **init:** Código del tercero para el cual se calculará el detalle de la ficha (información financiera). (requerido)  **itdtercero:** Código del tipo de tercero al que se está asociada la ficha. (requerido)  **idato:** código del favorito para la cual se calculará el detalle. (requerido)  **itdficha:** código del tipo de ficha que se calculará para el favorito. (requerido)  **fecha:** Especifica la fecha de referencia para el cálculo de los datos. Si no se especifica se toma la fecha actual. (opcional) | { "inode": "1", "init": "1053814729", "itdtercero": "2", "idato": "3", "itdficha": "1", "fecha": "12 / 31 / 2013 " } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TEstadosFinancieros/"GetDetalleFicha"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"inode": "1",
"init": "1053814729",
"itdtercero": "2",
"idato": "3",
"itdficha": "1",
"fecha": "12 / 31 / 2013 "
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/080-GetDetalleFicha.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "78"
},
"respuesta": {
"datos": [
{
"iperiodo": "1",
"mdebito": "0",
"mcredito": "1104000",
"pvariacion": "0",
"mvarabsoluta": "-1104000"
},
{
"iperiodo": "2",
"mdebito": "0",
"mcredito": "295000",
"pvariacion": "26.721",
"mvarabsoluta": "-295000"
},
{
"iperiodo": "3",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "4",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "5",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "6",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "7",
"mdebito": "0",
"mcredito": "64000",
"pvariacion": "4.5747",
"mvarabsoluta": "-64000"
},
{
"iperiodo": "8",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "9",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "10",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "11",
"mdebito": "0",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "12",
"mdebito": "0",
"mcredito": "336000",
"pvariacion": "22.9665",
"mvarabsoluta": "-336000"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” que se describe a continuación:  **iperiodo (integer):** Identificador del mes calculado **mdebito (double):** Débitos registrados en el período **mcredito (double)**: Créditos registrados en el período **pvariacion (doublé)**: Porcentaje de variación del saldo. **mvarabsoluta (double)**: Monto de la variación absoluta |

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
