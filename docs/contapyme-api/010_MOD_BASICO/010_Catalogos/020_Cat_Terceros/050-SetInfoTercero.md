# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/050-SetInfoTercero.html

Â¿CÃ³mo establecer la informaciÃ³n de un tercero?

SetInfoTercero (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de modificar la información de un tercero en la base de datos, recibe los datos que se actualizarán agrupados por secciones. Si no se envía una sección el agente la omitirá, pero si se envía una sección vacía la información que haya almacenada en la base de datos se eliminará. Por ejemplo, si se envía la sección “lista de direcciones” así: {"listadirecciones":[]} el sistema eliminará toda la información que haya registrada de la lista de otras direcciones del tercero.

#### Resultado

Retorna un Json con la confirmaciÃ³n de la modificaciÃ³n de los datos del tercero.

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
| dataJSON | JSON | Json que contiene en su interior la información a modificar del tercero, contiene la siguiente estructura.  **init:** Identificador único del tercero. (requerido).  **infobasica:** Contiene la información básica o principal del tercero. La documentación de la sección se encuentra en el documento "InfoBasica" de la zona “Documentación de apoyo”.  **tipotercero :** Arreglo que contiene en su interior objetos con los códigos de cada tipo de tercero a los que aplica el registro. La documentación de la sección se encuentra en el documento "TipoTercero" de la zona de “Documentación de apoyo”.  **listacontactos** : Arreglo que contiene en su interior objetos con los contactos que pertenecen a un tercero. La documentación de la sección se encuentra en el documento "ListaContactos" de la zona de “Documentación de apoyo”.  **conceptosnominacontable**: Arreglo que contiene en su interior objetos con la información de la nómina contable asociada a un tercero. La documentación de la sección se encuentra en el documento "ConceptosNominaContable" de la zona de “Documentación de apoyo”.  **entidadesempleado** : Arreglo que contiene en su interior objetos con la información de las entidades a las que puede estar afiliado un tercero de tipo empleado o vendedor. La documentación de la sección se encuentra en el documento "EntidadesEmpleado" de la zona de “Documentación de apoyo”.  **datosvendedor** : Arreglo que contiene en su interior objetos con la información de los perfiles que tienen asignado un vendedor. La documentación de la sección se encuentra en el documento "DatosVendedor" de la zona de “Documentación de apoyo”  **lineasproductos**: Arreglo que contiene en su interior objetos con la información de los productos que ofrece un tercero de tipo proveedor. La documentación de la sección se encuentra en el documento "LineasProductos" de la zona de “Documentación de apoyo”.  **listaeleminvproveedor**: Arreglo que contiene en su interior objetos con la información de los productos que ofrece un tercero de tipo proveedor. La documentación de la sección se encuentra en el documento "ListaElemInvProveedor" de la zona de “Documentación de apoyo”.  **listadirecciones**: Arreglo que contiene en su interior objetos con la información de otras direcciones de un tercero. La documentación de la sección se encuentra en el documento "ListaDirecciones" de la zona de “Documentación de apoyo”. | { "init": "10538142", "infobasica": { "scargo": "Administrador", "tdireccion": "Cra 23 # 5 â21", "sbarrio": "Los alcazares", "ttelefono": "8850788", "tcelular": "3217258966", "semail": "pperez56@gmail.com" }, "listacontactos": [ { "init": "10538142", "ilinea": "0", "nnombre": "MarÃ­a JosÃ©", "napellido": "GÃ³mez", "sobservaciones": "", "stratamiento": "SeÃ±ora", "isexo": "F", "iinterno": "0" }, { "iinterno": "1", "nnombre": "Juan Carlos ", "napellido": "Jimenez", "isexo": "M", "stratamiento": "SeÃ±or", "sprofesion": "Contador", "ttelefono": "8974455", "tcelular": "3185240058", "semail": "jcjimenez5@hotmail.com", "sobservaciones": "" } ] } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatTerceros/"SetInfoTercero"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"init": "10538142",
"infobasica": {
"scargo": "Administrador",
"tdireccion": "Cra 23 # 5 â21",
"sbarrio": "Los alcazares",
"ttelefono": "8850788",
"tcelular": "3217258966",
"semail": "pperez56@gmail.com"
},
"listacontactos": [
{
"init": "10538142",
"ilinea": "0",
"nnombre": "MarÃ­a JosÃ©",
"napellido": "GÃ³mez",
"sobservaciones": "",
"stratamiento": "SeÃ±ora",
"isexo": "F",
"iinterno": "0"
},
{
"iinterno": "1",
"nnombre": "Juan Carlos ",
"napellido": "Jimenez",
"isexo": "M",
"stratamiento": "SeÃ±or",
"sprofesion": "Contador",
"ttelefono": "8974455",
"tcelular": "3185240058",
"semail": "jcjimenez5@hotmail.com",
"sobservaciones": ""
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

âº EJECUTAR CODIGO

Ver otros ejemplos en:
[PHP](../../../ejemplo/PHP.html) ,
[JAVA](../../../ejemplo/JAVA.html),
[C#](../../../ejemplo/Csharp.html),
[Visual Basic.net](../../../ejemplo/visualBasic.html),
[Visual Basic 6](../../../ejemplo/visualBasic6.html),
[Delphi.](../../../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/050-SetInfoTercero.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "25"
},
"respuesta": {
"datos": {
"modificar": "true"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **modificar (varchar):** contiene true cuando el tercero se modifica satisfactoriamente y false cuando no se pudo modificar. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 50: El usuario no posee permisos para: âabrir el catÃ¡logo de tercerosâ.
- 130: No se ingresÃ³ el parÃ¡metro "X".
- 190: El registro no existe.
- 250: No se tiene permisos sobre el registro por seguridad de datos, por lo tanto no se puede ejecutar la acciÃ³n.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
