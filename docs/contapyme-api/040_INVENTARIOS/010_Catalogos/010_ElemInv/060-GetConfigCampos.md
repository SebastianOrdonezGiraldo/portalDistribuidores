# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/010_Catalogos/010_ElemInv/060-GetConfigCampos.html

Â¿CÃ³mo obtener la configuraciÃ³n de los campos?

GetConfigCampos (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la configuración para cada campo del catálogo de elementos de inventarios, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” del catálogo de elementos de inventario.  
Por cada campo retorna: Si es visible, requerido o de solo lectura, valor por defecto, etiqueta, tipo de lista y configuración para las listas.

#### Resultado

Retorna un Json con la configuraciÃ³n de cada uno de los campos del catÃ¡logo de elementos de inventario.

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
| dataJSON | JSON | En esta función este parámetro no se utiliza pero se define debido que puede ser requerido para usos futuros en el Agente CP. | {} |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatElemInv/"GetConfigCampos"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {};
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/060-GetConfigCampos.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "60"
},
"respuesta": {
"datos": {
"infobasica": {
"nrecurso": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"nunidad": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": "",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "T"
},
"bconsumo": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"bcontrolinv": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"bproducto": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"bservicio": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"clase1": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "Clase 1",
"valorpordefecto": "",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "F"
},
"idepinv": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"igrupoinv": {
"bvisible": "T",
"blectura": "F",
"brequerido": "T",
"etiqueta": "",
"valorpordefecto": ""
},
"sdescrip": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"smarca": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": "",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "F"
}
},
"cfg": {
"bpersonalizarcuentasmanejo": "T",
"bpersonalizarcuentainventario": "T",
"bcalcimpfromcuentaing": "F",
"bpermitirelemcompuestos": "T",
"qprecisionelemcompuesto": "2",
"bmanejoalias": "F",
"bpermitiralquilerelemcontrol": "T",
"bundcompra": "T",
"binventarioestricto": "T",
"bundventa": "T",
"qprecisioncosto": "2",
"itipocostosdefmargenneg": "0",
"ilistapreciosdefmargenneg": "0",
"ilistapreciosdefault": "1",
"itdlistaprecios": "1"
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
| respuesta | JSON | Json que contiene en su interior el objeto “datos”, éste a su vez contiene objetos con las secciones de información del catálogo de terceros, cada una de estas secciones contiene los campos con su respectiva configuración.  **infobasica** (**objeto**): Corresponde a la sección en la que se agrupa la información del elemento de inventario, contiene objetos con los campos del catálogo de elementos de inventario, la llave del objeto es el nombre del campo y su valor contiene la configuración así:  **bvisible**: Contiene T cuando el campo es visible en el formulario y F cuando no lo es.  **blectura**: Contiene T cuando el campo es de solo lectura (es decir, no se puede modificar) y F cuando no lo es.  **brequerido**: Contiene T cuando el campo es obligatorio en el formulario y F cuando es opcional.  **etiqueta**: contiene el nombre del campo definido por el usuario. Hay algunos campos que permiten configurar su nombre según las necesidades del usuario, en ese caso, este parámetro contendrá el nombre que definió el usuario para el campo.  **valorpordefecto**: valor por defecto configurado por el usuario para el campo.  Cuando el campo es de tipo lista, el objeto contendrá adicionalmente los siguientes campos:  **itdlista:** Código identificador del tipo de lista, influye en el tipo de campo que se debe presentar en el formulario. Los tipos son:  0: **Edit** – Corresponde a una caja de texto normal.  1: **Autolista** – Cuando la configuración retorne este tipo de lista, se debe llamar la función GetAutoLista de la clase TCatElemInv y enviar el nombre del campo para poder obtener los valores que se deben presentar en el listado, adicionalmente si ncampofiltro (que se describe más adelante) retorna algún valor, al llamar la función GetAutoLista se debe enviar dicho campo de filtro.   2: **Autolistacatalogo**: Corresponde a un campo de tipo selector, cuando llegue este tipo de lista se debe llamar la función GetListaSeleccion de la clase que llegue en el parámetro “ncatalogo”.  **ncampofiltro**: Nombre del campo de filtro que se debe aplicar al obtener la autolista, esto cuando el itdlista es 1.  **ncatalogo**: nombre de la clase de la cual se debe llamar la función GetListaSelección cuando el itdlista es 2.  **beditable**: aplica para cuando el itdlista es 1, cuando contenga F indica que el campo debe ser un combobox (pues el campo corresponde a una tabla de usuario) y cuando contenga T indica que el campo debe ser un combobox editable.  **cfg** (**objeto**): Objeto que contiene las configuraciones adicionales que se deben tener en cuenta para el manejo del catálogo de elementos de inventario, estas configuraciones pueden ser definidas por el usuario. Las configuraciones del catálogo de elementos de inventario son:  **bpersonalizarcuentasmanejo**: Contiene T cuando es posible personalizar por cada elemento de inventario las cuentas que el sistema afectará al momento de comprar, vender o consumir el elemento, y F cuando no es posible.  **bpersonalizarcuentainventario**: Contiene T cuando es posible personalizar por cada grupo o elemento de inventario, la cuenta de Inventarios y de devoluciones que el sistema afectará al momento de registrar compras o embodegamientos del producto. Y contiene F cuando no es posible realizar ese manejo.  **bcalcimpfromcuentaing**: Contiene T cuando es posible usar la configuración de la cuenta de ingresos por venta para el cálculo de liquidación (IVA y Retención) en ventas de productos. Y contiene F cuando no es posible realizar ese manejo.  **bpermitirelemcompuestos**: Contiene T cuando es posible definir elementos de inventario compuestos. Y contiene F no es posible.  **qprecisionelemcompuesto**: Contiene la precisión (cantidad de decimales) que se usará al registrar la cantidad de partes que componen un elemento compuesto.  **bmanejoalias**: Contiene T cuando es posible manejar alias de elementos de inventario, es decir, definir múltiples elementos de control con diferentes configuraciones de cuentas, pero que a nivel de inventarios representan un mismo elemento. Y contiene F cuando no es posible realizar ese manejo.  **bpermitiralquilerelemcontrol**: Contiene T cuando es posible definir elementos de inventario de alquiler, y F cuando no es posible.  **bundcompra**: Contiene T cuando es posible manejar unidad de medida para compras del elemento, diferente a la unidad de medida en uso, consumo y ventas.  **binventarioestricto**: Contiene T cuando el sistema maneja un control estricto de las cantidades de los elementos de inventario, es decir, no permite egresar más productos de los que haya en el inventario físico. Y contiene F cuando no se aplica este manejo.  **bundventa**: Contiene T cuando es posible manejar la unidad de presentación o empaque del elemento, diferente a la unidad de medida general del mismo. Contiene F cuando no es posible realizar este manejo.  **qprecisioncosto**: Contiene el número de decimales a los cuales el sistema redondeará el costo unitario del elemento en las operaciones de compras o embodegamientos.  **itipocostosdefmargenneg**: Contiene el tipo de margen de negociación que se usará para los elementos de inventario, los tipos son: **0**: Último valor de compra **1**: Costo promedio ponderado **2**: Costo predeterminado  **ilistapreciosdefmargenneg**: Código de la lista de precios por defecto para el cálculo de los márgenes de negociación de productos.  **ilistapreciosdefault**: Código de la lista de precios a usar por defecto en las operaciones de venta de productos, cuando el tercero al que se realiza la venta no tiene una lista de precios asignada.  **itdlistaprecios**: Identificador del tipo de lista de precios a usar. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 1008: El cÃ³digo de la aplicaciÃ³n es incorrecto, informar de este error.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
