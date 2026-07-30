# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/110-GetOpcionesCuentaFormaCobro.html

Â¿CÃ³mo verificar las opciones de forma de cobro?

GetOpcionesCuentaFormaCobro (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la configuración que se debe aplicar a una cuenta cuando se utilice en la forma de cobro de una operación.  
Estas configuraciones están relacionadas principalmente con el manejo de flujo de efectivo y de conciliación bancaria, pueden ser usadas en las aplicaciones cliente para ocultar o visualizar campos en la forma de cobro por cada medio de pago.

#### Resultado

Retorna un Json con la configuraciÃ³n que se deben aplicar para una cuenta dentro de la forma de cobro de una operaciÃ³n.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente información.  **icuenta:** Identificación de la cuenta de la cual se desea obtener la configuración aplicable para la forma de cobro de una operación. (requerido)  **fsoport**: Fecha de soporte de la operación (05-10-2014). (requerido) | { "icuenta": "11100501", "fsoport": "05-10-2014" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetOpcionesCuentaFormaCobro"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON ={
"icuenta": "11100501",
"fsoport": "05-10-2014"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/110-GetOpcionesCuentaFormaCobro.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "48"
},
"respuesta": {
"datos": {
"bmanejatercero": "F",
"bexigetercero": "T",
"bexigecc": "F",
"brequierenumtransaccion": "T",
"bmanejaotramoneda": "T",
"bcaneditvrotramoneda": "T",
"imoneda": "20",
"mtasacambio": "0.0000",
"bmanejaflujoefectivo": "T",
"bexigeflujoefectivo": "T"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **bmanejatercero (boolean):** Indica si la cuenta maneja o no tercero.  **bexigetercero (boolean):** Indica si la cuenta exige o no tercero  **bexigecc (boolean):** Indica si la cuenta exige o no centro de costos  **brequierenumtransaccion (boolean):** Indica si la cuenta requiere el número de transacción (conciliación bancaria).  **bmanejaotramoneda (boolean):** Indica si está habilitado el manejo de moneda extranjera y si la cuenta tiene activa la opción de moneda extranjera, es decir que se mostrará el campo de valor en otra moneda.  **bcaneditvrotramoneda (boolean):** Retorna T si el valor en moneda extranjera puede ser modificado.  **imoneda (boolean):** indica el código de la moneda a usar para la cuenta.  **mtasacambio (boolean):** indica la tasa de cambio respecto a la moneda local, si la cuenta es de moneda extranjera.  **bmanejaflujoefectivo (boolean):** indica si está habilitado el manejo de flujo de efectivo. Si la cuenta no es de efectivo, este atributo siempre irá en F.  **bexigeflujoefectivo (boolean):** indica si además de tener activo el manejo de flujo de efectivo, es obligatorio especificar un código de concepto de flujo de efectivo. Si la cuenta no es de efectivo, este atributo siempre irá en F. |

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
