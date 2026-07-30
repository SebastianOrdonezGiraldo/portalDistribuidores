# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/010-GetInfoEstadosFinancieros.html

Â¿CÃ³mo obtener la informaciÃ³n de estados financieros?

GetInfoEstadosFinancieros (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la información de saldos y movimientos contables de una cuenta o conjunto de cuentas.  
Retorna datos como: Saldo anterior, movimientos débito y crédito del período consultado, nuevo saldo, porcentaje de variación del saldo, entre otros.  
Con esta función es posible obtener la información contable de un conjunto de cuentas hijas o solo de las cuentas padre del PUC.

#### Resultado

Retorna un Json con informaciÃ³n de saldos y movimientos de una cuenta en un perÃ­odo de tiempo determinado.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura:  **inode:** Este parámetro puede contener los siguientes datos:   - Código identificador de la empresa para la cual se desean obtener los estados financieros (requerido).  - Código del centro de costos del cual se desean consultar los estados financieros (opcional)  En caso de que se envíen los dos parámetros éstos deben ir separados por “:”, así: "1:1150", el primer parámetro corresponde al código de la empresa y el segundo al código del centro de costos.   **icuentapadre:** Determina la cuenta padre para la cual se obtendrán los saldos y movimientos de sus cuentas hijas. (opcional)  **ffin:** Indica la fecha hasta la cual se realizará el cálculo de los saldos. Si no se indica se toma la fecha actual. El formato de la fecha es: mm/dd/aaaa.  (Opcional)  **itdsaldo:** Indica el tipo de saldo que se va a calcular. Existen las siguientes opciones: **0:** Histórico (por defecto) **1:** Mes (Saldo del mes y calculando también en MSaldoAnt, saldo hasta el mes anterior) **2:** Año (Saldos del año y calculando también en MSaldoAnt, saldo hasta el año anterior) (opcional).  **filtro:** Permite definir un filtro adicional que debe ser aplicado a la consulta. (opcional).  **btotalizar:** Indica si se debe retornar un solo registro con los totales de todas las cuentas hijas. Sino, se entregan los registros discriminados. (opcional). | { "inode": "1", "icuentapadre": "11", "fini": "12/31/2013", "itdsaldo": "2", "btotalizar": "T" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TEstadosFinancieros/"GetInfoEstadosFinancieros"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"inode": "1",
"icuentapadre": "11",
"fini": "12/31/2013",
"itdsaldo": "2",
"btotalizar": "T"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/010-GetInfoEstadosFinancieros.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "8"
},
"respuesta": {
"datos": [
{
"icuenta": "1105",
"ncuenta": "Caja",
"msaldoant": "27701890",
"mdebito": "24376520",
"mcredito": "32712000",
"msaldo": "19366410",
"pvariacion": "-30.089932",
"mvarabsoluta": "-8335480",
"btienehijas": "T",
"naturaleza": "D",
"itdcuenta": "1"
},
{
"icuenta": "1110",
"ncuenta": "Bancos",
"msaldoant": "30526660",
"mdebito": "87750000",
"mcredito": "24900000",
"msaldo": "93376660",
"pvariacion": "205.885609",
"mvarabsoluta": "62850000",
"btienehijas": "T",
"naturaleza": "D",
"itdcuenta": "1"
},
{
"icuenta": "1120",
"ncuenta": "Cuentas de ahorro",
"msaldoant": "0",
"mdebito": "10000000",
"mcredito": "0",
"msaldo": "10000000",
"pvariacion": "0",
"mvarabsoluta": "10000000",
"btienehijas": "T",
"naturaleza": "D",
"itdcuenta": "1"
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
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” que se describe a continuación.  **icuenta (varchar)**: Código de la cuenta. **ncuenta (varchar)**: Nombre de la cuenta **msaldoant (double):** Saldo de la cuenta hasta la fecha inicial del período de cálculo (saldo anterior). **mdebito (double)**: Débitos que recibió la cuenta durante el período de cálculo. **mcredito (double):** Créditos que recibió la cuenta durante el período de cálculo. **msaldo (double):** Nuevo saldo de la cuenta (débitos - créditos) **pvariacion (double)**: Porcentaje de variación del saldo de la cuenta. **mvarabsoluta (double)**: Monto de variación absoluta. **btienehijas (varchar)**: Contiene T si la cuenta tiene cuentas hijas y F si no tiene cuentas hijas. **naturaleza (varchar):** Indica la naturaleza de la cuenta. D: Débito, C: Crédito **itdcuenta (varchar):** Identificador del tipo de cuenta.1: Activo, 2: Pasivo, 3: Patrimonio, 4: Ingresos, 5: Egresos, 6: De orden deudora, 7: De orden acreedora. |

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
