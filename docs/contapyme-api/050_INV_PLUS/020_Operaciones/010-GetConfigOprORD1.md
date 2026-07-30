# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/050_INV_PLUS/020_Operaciones/010-GetConfigOprORD1.html

Â¿CÃ³mo obtener configuraciones para operaciÃ³n de "pedido a un cliente"?

GetConfigOprORD1 (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la configuración de los campos de la operación de pedido, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” de dicha operación.

Por cada campo retorna si es visible, requerido, de solo lectura, valor por defecto, etiqueta y su configuración cuando es un campo de tipo lista.  
También retorna las opciones de configuración propias de la operación y la definición por defecto para las formas de cobro de la operación.

Para conocer los campos de la operación, consultar el documento: “DocJsonOprORD1” que se encuentra en la zona de “Documentación de apoyo”.

#### Resultado

Retorna un Json con la **configuraciÃ³n de la operaciÃ³n de pedido** disponible para un usuario.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **itdsop:** Identificador del tipo de documento de soporte por defecto de la operación. | { "itdsop": "35" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"GetConfigOprOrd1"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"itdsop": "35"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/010-GetConfigOprORD1.pdf)

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
"cfg": {
"bitdsopoprro": "F",
"bsnumsopoprro": "T",
"iclaseopr": "0",
"biclaseoprro": "F",
"bfsoportoprro": "F",
"iccbase": "",
"imonedadef": "10",
"bdescuentopedidos": "T",
"bconfigprecio": "T"
},
"encabezado": {
"svaloradic1": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": "Valor 1",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
},
"svaloradic2": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": "Valor 2",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
},
"svaloradic3": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": "Valor 3",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
},
"svaloradic4": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": "Valor 4",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
}
},
"datosprincipales": {
"init": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"ireferencia": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"ilistaprecios": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"bregvrunit": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "F",
"etiqueta": ""
},
"bregvrtotal": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "F",
"etiqueta": ""
},
"iinventario": {
"bvisible": "T",
"blectura": "F",
"brequerido": "T",
"valorpordefecto": "",
"etiqueta": ""
},
"initvendedor": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"planeacionentrega": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"qdias": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"datosadicionales": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"isucursalcliente": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"icuenta": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"step\_2": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"step\_3": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"step\_4": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
}
},
"listaproductos": {
"itiporec": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"nunidad": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"qporcdescuento": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"sobserv": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"dato1": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": "Dato 1",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
},
"dato2": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": "Dato 2",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
},
"dato3": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": "Dato 3",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
},
"valor1": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
},
"valor2": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"valorpordefecto": "",
"etiqueta": ""
}
},
"formacobro": {
"cajas": [
{
"icuenta": "110505",
"icc": "",
"iflujoefec": "1111",
"itipomovcaja": "",
"caption": "Caja # 1"
}
],
"bancos": [
{
"icuenta": "11100501",
"icc": "",
"ibanco": "CH",
"iconsignacion": "",
"iflujoefec": "1113",
"caption": "Banco # 1"
}
],
"cxc": [
{
"icuenta": "130505",
"caption": "CxC # 1"
}
]
}
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje** (**varchar**): Código del mensaje de eventualidad o error en caso de presentarse. **mensaje** (**varchar**): Mensaje de eventualidad o error en caso de presentarse. **tiempo** (**varchar**): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos”, éste a su vez contiene objetos con las secciones de información de la operación de pedidos, cada una de estas secciones contiene los campos con su respectiva configuración.  **cfg (objeto):** Objeto que contiene las configuraciones adicionales que se deben tener en cuenta para el manejo de la operación de pedido de un cliente, estas configuraciones pueden ser definidas por el usuario. Las configuraciones de la operación de pedido son:  **bitdsopoprro:** Contiene T cuando no es posible modificar el tipo de documento de soporte en la operación y contiene F cuando si es posible modificarlo.  **bsnumsopoprro:** indica si el número de documento puede ser o no modificado. T: solo lectura. F: editable.  **iclaseopr:** Código de la clase de operación por defecto que se asignará a las nuevas operaciones de pedido que se creen en el sistema.  **biclaseoprro:** Contiene T cuando la clase de operación asignada para las nuevas operaciones de pedido, puede ser modificada por el usuario, y contiene F cuando no es posible modificar la clase de operación.  **bfsoportoprro:** Contiene T cuando la fecha de operación asignada para las nuevas operaciones de pedido, puede ser modificada por el usuario, y contiene F cuando no es posible modificar la fecha de la operación.  **iccbase:** código del centro de costos base por defecto para las operaciones donde aplique.  **imonedadef:** Código de la moneda por defecto que se usará para las nuevas operaciones de pedido, de todas formas, al crear la operación se podrá modificar la moneda si así se requiere.  **bdescuentopedidos:** Contiene T cuando está habilitado el campo de descuentos en el registro de los productos del pedido.  **bconfigprecio:** Contiene T cuando es posible cambiar el modo de registro del precio del producto, es decir, registrar el precio unitario o el valor total.  **encabezado (objeto):** Objeto que contiene la configuración de los campos de: svaloradic1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, y 12. La llave el objeto es el nombre del campo y su valor contiene la configuración, así:  **bvisible:** Contiene T cuando el campo es visible en el formulario y F cuando no lo es.  **blectura:** Contiene T cuando el campo es de solo lectura (es decir, no se puede modificar) y F cuando no lo es.  **brequerido:** Contiene T cuando el campo es obligatorio en el formulario y F cuando es opcional.  **etiqueta:** contiene el nombre del campo definido por el usuario. Hay algunos campos que permiten configurar su nombre según las necesidades del usuario, en ese caso, este parámetro contendrá el nombre que definió el usuario para el campo.  **valorpordefecto:** valor por defecto configurado por el usuario para el campo.  Cuando el campo es de tipo lista, el objeto contendrá adicionalmente los siguientes campos:  **itdlista:** Código identificador del tipo de lista, influye en el tipo de campo que se debe presentar en el formulario. Los tipos son:  0: Edit – Corresponde a una caja de texto normal.  **1: Autolista** – Cuando la configuración retorne este tipo de lista, se debe llamar la función GetAutoLista de la clase TCatTerceros y enviar el nombre del campo para poder obtener los valores que se deben presentar en el listado, adicionalmente si ncampofiltro (que se describe más adelante) retorna algún valor, al llamar la función GetAutoLista se debe enviar dicho campo de filtro.   **2: Autolistacatalogo** – Corresponde a un campo de tipo selector, cuando llegue este tipo de lista se debe llamar la función GetListaSeleccion de la clase que llegue en el parámetro “ncatalogo”.  **ncampofiltro:** Nombre del campo de filtro que se debe aplicar al obtener la autolista, esto cuando el itdlista es 1.  **ncatalogo:** nombre de la clase de la cual se debe llamar la función GetListaSelección cuando el itdlista es 2.  **beditable:** aplica para cuando el itdlista es 1, cuando contenga F indica que el campo debe ser un combobox (pues el campo corresponde a una tabla de usuario) y cuando contenga T indica que el campo debe ser un combobox editable.  **datosprincipales (objeto):** Objeto que contiene la configuración de los campos principales de la operación, cada campo contiene la configuración que se detalló para el objeto “encabezado”. De este objeto es importante resaltar los siguientes campos:  **step\_2:** Indica si el paso “Datos adicionales del pedido” estará visible en el asistente de registro de la operación de pedido.  **step\_3:** Indica si el paso “Observaciones” estará visible en el asistente de registro de la operación de pedido.  **step\_4:** Indica si el paso “Forma de pago del primer anticipo” estará visible en el asistente de registro de la operación de pedido.  **listaproductos (objeto):** Objeto que contiene la configuración de los campos para el registro de productos de la operación, cada campo contiene la configuración que se detalló para el objeto “encabezado”.  **formacobro (objeto):** Objeto que contiene la configuración de cada una de las formas de cobro definidas en el sistema ContaPyme / AgroWin, así:  **cajas:** arreglo que contiene objetos con la configuración de cada una de las formas de cobro de caja definidas en el sistema. Cada objeto **contiene:** **icuenta:** código de la cuenta de caja definida por **defecto.** **icc:** código del centro de costos definido por **defecto.** **iflujoefec:** Código del concepto de flujo de efectivo definido para la cuenta de caja configurada por **defecto.** **itipomovcaja:** Código del tipo de movimiento de caja asignado por **defecto.** **caption:** nombre personalizado que se asigna a la forma de cobro de caja.  **bancos:** arreglo que contiene objetos con la configuración de cada una de las formas de cobro de banco definidas en el sistema. Cada objeto **contiene:** **icuenta:** código de la cuenta de banco definida por **defecto.** **icc:** código del centro de costos definido por **defecto.** **ibanco:** identificador del tipo de movimiento bancario asignado por **defecto.** **iflujoefec:** Código del concepto de flujo de efectivo definido para la cuenta de banco configurada por **defecto.** **caption:** nombre personalizado que se asigna a la forma de cobro de banco.  **cxc:** arreglo que contiene objetos con la configuración de cada una de las formas de cobro por Cuenta por cobrar definidas en el sistema. Cada objeto **contiene:** **icuenta:** código de la cuenta para Cuentas por cobrar definidas por **defecto.** **caption:** nombre personalizado que se asigna a la forma de cobro por Cuenta por cobrar (CxC). |

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
