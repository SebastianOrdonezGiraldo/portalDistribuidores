# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/030-GetPermisosPorAcciones.html

Â¿CÃ³mo obtener los permisos por acciones?

GetPermisosPorAcciones (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Función que retorna si se tiene o no permiso sobre las acciones del sistema. La seguridad de acciones está definida a nivel de perfil de usuario, es decir, cada perfil tiene la definición de las acciones que puede o no ejecutar.   
Cada acción del sistema tiene un código definido por defecto, por lo cual si se va a implementar la seguridad de acciones se deben manejar dichos códigos.   
Para conocer más sobre la seguridad de acciones, consultar su manejo en el sistema ContaPyme o AgroWin.

#### Resultado

Retorna un Json con los permisos del usuario sobre las acciones del sistema.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **acciones:** Arreglo que contiene la lista con los códigos de las acciones, por cada acción se debe concatenar el "ITDACTION" y el "KEYACTION" separados por ":", respetando el orden mencionado. El ITDACTION corresponde al tipo de acción y el KEYACTION corresponde al código identificador de la acción en el sistema.  Para conocer la lista de acciones y sus códigos, revisar el documento "SeguridadAcciones" que se encuentra en la zona de “Documentación de apoyo”  (requerido) | { "acciones": [ "1: 5093", "1: 5094", "1: 5260", "1: 5095", "1: 5096", "1: 5099" ] } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TBasicoGeneral/"GetPermisosPorAcciones"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
   "acciones": [
       "1:5093",
       "1:5094",
       "1:5260",
       "1:5095",
       "1:5096",
       "1:5099"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/030-GetPermisosPorAcciones.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "6"
},
"respuesta": {
"datos": {
"1:5093": "T",
"1:5094": "F",
"1:5260": "T",
"1:5095": "T",
"1:5096": "T",
"1:5099": "T"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto âdatosâ el cual serÃ¡ descrito a continuaciÃ³n:    Json que contiene en su interior el objeto “datos” el cual será descrito a continuación:  **datos (json):** contiene la lista de acciones solicitadas en el llamado de la petición con su respectivo valor T o F. T: indica que el usuario si tiene permiso sobre la acción. F: Indica que el usuario no tiene permiso sobre la acción. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 180: No se ingresaron los campos de los cuales desea obtener la configuraciÃ³n.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
