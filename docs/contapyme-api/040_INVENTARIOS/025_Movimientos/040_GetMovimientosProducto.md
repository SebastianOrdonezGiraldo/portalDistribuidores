# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/025_Movimientos/040_GetMovimientosProducto.html

Â¿CÃ³mo verificar los movimientos del producto?

GetMovimientosProducto (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar el listado con los movimientos de un tipo particular para un producto dado en un periodo.

#### Resultado

Retorna un Json con los movimientos de un tipo particular para un producto dado en periodo.

Seguridad

Aplica todas las configuraciones de seguridad de datos y de seguridad de acciones de ContaPyme / AgroWin.

Compatibilidad de la API

FunciÃ³n disponible desde ContaPyme/AgroWin VersiÃ³n 4 - Release 7.

## PeticiÃ³n

Requisitos

Debe haber realizado el logueo en el agente a travÃ©s de la funciÃ³n [GETAUTH().](../../005_INTRODUCCION/020-GetAuth.html)

ParÃ¡metros

| Nombre parÃ¡metro | Tipo | DescripciÃ³n | Ejemplo |
| --- | --- | --- | --- |
| dataJSON | JSON | Este Json contiene en su interior la siguiente estructura:  **iinventario:** Código de la bodega donde se buscarán los movimientos de productos (Opcional). Si no se envía el código de la bodega buscará en todas las bodegas.  **fini:** Fecha inicial del periodo que se quiere evaluar. Si no se indica, se asume desde inicio del año actual. (Opcional)  **ffin:** Fecha final del periodo que se quiere evaluar. Si no se indica, se asume hasta la fecha del sistema. (Opcional)  **init:** Código del tercero para el cual se obtiene los movimientos. En caso de una compra sería el proveedor, de una venta el cliente, etc. (Opcional).  **where:** Condición adicional que puede ser aplicada a la consulta (Opcional).  **itdmov:** Código del tipo de movimiento de inventarios particular para el cual se evalúan los productos (Opcional):  **3001:** Movimiento de ingreso por embodegamiento.  **3002:** Movimiento de egreso de kárdex.  **3003:** Movimiento de ajuste ingreso en kárdex.  **3004:** Movimiento de ajuste egreso en kárdex.  **3005:** Movimiento de venta de recursos del kárdex.  **3006:** Movimiento de compra de elementos de control.  **3007:** Movimiento de devolución en compras de **productos.** **3008:** Movimiento de devolución en ventas de productos.  **3009:** Movimiento de egreso de kárdex para consumo.  **3010:** Movimiento de inicialización de saldos de kárdex para comienzo de año.  **3011:** Movimiento de ingreso por **traslados.** **3012:** Movimiento de ajustes por inflación a productos.  **3013:** Movimiento de deterioro/reversión de deterioro de inventarios. | { "irecurso": "75625630", "itdmov": "3005" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TInventarios/"GetMovimientosProducto"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"irecurso": "75625630",
"itdmov": "3005"
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
[PHP](../../ejemplo/PHP.html) ,
[JAVA](../../ejemplo/JAVA.html),
[C#](../../ejemplo/Csharp.html),
[Visual Basic.net](../../ejemplo/visualBasic.html),
[Visual Basic 6](../../ejemplo/visualBasic6.html),
[Delphi.](../../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/040_GetMovimientosProducto.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "37"
},
"respuesta": {
"datos": [
{
"iinventario": "1207",
"qproducto": "-3",
"mvalor": "91200"
},
{
"iinventario": "1218",
"qproducto": "-1",
"mvalor": "30400"
},
{
"iinventario": "1219",
"qproducto": "-1",
"mvalor": "30400"
},
{
"iinventario": "1224",
"qproducto": "-2",
"mvalor": "60800"
},
{
"iinventario": "1227",
"qproducto": "-3",
"mvalor": "91200"
},
{
"iinventario": "1229",
"qproducto": "-1",
"mvalor": "30400"
},
{
"iinventario": "1231",
"qproducto": "-1",
"mvalor": "30400"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje** (**varchar**): Código del mensaje de eventualidad o error en caso de presentarse. **mensaje** (**varchar**): Mensaje de eventualidad o error en caso de presentarse. **tiempo** (**varchar**): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” los cuales serán descritos a continuación.  **datos (arreglo de objetos):** contiene en su interior el listado de movimientos de un producto en un periodo. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 40: Usuario no logueado.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
