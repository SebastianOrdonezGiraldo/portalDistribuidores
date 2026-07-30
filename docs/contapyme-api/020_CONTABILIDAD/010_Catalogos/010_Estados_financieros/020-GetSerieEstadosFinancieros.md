# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/020-GetSerieEstadosFinancieros.html

Â¿CÃ³mo obtener el numero de serie de un estado financiera?

GetSerieEstadosFinancieros (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar el movimiento de una o varias cuentas en una serie de tiempo.  
La serie de tiempo puede ser: diaria, semanal, mensual, bimestral, trimestral, cuatrimestral, semestral o anual. De acuerdo a la serie de tiempo solicitada, la función retornará los movimientos de la cuenta discriminados por día, semana, mes entre otros.

#### Resultado

Retorna un Json con el movimiento de una o varias cuentas en una serie de tiempo

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **inode:** Este parámetro puede contener los siguientes datos:   - Código identificador de la empresa para la cual se desean obtener los estados financieros *(requerido).* - Código del centro de costos del cual se desean consultar los estados financieros *(opcional)*   En caso de que se envíen los dos parámetros éstos deben ir separados por “:”, así: "1:1150", el primer parámetro corresponde al código de la empresa y **icuentas:** Arreglo que contiene la o las cuentas para las cuales se calcula la serie. (*requerido*)  **periodicidad:** Indica el periodo de tiempo que determina el cálculo de la serie. Esta puede ser: *(requerido)*   - Diaria - Semanal - Mensual - Bimestral - Trimestral - Cuatrimestral - Semestral - Anual.   **fini:** Indica la fecha inicial de cálculo de los saldos. Si no se indica se calcula según la periodicidad requerida. Si periodicidad es:  1 ó 2: fini= inicio mes.  3, 4, 5, 6 ó 7: fini= inicio año.  8: fini= fecha inicial de registro de movimientos contables para la empresa.  *(opcional)* | { "inode": "1", "icuentas": [ "1110" ], "periodicidad": 3, "fini": "12/01/2013", "ffin": "12/31/2013", "bsaldoinicial": "T" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TEstadosFinancieros/"GetSerieEstadosFinancieros"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"inode": "1",
"icuentas": [
"1110"
],
"periodicidad": 3,
"fini": "12/01/2013",
"ffin": "12/31/2013",
"bsaldoinicial": "T"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/020-GetSerieEstadosFinancieros.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "87"
},
"respuesta": {
"datos": [
{
"iperiodo": "0",
"mdebito": "15150000",
"mcredito": "0",
"pvariacion": "0",
"mvarabsoluta": "0"
},
{
"iperiodo": "1",
"mdebito": "668160",
"mcredito": "100000",
"pvariacion": "3.7502",
"mvarabsoluta": "568160"
},
{
"iperiodo": "2",
"mdebito": "0",
"mcredito": "1000000",
"pvariacion": "-6.3621",
"mvarabsoluta": "-1000000"
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
"mdebito": "50000000",
"mcredito": "11250000",
"pvariacion": "263.2802",
"mvarabsoluta": "38750000"
},
{
"iperiodo": "7",
"mdebito": "1658500",
"mcredito": "1200000",
"pvariacion": "0.8575",
"mvarabsoluta": "458500"
},
{
"iperiodo": "8",
"mdebito": "0",
"mcredito": "1350000",
"pvariacion": "-2.5034",
"mvarabsoluta": "-1350000"
},
{
"iperiodo": "9",
"mdebito": "0",
"mcredito": "5250000",
"pvariacion": "-9.9854",
"mvarabsoluta": "-5250000"
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
"mdebito": "3600000",
"mcredito": "20400000",
"pvariacion": "-35.498",
"mvarabsoluta": "-16800000"
},
{
"iperiodo": "12",
"mdebito": "87750000",
"mcredito": "24900000",
"pvariacion": "205.8856",
"mvarabsoluta": "62850000"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar)**: Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” que se describe a continuación. **iperiodo (integer):** Código del período obtenido en el cálculo, puede corresponder a un número de día, a un número de mes, bimestre, trimestre entre otros. **mdebito (double):** Débitos que recibió la cuenta en el período. **mcredito (double):** Créditos que recibió la cuenta en el período. **pvariacion (double):** porcentaje de variación del saldo de la cuenta en el período. **mvarabsoluta (double):** Monto de variación absoluta del saldo de la cuenta. |

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
