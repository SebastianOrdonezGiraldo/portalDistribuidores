# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/010_Catalogos/020_GruposInv/050-SetInfoGrupoInv.html

Â¿CÃ³mo establecer la informaciÃ³n de un grupo del inventario?

SetInfoGrupoInv (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la información de un listado de grupos de inventario registrados en el sistema.  
Esta función retorna para cada grupo de inventario la información que sea solicitada.

Esta función es la encargada de modificar la información de un grupo de inventario en la base de datos, recibe los datos que se actualizarán agrupados por secciones, si no se envía una sección el agente la omitirá, pero si se envía una sección vacía la información que haya almacenada en la base de datos se eliminará.   
Por ejemplo, si se envía la sección “listacuentasegresos” así: {"listacuentasegresos":[ ]} el sistema eliminará toda la información que haya registrada de las cuentas de egreso según la clase contable del centro de costos.

#### Resultado

Retorna un Json con la confirmaciÃ³n de la modificaciÃ³n de los datos del grupo de inventario.

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
| dataJSON | JSON | Json que contiene en su interior la información a modificar del grupo de inventario, contiene la siguiente estructura:  **igrupoinv:** Identificador del grupo de inventario. (requerido)  Secciones  **Infobasica:** Contiene la información básica del grupo de inventario y la configuración de cuentas e impuestos que se asignan al grupo de inventario, la documentación de esta sección la puede encontrar en el documento “InfoBasica” que se encuentra en la zona de “Documentación de apoyo”  **Listacuentasegresos:** Contiene la información de las cuentas de egreso que se han configurado según la clase contable del centro de costos, esto aplica cuando además de tener la cuenta de egresos del grupo de inventario definida, se configuran otras cuentas para que los egresos se imputen a ellas según la clase contable del centro que se afecta.  La documentación de los campos de esta sección los puede encontrar en el documento “InfoCuentasIngresosEgresos” que se encuentra en la zona “Documentación de apoyo”.  **listacuentasingresos:** Contiene la información de las cuentas de ingreso que se han configurado según la clase contable del centro de costos, esto aplica cuando además de tener la cuenta de ingresos del grupo de inventario definida, se configuran otras cuentas para que los ingresos se imputen a ellas según la clase contable del centro que se afecta. La documentación de los campos de esta sección los puede encontrar en el documento “InfoCuentasIngresosEgresos” que se encuentra en la zona “Documentación de apoyo”. | { "igrupoinv": "C01520", "infobasica": { "bconsumo": "T", "icuentaegr": "710101", "bicuentasporclaseegr": "T" }, "listacuentasegresos": [ { "iinterno": "0", "itdcc": "1", "icuenta": "512025" } ] } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatGrupoInv/"SetInfoGrupoInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"igrupoinv": "C01520",
"infobasica": {
"bconsumo": "T",
"icuentaegr": "710101",
"bicuentasporclaseegr": "T"
},
"listacuentasegresos": [
{
"iinterno": "0",
"itdcc": "1",
"icuenta": "512025"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/050-SetInfoGrupoInv.pdf)

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
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje** (**varchar**): Código del mensaje de eventualidad o error en caso de presentarse. **mensaje** (**varchar**): Mensaje de eventualidad o error en caso de presentarse. **tiempo** (**varchar**): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:Json que contiene en su interior el objeto “datos” que se describe a **continuación:** **modificar (varchar):** contiene true cuando el grupo de inventario se modifica satisfactoriamente y false cuando no se pudo modificar. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 50: El usuario no posee permisos para: âmodificar grupos de inventariosâ.
- 130: No se ingresÃ³ el parÃ¡metro "X".

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
