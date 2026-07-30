# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/060-GetConfigCampos.html

Â¿CÃ³mo obtener la configuraciÃ³n de los campos?

GetConfigCampos (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la configuración para cada campo del catálogo de terceros, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” del catálogo de terceros.  
Por cada campo retorna: Si es visible, requerido o de solo lectura, valor por defecto, etiqueta, tipo de lista y configuración para las listas.

#### Resultado

Retorna un Json con la configuraciÃ³n de cada uno de los campos del catÃ¡logo de terceros.

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
var URLFuncion = '/datasnap/rest/TCatTerceros/"GetConfigCampos"/';
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
"tiempo": "111"
},
"respuesta": {
"datos": {
"infobasica": {
"ntercero": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"napellido": {
"bvisible": "T",
"blectura": "F",
"brequerido": "T",
"etiqueta": "",
"valorpordefecto": ""
},
"stratamiento": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": "",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "F"
},
"sprofesion": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": "",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "F"
},
"scargo": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": "",
"itdlista": "1",
"ncampofiltro": "",
"ncatalogo": "",
"beditable": "F"
},
"isexo": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"tdireccion": {
"bvisible": "T",
"blectura": "F",
"brequerido": "T",
"etiqueta": "",
"valorpordefecto": ""
},
"ipais": {
"bvisible": "T",
"blectura": "F",
"brequerido": "T",
"etiqueta": "",
"valorpordefecto": "169"
},
"idep": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": "17"
},
"imun": {
"bvisible": "T",
"blectura": "F",
"brequerido": "T",
"etiqueta": "",
"valorpordefecto": "001"
},
"ttelefono": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"tcelular": {
"bvisible": "T",
"blectura": "F",
"brequerido": "F",
"etiqueta": "",
"valorpordefecto": ""
},
"semail": {
"bvisible": "T",
"blectura": "F",
"brequerido": "T",
"etiqueta": "",
"valorpordefecto": ""
}
},
"cfg": {
"bapellidotercero": "T",
"bactivarcomisiones": "T"
}
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos”, éste a su vez contiene objetos con las secciones de información del catálogo de terceros, cada una de estas secciones contiene los campos con su respectiva configuración.  **infobasica (objeto):** Corresponde al nombre de cada una de las secciones de información del catálogo de terceros, las secciones son: infobasica, listadirecciones, listacontactos, datosvendedor, lineasproductos, listaeleminvproveedor, listaproductoscomprados.  Cada sección contiene objetos con los campos del catálogo de terceros, la llave del objeto es el nombre del campo y su valor contiene la configuración, así:  **bvisible:** Contiene T cuando el campo es visible en el formulario y F cuando no lo es.  **blectura:** Contiene T cuando el campo es de solo lectura (es decir, no se puede modificar) y F cuando no lo es.  **brequerido:** Contiene T cuando el campo es obligatorio en el formulario y F cuando es opcional.  **etiqueta:** contiene el nombre del campo definido por el usuario. Hay algunos campos que permiten configurar su nombre según las necesidades del usuario, en ese caso, este parámetro contendrá el nombre que definió el usuario para el campo.  **valorpordefecto**: valor por defecto configurado por el usuario para el campo.  Cuando el campo es de tipo lista, el objeto contendrá adicionalmente los siguientes campos:  **itdlista**: Código identificador del tipo de lista, influye en el tipo de campo que se debe presentar en el formulario. Los tipos son:  **0: Edit** – Corresponde a una caja de texto normal.  **1: Autolista** – Cuando la configuración retorne este tipo de lista, se debe llamar la función GetAutoLista de la clase TCatTerceros y enviar el nombre del campo para poder obtener los valores que se deben presentar en el listado, adicionalmente si ncampofiltro (que se describe más adelante) retorna algún valor, al llamar la función GetAutoLista se debe enviar dicho campo de filtro.   **2: Autolistacatalogo:** Corresponde a un campo de tipo selector, cuando llegue este tipo de lista se debe llamar la función GetListaSeleccion de la clase que llegue en el parámetro “ncatalogo”.  **ncampofiltro:** Nombre del campo de filtro que se debe aplicar al obtener la autolista, esto cuando el itdlista es 1.  **ncatalogo:** nombre de la clase de la cual se debe llamar la función GetListaSelección cuando el itdlista es 2.  **beditable:** aplica para cuando el itdlista es 1, cuando contenga F indica que el campo debe ser un combobox (pues el campo corresponde a una tabla de usuario) y cuando contenga T indica que el campo debe ser un combobox editable.  **cfg (objeto):** Objeto que contiene las configuraciones adicionales que se deben tener en cuenta para el manejo del catálogo de terceros, estas configuraciones pueden ser definidas por el usuario. Las configuraciones del catálogo de terceros son:  **bcaracteresespecialesennit:** Contiene T cuando es posible definir caracteres especiales como guión medio “-”, guión bajo “\_” y punto “.” en el código del tercero. Contiene F cuando el código del tercero no puede contener ningún carácter especial.  **separadordigverificacion:** Contiene el caracter que se usará para separar el código del tercero y el dígito de verificación en informes y reportes.  **bapellidotercero:** contiene T cuando se debe manejar el nombre y el apellido del tercero en campos independientes, es decir, en el formulario de edición del tercero se debe presentar un campo para ingresar el nombre y otro para ingresar el apellido. Cuando este parámetro contenga F se debe manejar en un solo campo el nombre y el apellido del tercero.  **ordennombreapellido:** Contiene T cuando se debe mostrar primero el campo nombre tercero y luego el campo apellido tercero; y contiene F cuando se debe mostrar primero el apellido y luego el nombre.  **bverifycupocredito:** Contiene T cuando al procesar operaciones que permitan crear cuentas por cobrar, se verifica que el saldo total por cobrar al tercero no exceda el cupo de crédito definido para él. Y contiene F cuando no se realiza dicha verificación  **bverifyvencimientomaximo:** Contiene T cuando al procesar operaciones que permitan crear cuentas por cobrar, se verifica que el vencimiento máximo de las cuentas por cobrar al tercero no exceda el número de días máximo que se puede tardar un tercero para saldar una deuda.  **qdiasplazocxcparacontado:** Cantidad de días de plazo máximo de CxC para considerar el pago como de contado.  **bactivarcomisiones:** contiene T cuando está activado el manejo de comisiones a vendedores, en ese caso, se debe: 1 En el formulario de edición de un tercero, en la pestaña Datos vendedor, se debe habilitar el registro de autorizaciones para configurar las comisiones que recibe el vendedor. 2 En el formulario de edición de un elemento de inventario se habilita la opción “Genera comisión para vendedores”.  **itdbasecomisionventasdef:** Identificador del tipo de base para el cálculo de la comisión en ventas que se da a los vendedores. Los tipos de base son:  **F:** Facturado sin impuestos **T:** Facturado con impuestos **C**: Base comisionable (antes de impuestos).  **itdbasecomisionrecaudodef:** Identificador del tipo de base para el cálculo de la comisión por recaudo que se da a los vendedores. Los tipos de base son:  **R:** Total abonado **C:** Abonado comisionable. |
| seguridaddedatos | Boolean | Retorna true siempre y cuando se aplique seguridad de datos al obtener la lista de terceros. Si no se aplica seguridad de datos en el catÃ¡logo de terceros, este campo no se retornarÃ¡. |

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
