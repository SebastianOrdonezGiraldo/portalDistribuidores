# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/030-DoExecuteOprAction.html

Â¿CÃ³mo hacer la ejecuciÃ³n de la operaciÃ³n?

DoExecuteOprAction (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Función que permite la ejecución de una acción sobre una o varias operaciones según el caso. Las acciones que se pueden ejecutar son:

- Crear una operación vacía.
- Guardar sobrescribiendo la información para una operación existente.
- Crear y guardar una operación en una sola petición.
- Adicionar registros de información a una operación existente.
- Cargar la información de una operación existente.
- Procesar operaciones (es posible ejecutar esta acción para una o varias operaciones).
- Desprocesar operaciones (es posible ejecutar esta acción para una o varias operaciones)
- Verificar operaciones (es posible ejecutar esta acción para una o varias operaciones)
- Anular operaciones (es posible ejecutar esta acción para una o varias operaciones)
- Eliminar operaciones (es posible ejecutar esta acción para una o varias operaciones)
- Solicitar datos calculados para una operación existente.

Para ejecutar alguna de las acciones descritas anteriormente, es necesario conocer el tipo de operación sobre la que se realizará la acción, en algunos casos es necesario conocer el número de la operación sobre la que se ejecutará la acción.

Para conocer los tipos de operación (itdoper) disponibles, consulte el documento “TiposDeOperaciones” de la zona de “Documentación de apoyo”.

#### Resultado

Retorna un Json con un listado de terceros registrados en el sistema.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura:  **accion**: Especifica la acción que se va a realizar sobre las operaciones. Puede ser:  **NEW:** Crea un nuevo registro para una operación del tipo especificado en el parámetro “itdoper”. Esta acción retorna el número de operación “inumoper” asignado.  **LOAD:** Carga la información de la operación especificada en el parámetro “inumoper”. En inumoper solo se permite un dato, es decir, solo se puede cargar una operación a la vez.  **SAVE:** Graba la información dada en el parámetro “oprdata” para la operación especificada en el parámetro “itdoper”. En el parámetro “inumoper” solo se permite un dato, es decir, solo se puede grabar una operación a la vez.  **CREATE:** Crea la operación y guarda la información dada en el parámetro “oprdata”. Esta acción retorna el número de operación “inumoper” asignado.  **APPEND:** Adiciona la información de los listados de datos dados en el parámetro “oprdata”, para la operación especificada; es decir que la información que se guarde con esta acción no reemplazará la ya existente, sino que se adicionará al final de cada listado. Para los datos simples (que no son listas) los datos sí son reemplazados. Ejemplo de uso: adición de productos a una operación de factura o pedido existente.  **PROCESS**: Procesa las operaciones dadas en el parámetro “inumoper”.  **UNPROCESS**: Desprocesa las operaciones dadas en el parámetro “inumoper”.  **VERIFY:** Verifica las operaciones dadas en el parámetro “inumoper”.  **ANULAR:** Anula las operaciones dadas en el parámetro “inumoper”.  **DELETE:** Borra las operaciones dadas en el parámetro “inumoper”.  **MTOTALAPAGAR**: Obtiene el total a pagar neto de la operación.  **EMAILSOPR:** Obtiene los emails de los terceros involucrados en la operación con la siguiente información: rol del tercero en la operación (cliente, vendedor, etc.), nombre del tercero, email.  **ROLESOPR:** Retorna los diferentes roles de terceros que aplican para la operación (cliente, vendedor, recaudador, vendedor 2, etc.).  **CALCULARIMPUESTOS:** (Solo aplica inicialmente para la operación de ventas): Calcula todos los impuestos automáticos según la configuración de la operación, la clasificación legal de la empresa y la clasificación tributaria del tercero. Si en “oprdata” se envían los datos de la operación, los calcula y retorna dentro del mismo JSON. Si es para una operación ya existente, se envía los números de operación en “operaciones”.  **CALCULARYGRABARIMPUESTOS:** Calcula todos los impuestos automÃ¡ticos segÃºn la configuraciÃ³n de la operaciÃ³n, la clasificaciÃ³n legal de la empresa y la clasificaciÃ³n tributaria del tercero, y adicionalmente guarda esta informaciÃ³n en la base de datos, asociÃ¡ndola directamente a la operaciÃ³n. A diferencia de la acciÃ³n CALCULARIMPUESTOS, que Ãºnicamente realiza un cÃ¡lculo temporal y retorna los valores en el JSON sin persistirlos, esta acciÃ³n realiza el cÃ¡lculo sobre una operaciÃ³n ya existente y lo deja aplicado de forma definitiva en el sistema, se deben enviar los nÃºmeros de operaciÃ³n en el parÃ¡metro âoperacionesâ.  **LOADCONFIG:** Obtiene unos atributos básicos de configuración de la operación que faciliten la implementación de una interfaz para la edición de la información.  **GETDATAIRECURSO:** (Solo aplica para operaciones de ventas, pedidos y cotización): Retorna un objeto JSON con toda la información necesaria para ordenar, cotizar o vender el producto de un número de registro indicado. Dentro de los datos que se calculan están: CC por defecto, porcentaje de descuento que aplica, porcentaje de IVA, precio de lista, datos calculados según expresiones de cálculo establecidas, etc. Para indicar qué registro de producto va a ser usado para el cálculo de esta acción, se envía un campo adicional “objindex” (0..n) con el número de registro de la lista (de operación existente o según datos del JSON “oprdata”).  **operaciones**: Arreglo de objetos donde cada elemento lleva:  inumoper: Indica el número de operación sobre la que se ejecutara la acción.  **itdoper:** Determina el tipo de operación sobre la que se realizara la acción, los tipos de operación se encuentran en el documento: “TiposDeOperaciones” de la zona de “Documentación de apoyo”.  **oprdata:** Objeto que contiene la información de la operación que se va a guardar cuando la acción es SAVE, CREATE o cualquiera de las de cálculo de datos (MTOTALAPAGAR, EMAILSOPRS, CALCULARIMPUESTOS, etc.). En los demás casos no se necesita la información completa de la operación.  Para conocer la estructura del objeto Json de cada operación, se debe consultar la documentación correspondiente a la operación. | Los ejemplos de cada una de las acciones, se encuentran en la zona “Ejemplo de la URL del llamado de la función” de este documento. |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

NEW
LOAD
SAVE
APPEND
CREATE
PROCESS
UNPROCESS
VERIFY
ANULAR
DELETE
MTOTAL\_A\_PAGAR
EMAILSOPR
ROLESOPR
CALCULARIMPUESTOS
CALCULARYGRABARIMPUESTOS
LOADCONFIG
GETDATA\_IRECURSO

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "NEW",
"operaciones": [
{
"itdoper": "ORD4"
}
]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "LOAD",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "SAVE",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
],
"oprdata": {
"datosprincipales": {
"init": "0310034132",
"initvendedor": "0471006589",
"finicio": "05/21/2014",
"qdias": "15",
"ilistaprecios": "1",
"sobservenc": "",
"bregvrunit": "F",
"qporcdescuento": "0",
"bregvrtotal": "F",
"frmenvio": "3",
"frmpago": "1",
"condicion1": "1",
"blistaconiva": "F",
"busarotramoneda": "F",
"imonedaimpresion": "",
"mtasacambio": "0",
"ireferencia": "",
"bcerrarref": "F",
"itdprintobs": "-1",
"icontactocliente": "0"
},
"encabezado": {
"iemp": "1",
"inumoper": "34972",
"itdsop": "34",
"fsoport": "05/21/2014",
"iclasifop": "0",
"imoneda": "10",
"iprocess": "0",
"banulada": "F",
"inumsop": "0",
"snumsop": "",
"tdetalle": "CotizaciÃ³n del cliente Antonio Madera"
},
"formacobro": {
},
"liquidacion": {
"parcial": "0",
"descuento": "0",
"iva": "0",
"total": "1750000"
},
"listaproductos": [
{
"iinventario": 0,
"irecurso": "C01140",
"itiporec": "",
"qrecurso": 1,
"mprecio": "550000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "550000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": ""
},
{
"iinventario": 0,
"irecurso": "C01145",
"itiporec": "",
"qrecurso": 1,
"mprecio": "1200000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "1200000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": ""
}
],
"listaanexos": [
],
"qoprsok": "0"
}
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "APPEND",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
],
"oprdata": {
"listaproductos": [
{
"iinventario": 0,
"irecurso": "C01140",
"itiporec": "",
"qrecurso": 1,
"mprecio": "550000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "550000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": ""
},
{
"iinventario": 0,
"irecurso": "C01145",
"itiporec": "",
"qrecurso": 1,
"mprecio": "1200000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "1200000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": ""
}
]
}
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "CREATE",
"operaciones": [
{
"itdoper": "ORD4"
}
],
"oprdata": {
"datosprincipales": {
"init": "0310034132",
"initvendedor": "0471006589",
"finicio": "05/21/2014",
"qdias": "15",
"ilistaprecios": "1",
"sobservenc": "",
"bregvrunit": "F",
"qporcdescuento": "0",
"bregvrtotal": "F",
"frmenvio": "3",
"frmpago": "1",
"condicion1": "1",
"blistaconiva": "F",
"busarotramoneda": "F",
"imonedaimpresion": "",
"mtasacambio": "0",
"ireferencia": "",
"bcerrarref": "F",
"itdprintobs": "-1",
"icontactocliente": "0"
},
"encabezado": {
"iemp": "1",
"itdsop": "34",
"fsoport": "05/21/2014",
"iclasifop": "0",
"imoneda": "10",
"iprocess": "0",
"banulada": "F",
"inumsop": "0",
"snumsop": "",
"tdetalle": "CotizaciÃ³ndel cliente Antonio Madera"
},
"formacobro": {
},
"liquidacion": {
"parcial": "0",
"descuento": "0",
"iva": "0",
"total": "1750000"
},
"listaproductos": [
{
"iinventario": 0,
"irecurso": "C01140",
"itiporec": "",
"qrecurso": 1,
"mprecio": "550000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "550000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": ""
},
{
"iinventario": 0,
"irecurso": "C01145",
"itiporec": "",
"qrecurso": 1,
"mprecio": "1200000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "1200000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": ""
}
],
"listaanexos": [
],
"qoprsok": "0"
}
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "PROCESS",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "UNPROCESS",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "VERIFY",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "ANULAR",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "DELETE",
"operaciones": [
{
"inumoper": "34972",
"itdoper": "ORD4"
}
]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "MTOTALAPAGAR",
"operaciones": [{
"inumoper": "34973",
"itdoper": "ING1"
}]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "EMAILSOPR",
"operaciones": [{
"inumoper": "34972",
"itdoper": "ORD4"
}]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "ROLESOPR",
"operaciones": [{
"inumoper": "34973",
"itdoper": "ING1"
}]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "CALCULARIMPUESTOS",
"operaciones": [
{
"itdoper": "ING1"
}
],
"oprdata": {
"datosprincipales": {
"init": "10212121",
"iinventario": "2",
"initvendedor": "14856296",
"initvendedor2": "",
"busarotramoneda": "F",
"sobserv": "SOPORTE API",
"imoneda": "10",
"qporcdescuento": "0.0000",
"ilistaprecios": "1",
"blistaconiva": "F",
"isucursalcliente": "-1",
"bfacturaexportacion": "F",
},
"encabezado": {
"iemp": "1",
"inumoper": "",
"tdetalle": "Ventas 01-02-2019",
"itdsop": "2",
"inumsop": "",
"snumsop": "",
"fsoport": "02/01/2019",
"iclasifop": "0",
"imoneda": "10",
},
"listaproductos": [
{
"iinventario": "2",
"irecurso": "EQP003",
"nrecurso": "COMPUTADOR CLON 1",
"nunidad": "Und",
"qproducto": "1.0000",
"mprecio": "1350000.0000",
"qporcdescuento": "0.0000",
"mvrtotal": "1350000.0000",
"sobserv": "PC Clon con todos los accesorios.",
"qporciva": "19.0000",
"icc": "10101",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "0.0000",
"valor2": "0.0000",
"valor3": "0.0000",
"valor4": "0.0000",
}
],
}
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "CALCULARYGRABARIMPUESTOS",
"operaciones": [
{
"itdoper": "ING1"
"inumoper": ""
}
],
"oprdata": {}
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"accion": "LOADCONFIG",
"operaciones": [{
"itdoper": "ING1"
}]
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

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatOperaciones/"DoExecuteOprAction"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON ={
"accion": "GETDATAIRECURSO",
"operaciones": [{
"itdoper": "ING1"
}],
"oprdata": {
"datosprincipales": {
"init": "0310034132",
"initvendedor": "0471006589",
"initvendedor2": "",
"ilistaprecios": "1",
"sobservenc": "",
"bregvrunit": "F",
"qporcdescuento": "0",
"bregvrtotal": "F",
"blistaconiva": "F",
"busarotramoneda": "F",
"imonedaimpresion": "",
"mtasacambio": "0",
"ireferencia": "",
"bcerrarref": "F",
"itdprintobs": "-1",
"isucursalcliente": "0"
},
"encabezado": {
"iemp": "1",
"itdsop": "1",
"fsoport": "05/21/2014",
"iclasifop": "0",
"imoneda": "10",
"iprocess": "0",
"banulada": "F",
"inumsop": "0",
"snumsop": "",
"tdetalle": "Venta productos a Antonio Madera"
},
"formacobro": {},
"liquidimpuestos": {},
"listaproductos": [{
"iinventario": 0,
"irecurso": "C01140",
"itiporec": "",
"qrecurso": 1,
"mprecio": "550000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "550000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": ""
}, {
"iinventario": 0,
"irecurso": "C01145",
"itiporec": "",
"qrecurso": 1,
"mprecio": "1200000.0000",
"qporcdescuento": 0,
"qporciva": "0",
"mvrtotal": "1200000",
"sobserv": "",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "",
"valor2": "",
"valor3": "",
"valor4": "",
"qproducto2": ""
}],
"listaanexos": [],
"qoprsok": "0"
},
"objindex": "0"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/030-DoExecuteOprAction.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

NEW
LOAD
SAVE
APPEND
CREATE
PROCESS
UNPROCESS
VERIFY
ANULAR
DELETE
MTOTAL A PAGAR
EMAILSOPR
ROLESOPR
CALCULAR IMPUESTOS
CALCULAR Y GRABAR IMPUESTOS
LOADCONFIG
GETDATA IRECURSO

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "224"
},
"respuesta": {
"datos": {
"iemp": "1",
"inumoper": "34972",
"itdsop": "34",
"fsoport": "05/21/2014",
"iclasifop": "0",
"imoneda": "10",
"iprocess": "0",
"banulada": "F",
"qoprsok": "0"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "221"
},
"respuesta": {
"datos": {
"encabezado": {
"iemp": "1",
"inumoper": "34972",
"itdsop": "34",
"fsoport": "05/21/2014",
"iclasifop": "0",
"imoneda": "10",
"iprocess": "0",
"banulada": "F"
},
"liquidacion": {
"parcial": "0",
"descuento": "0",
"iva": "0",
"total": "0"
},
"datosprincipales": {
"init": "10530547",
"initvendedor": "10365201",
"finicio": "30/12/1899",
"qdias": "0",
"ilistaprecios": "0",
"sobservenc": "",
"sobservpie": "",
"bregvrunit": "F",
"qporcdescuento": "0",
"bregvrtotal": "F",
"frmenvio": "0",
"frmpago": "0",
"condicion1": "0",
"blistaconiva": "F",
"busarotramoneda": "F",
"imonedaimpresion": "",
"mtasacambio": "0",
"ireferencia": "",
"bcerrarref": "F",
"itdprintobs": "1",
"icontactocliente": "0"
},
"listaproductos": [
],
"listaanexos": [
],
"qoprsok": "0"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "261"
},
"respuesta": {
"datos": {
"resultado": "T",
"qoprsok": "0"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "261"
},
"respuesta": {
"datos": {
"resultado": "T",
"qoprsok": "0"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "261"
},
"respuesta": {
"datos": {
"resultado": "T",
"qoprsok": "0",
"inumoper": "147584"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "619"
},
"respuesta": {
"datos": {
"resultado": "T",
"errores": "0",
"advertencias": "0",
"bitacora": "  
VerificaciÃ³n...<\/span><\/font>  
  
  
size:16px\">Proceso...<\/span><\/font>  
  
  
Proceso CreaciÃ³n de una cotizaciÃ³n...<\/span><\/font>  
  
  
<\/body>",
"qoprsok": "1"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "295"
},
"respuesta": {
"datos": {
"resultado": "T",
"qoprsok": "1"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "619"
},
"respuesta": {
"datos": {
"resultado": "T",
"errores": "0",
"advertencias": "0",
"bitacora": "VerificaciÃ³n...<\/span><\/font><\/body>",
"qoprsok": "1"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "128"
},
"respuesta": {
"datos": {
"resultado": "T",
"qoprsok": "0"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "261"
},
"respuesta": {
"datos": {
"resultado": "T",
"qoprsok": "0"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "562"
},
"respuesta": {
"datos": {
"resultado": {
"mtotalapagar": "150000"
}
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "562"
},
"respuesta": {
"datos": {
"resultado": [
{
"etiqueta": "cliente",
"nombre": "Pablo Arciniegas",
"email": "parciniegas@gmail.com"
},
{
"etiqueta": "vendedor",
"nombre": "Sandra Cardona",
"email": "scardona@mpcomputadores.com"
}
]
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "562"
},
"respuesta": {
"datos": {
"resultado": [
"1|cliente",
"2|vendedor",
"3|vendedor 2"
]
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "562"
},
"respuesta": {
"datos": {
"resultado": {
"liquidimpuestos": [
{
"iconcepto": "IVAV16VL",
"nconcepto": "IVA liquidado",
"isigno": "+",
"mvalorbase": "290000.0000",
"qpercent": "16.0000",
"mvalor": "46400.0000",
"bautocalc": "S",
"icuenta": "24080116",
"initcxx": "800197268",
"iasiento": "C",
"mvrbasemin": "0.0000",
"bdefecto": "N",
"sobserv": "",
"custom": "F",
"itdsop": "10",
"inumsop": "FV-000068",
"init": "900631860",
"fpago": "11/13/2014",
"icc": "",
"iaccion": "2",
"icuentaiesinsigno": "",
"itablapago": "IVA",
"bsistema": "T",
"mvrotramoneda": "0.0000"
},
{
"iconcepto": "RCREE03",
"nconcepto": "Impuesto CREE 0.4%",
"isigno": "",
"mvalorbase": "310000.0000",
"qpercent": "0.4000",
"mvalor": "1240.0000",
"bautocalc": "S",
"icuenta": "13559003",
"initcxx": "800197268",
"iasiento": "D",
"mvrbasemin": "1.0000",
"bdefecto": "N",
"sobserv": "",
"custom": "F",
"itdsop": "10",
"inumsop": "FV-000068",
"init": "900631860",
"fpago": "11/13/2014",
"icc": "",
"iaccion": "1",
"icuentaiesinsigno": "23659003",
"itablapago": "RETENCION",
"bsistema": "T",
"mvrotramoneda": "0.0000"
}
]
}
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "58",
"version": "1.0"
},
"respuesta": {
"datos": {
"resultado": {
"datosprincipales": {
"init": "222222222222",
"initvendedor": "",
"initvendedor2": "",
"busarotramoneda": "F",
"sobserv": "",
"imoneda": "",
"bshowsupportinfo": "F",
"bregvrunit": "T",
"bshowcntfields": "F",
"bregvrtotal": "F",
"blistaconiva": "F",
"blistaconotrosimpuestos": "F",
"bfactporpedido": "T",
"bimprimirdescfinan": "T",
"bautocalcularcomisiones": "T",
"sperfilyreferencias": "",
"iws": "",
"fhultcfdigenerado": "12/30/1899",
"ntercero": "",
"nterceroprincipal": "CONSUMIDOR FINAL",
"incoterms": "",
"bfacturaexportacion": "F",
"itipooperacionfe": "11",
"iordencompra": "",
"benviarcontingenciadian": "F",
"iinventario": "1",
"mtasacambio": "1.0",
"qporcdescuento": "0.0",
"ilistaprecios": "1",
"icuentaporfacturar": "-1",
"qprecisionprecio": "0",
"qprecisionliquid": "0",
"isucursalcliente": "-1",
"mcambio": "0.0",
"mavance": "0.0",
"qregproductos": "1",
"qregreferencias": "0",
"qregingresos": "0",
"qregconcdescuento": "1",
"qregcomisiones": "0",
"qregseriesproductos": "0"
},
"listaproductos": [
{
"icc": "1",
"irecurso": "1",
"nrecurso": "",
"nunidad": "",
"itiporec": "",
"sobserv": "",
"bempresaasumeiva": "T",
"iconceptoivafaltante": "IVAD19",
"fhini": "12/30/1899",
"fhfin": "12/30/1899",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"iinventario": "1",
"qproducto": "1.0",
"mprecio": "250000.0",
"qporcdescuento": "0.0",
"mvrtotal": "250000.0",
"qporciva": "19.0",
"qporcinc": "0.0",
"mvrinc": "0.0",
"mvribua": "0.0",
"qporcicui": "0.0",
"mcostoun": "0.0",
"valor1": "0.0",
"valor2": "0.0",
"valor3": "0.0",
"valor4": "0.0",
"qproducto2": "0.0"
}
],
"series": [],
"ingresosegresos": [],
"liquidimpuestos": [
{
"iconcepto": "IVAV16VL",
"nconcepto": "IVA liquidado",
"isigno": "+",
"mvalorbase": "250000.0000",
"qpercent": "19.0000",
"mvalor": "47500.0000",
"bautocalc": "S",
"icuenta": "24080119",
"initcxx": "800197268",
"iasiento": "C",
"mvrbasemin": "0.0000",
"bdefecto": "N",
"sobserv": "",
"custom": "F",
"itdsop": "10",
"inumsop": "FV-73",
"init": "222222222",
"fpago": "05/14/2026",
"icc": "",
"iaccion": "2",
"icuentaiesinsigno": "",
"itablapago": "IVA",
"bsistema": "T",
"mvrotramoneda": "0.0000"
}
],
"formacobro": {
"fcobrocaja": [
{
"icuenta": "110505",
"init": "222222222",
"icc": "",
"itipotransaccion": "",
"itransaccion": "",
"iflujoefec": "",
"beditvrotramoneda": "F",
"id": "1",
"ilineamov": "1",
"mvalor": "250000.0",
"mvrotramoneda": "0.0"
}
],
"fcobrobanco": [],
"fcobrocxc": [],
"fcobroamortcxp": [],
"mtotalreg": "250000.0",
"mtotalpago": "250000.0"
},
"listacomisionesvta": [],
"listaanexos": [],
"encabezado": {
"tdetalle": "Ventas 06-04-2026",
"itdoper": "ING1",
"snumsop": "FV-73",
"fsoport": "04/06/2026",
"iccbase": "",
"imoneda": "",
"banulada": "F",
"blocal": "T",
"bniif": "T",
"svaloradic1": "",
"svaloradic2": "",
"svaloradic3": "",
"svaloradic4": "",
"svaloradic5": "",
"svaloradic6": "",
"svaloradic7": "",
"svaloradic8": "",
"svaloradic9": "",
"svaloradic10": "",
"svaloradic11": "",
"svaloradic12": "",
"fecha1adic": "12/30/1899",
"fecha2adic": "12/30/1899",
"fecha3adic": "12/30/1899",
"datosaddin": "",
"fcreacion": "04/08/2026",
"fultima": "04/08/2026",
"fprocesam": "4/8/2026 12:8:44.500",
"iusuario": "ADMIN",
"iusuarioult": "ADMIN",
"isucursal": "",
"inumoperultimp": "",
"bespadre": false,
"bconfirmaenviofe": true,
"accionesalgrabar": "",
"iemp": "1",
"inumoper": "300",
"itdsop": "10",
"inumsop": "73",
"iclasifop": "0",
"iprocess": "2",
"inumoperpadre": "0",
"mtotaloperacion": "250000.0"
},
"totalesimpuestos": [
{
"iva - impuesto sobre las ventas": "47500"
}
]
},
"qoprsok": "1"
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "562"
},
"respuesta": {
"datos": {
"resultado": {
"bexigetercero": "T",
"bexigevendedor": "T",
"bpermitenoiva": "F",
"bpermiteedicionliquidacion": "F",
"bpermiteconfigprecio": "F",
"bactivarcfgcomisionesvendedor": "T",
"bpermitereferenciasfactura": "F",
"bpermiteperfilparticularfactura": "F",
"bvercfgcomisionesvendedorfactura": "F",
"bmodificarcfgcomisionesvendedorfactura": "F",
"finicomisiones": "01-01-2016",
"qprecisiondecimales": "2",
"sfieldnameirecurso": "irecurso",
"iccdef": "",
"bdescuentoventas": "T",
"bcalculariva": "T",
"bviewntercero": "F",
"bpermitiralquilerelemcontrol": "F",
"listaconfigcolproductos": [
"",
"T|F||||",
"T|F||||",
"",
"T|F||||",
"",
"",
"",
"",
"",
"",
"",
"",
""
]
}
}
}
}
]
}

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "562"
},
"respuesta": {
"datos": {
"resultado": {
"icc": "3VEN",
"iinventario": "1",
"irecurso": "SSDMP1",
"nrecurso": "DISCO DURO MP 240GB SSD",
"nunidad": "",
"itiporec": "",
"qproducto": "2.0000",
"mprecio": "345000.0000",
"qporcdescuento": "0.0000",
"mvrtotal": "690000.0000",
"sobserv": "",
"qporciva": "16.0000",
"fhini": "12/30/1899",
"fhfin": "12/30/1899",
"dato1": "",
"dato2": "",
"dato3": "",
"dato4": "",
"dato5": "",
"dato6": "",
"valor1": "0.0000",
"valor2": "0.0000",
"valor3": "0.0000",
"valor4": "2.5000",
"qproducto2": "2.0000"
}
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” que se describe a continuación:  **datos (objeto):** objeto cuya llave corresponde al código del tipo de operación y el valor corresponde a la cantidad de operaciones existente.   La documentación de los tipos de operación existentes en el sistema se encuentra en el documento “TiposDeOperacion” de la zona de “Documentación de apoyo”. |

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
