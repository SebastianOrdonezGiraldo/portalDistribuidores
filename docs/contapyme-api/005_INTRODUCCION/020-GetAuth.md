# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/005_INTRODUCCION/020-GetAuth.html

Â¿CÃ³mo establecer comunicaciÃ³n con el Agente?

GetAuth (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de loguear a un usuario en el Agente y retornar un código único para la sesión del usuario (keyAgente). Ésta es la primera función que se debe ejecutar para establecer comunicación con el Agente.

#### Resultado

Retorna los datos que identifican al usuario logueado en el Agente.

Seguridad

Aplica todas las configuraciones de seguridad de datos y de seguridad de acciones de ContaPyme / AgroWin.

Compatibilidad de la API

FunciÃ³n disponible desde ContaPyme/AgroWin VersiÃ³n 4 - Release 7.

## PeticiÃ³n

Requisitos

Ninguno

ParÃ¡metros

| Nombre parÃ¡metro | Tipo | DescripciÃ³n | Ejemplo |
| --- | --- | --- | --- |
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **email:** Correo electrónico con el cual se encuentra registrado el usuario en la aplicación ContaPyme/AgroWin. (requerido)  **password:** Contraseña del usuario asignada en el sistema. Para enviar este parámetro en el llamado de la función se debe convertir a mayúscula y encriptarlo en MD5. (requerido)  **idmaquina:** Código que se genera por cada equipo, se debe generar con las características del equipo desde el cual se está accediendo. El idmaquina permite restringir el acceso de varios usuarios con los mismos datos de logueo. (email y password). (opcional) | { "email": "pperez@gmail.com ", "password": "c4ca4238a0b923820dcc509a6f75849b", "idmaquina": "537.22\_136301143299" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TBasicoGeneral/"GetAuth"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
   "email": "pperez@gmail.com ",
   "password": "c4ca4238a0b923820dcc509a6f75849b",
   "idmaquina": "537.22\_136301143299"
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
[PHP](../ejemplo/PHP.html) ,
[JAVA](../ejemplo/JAVA.html),
[C#](../ejemplo/Csharp.html),
[Visual Basic.net](../ejemplo/visualBasic.html),
[Visual Basic 6](../ejemplo/visualBasic6.html),
[Delphi.](../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/020-GetAuth.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "219"
},
"respuesta": {
"datos": {
"keyagente": "28533BCD97",
"version": "4",
"release": "7",
"actualizacion": "1"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje** (**varchar**): Código del mensaje de eventualidad o error en caso de presentarse. **mensaje** (**varchar**): Mensaje de eventualidad o error en caso de presentarse. **tiempo** (**varchar**): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **keyagente (varchar):** Identificador único que se asigna a cada usuario cuando se loguea en el Agente.  Este identificador se debe utilizar en cada petición que se haga al agente después del logueo, es lo que corresponde al “controlkey”  **versión (varchar):** Número de versión en la que se encuentra el sistema ContaPyme / AgroWin.  **release (varchar):** Número de release en el que se encuentra el sistema ContaPyme / AgroWin.  **actualizacion (varchar):** Número de la actualización en la que se encuentra el sistema **ContaPyme** / **AgroWin.** |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.

Especificas[Ir arriba](#arriba)

- 1000: El nombre de usuario y/o contraseÃ±a son incorrectos.
- 1001: No se ingresÃ³ el nombre de usuario y/o contraseÃ±a.
- 1007: Ingrese el id de la aplicaciÃ³n "IAPP".
- 1008: El cÃ³digo de la aplicaciÃ³n es incorrecto, informar de este error.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
