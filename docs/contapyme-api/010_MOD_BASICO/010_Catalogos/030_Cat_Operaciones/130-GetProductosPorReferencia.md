# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/130-GetProductosPorReferencia.html

Â¿CÃ³mo verificar el producto por su referencia?

GetProductosPorReferencia (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar los productos asociados a una referencia dada, permitiendo cargar productos de otra operación en la operación actual. Por ejemplo, estando en un pedido es posible cargar como referencia una cotización para que así no sea necesario volver a registrar todos los productos nuevamente.  
El manejo de referencias aplica para las operaciones relacionadas con compras y ventas en inventarios.

#### Resultado

Retorna un Json con el listado de productos asociados a una referencia dada.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente información:  **itdoper**: Identificador del tipo de operación actual sobre la cual se va a cargar la referencia de productos. (requerido).  **fsoport**: Fecha de soporte de la operación actual, esta fecha debe estar en formato mm/dd/aaaa. (requerido)  **ireferencia**: Número de documento soporte de la operación de la cual se cargarán los productos (snumsop). (requerido)  **bsaldos**: Valor booleano que indica si se deben cargar los saldos pendientes por cada producto. El saldo pendiente hace referencia a la cantidad de producto que hace falta por ordenar o remisionar de una cotización o pedido, respectivamente. (requerido) | { "itdoper": "ORD1", "fsoport": "04/10/2013", "ireferencia": "COT-002312", "bsaldos": "T" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetProductosPorReferencia"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"itdoper": "ORD1",
"fsoport": "04/10/2013",
"ireferencia": "COT-002312",
"bsaldos": "T"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/130-GetProductosPorReferencia.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "7"
},
"respuesta": {
"datos": {
"listaproductos": [
{
"iinventario": "0",
"irecurso": "01100",
"itiporec": "",
"qrecurso": "-5.0000",
"qporcdescuento": "0.0000",
"qporciva": "0.0000",
"mprecio": "250000.0000",
"mvrtotal": "1250000.0000",
"sobserv": ", 5,00 und CTC-1404004",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "0.0000",
"valor2": "0.0000",
"valor3": "0.0000",
"valor4": "0.0000"
},
{
"iinventario": "0",
"irecurso": "03110",
"itiporec": "",
"qrecurso": "-2.0000",
"qporcdescuento": "0.0000",
"qporciva": "0.0000",
"mprecio": "42000.0000",
"mvrtotal": "84000.0000",
"sobserv": ", 2,00 und CTC-1404004",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "0.0000",
"valor2": "0.0000",
"valor3": "0.0000",
"valor4": "0.0000"
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
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **listaproductos (arreglo de objetos)**: arreglo que contiene objetos con la información de cada producto de la referencia, cada objeto posee la siguiente información: **iinventario (varchar)**: Código de la bodega definida en la operación que se carga como referencia. **irecurso (varchar):** Código del producto definido en la operación que se carga como referencia. **itiporec (varchar):** Identificador del tipo de producto. **qrecurso (varchar)**: Cantidad de producto. **qporcdescuento (varchar)**: Porcentaje de descuento especificado para el producto. **qporciva (varchar)**: Porcentaje de IVA aplicado al producto. **mprecio (varchar):** Precio del producto. **mvrtotal (varchar)**: Valor total del producto, resultado de multiplicar la cantidad de producto por su valor unitario. **sobserv (varchar):** Observaciones especificadas para el renglón. **dato1, dato2, dato3, dato4, dato5, dato6, valor1, valor2, valor3 y valor4 (varchar):** Datos adicionales que se especifican para el producto en la operación que se carga como referencia. |

Eventualidades[ir arriba](#arriba)

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
