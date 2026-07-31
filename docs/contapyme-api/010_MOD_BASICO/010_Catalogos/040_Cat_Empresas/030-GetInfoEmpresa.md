# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/040_Cat_Empresas/030-GetInfoEmpresa.html

Â¿CÃ³mo verificar la informaciÃ³n de una empresa?

GetInfoEmpresa (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retorna la información básica de la empresa de trabajo a la que el usuario se encuentra conectado.

#### Resultado

Retorna un Json con la informaciÃ³n de la empresa de trabajo activa.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura:  **camposderetorno**: Arreglo que contiene los campos de los cuales se desea obtener la información. (requerido) Para conocer los nombres de los campos que se pueden solicitar, visualizar el anexo 1 de este documento. | { "camposderetorno": [ "nemp", "ncorto", "ttelefono", "tfax", "tcelular" ] } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatEmpresas/"GetInfoEmpresa"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"camposderetorno": [
"nemp",
"ncorto",
"ttelefono",
"tfax",
"tcelular"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/030-GetInfoEmpresa.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "9"
},
"respuesta": {
"datos": {
"nemp": "MP COMPUTADORES LTDA",
"ncorto": "MP COMPU",
"nresp": "Juan Pablo Garcia Rios",
"ttelefono": "8828944",
"tfax": "8828944",
"sdireccion": "Cra 19 # 25-18",
"imun": "001",
"idep": "17",
"ipais": "169",
"swww": "www.mpcomputadores.com",
"semail": "gestion@contapyme.com"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar)**: Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que posee en su interior el objeto “datos” el cual contiene la información de los **camposderetorno** enviados a la función. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 40: Usuario no logueado.
- 80: No se ingresaron los campos de retorno.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
