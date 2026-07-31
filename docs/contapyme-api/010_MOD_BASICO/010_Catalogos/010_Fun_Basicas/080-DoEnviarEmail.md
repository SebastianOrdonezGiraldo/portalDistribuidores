# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/010_Fun_Basicas/080-DoEnviarEmail.html

Â¿CÃ³mo hacer el envÃ­o de un email?

DoEnviarEmail (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de enviar reportes o documentos en PDF por email. Para poder utilizar esta función es necesario tener en el licenciamiento el módulo de Campañas y alertas tempranas por email.  
Recibe la información que se enviará por email para que así el Agente se encargue de construir el email y despacharlo al destinatario indicado.

#### Resultado

Retorna un Json con la confirmaciÃ³n del envÃ­o de reportes o documentos por email.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura:  **url:** Es el MD5 identificador del reporte que se obtuvo en el llamado de la función GetPDF. (requerido)  **asunto:** Texto con el asunto del email. (opcional)  **contenido:** Texto del cuerpo del email. (opcional)  **narchivo:** Nombre del archivo que se envía por email. (requerido).  **firma:** Firma del correo, se puede enviar una de las siguientes opciones: contacto: El agente construye la firma de acuerdo a la información del usuario que envía el email. empresa: El agente construye la firma de acuerdo a la información de la empresa de trabajo activa.  **email:** arreglo que contiene la lista de correos a los cuales se les va a enviar el email con el reporte o documento en PDF. | { "url": "Q7HqS3elBp4vC2uoD3ghjghg584â¦", "asunto": "Factura de venta # FV-00745", "contenido": "Buen dÃ­a, a continuaciÃ³n adjunto la factura de venta # FV-00745", "narchivo": "Factura\_venta\_fv00745.pdf", "firma": "contacto", "email": [ "gerencia@mpcom putadores.com", "contabilidad@mp computadores.com" ] } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TReportes/"DoEnviarEmail"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"url": "Q7HqS3elBp4vC2uoD3ghjghg584â¦",
"asunto": "Factura de venta # FV-00745",
"contenido": "Buen dÃ­a, a continuaciÃ³n adjunto la factura de venta # FV-00745",
"narchivo": "Factura\_venta\_fv00745.pdf",
"firma": "contacto",
"email": [
"gerencia@mpcom putadores.com",
"contabilidad@mp computadores.com"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/080-DoEnviarEmail.pdf)

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
"envio": "true"
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **envio (varchar):** Contiene true cuando el email se envía satisfactoriamente o false cuando no se puede enviar. . |

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
