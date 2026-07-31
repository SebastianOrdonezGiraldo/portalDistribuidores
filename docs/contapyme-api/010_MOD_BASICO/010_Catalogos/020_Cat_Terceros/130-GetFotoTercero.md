# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/020_Cat_Terceros/130-GetFotoTercero.html

Â¿CÃ³mo establecer la foto del tercero?

GetFotoTercero (datajson, controlkey, iapp, random) : TMemoryStream

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la foto que tenga asignada el tercero en el sistema.

#### Resultado

Retorna un TMemoryStream que contiene la foto del tercero.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **Init**: Identificador del tercero del cual se desea obtener la foto. (requerido) | { "init": "856956254" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatTerceros/"GetFotoTercero"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"init": "856956254"
}
//Se arma los 4 parÃ¡metros de entrada de la funcion
URL = URL + JSON.stringify(dataJSON) + "/" + controlkey + "/" + iapp + "/0";
imprimirRespuesta(URL);

âº EJECUTAR CODIGO

Ver otros ejemplos en:
[PHP](../../../ejemplo/PHP.html) ,
[JAVA](../../../ejemplo/JAVA.html),
[C#](../../../ejemplo/Csharp.html),
[Visual Basic.net](../../../ejemplo/visualBasic.html),
[Visual Basic 6](../../../ejemplo/visualBasic6.html),
[Delphi.](../../../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/130-GetFotoTercero.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

  

El resultado de la peticiÃ³n es un stream cuya presentaciÃ³n es algo como lo que sigue (extracto de los primeros caracteres):  
  
"Ã¿ÃÃ¿Ã \u0000\u0010JFIF\u0000\u0001\u0002\u0001\u0000`\u0000`\  
u0000\u0000Ã¿Ã®\u0000\u000eAdobe\u0000d\u0000\u0000\u0000\u0000\u0001Ã¿Ã¡\u0014#Exif\u0000\u0000MM\u0000\*\  
u0000\u0000\u0000\b\u0000\u0007\u00012\u0000\u0002\u0000\u0000\u0000\u0014"  
  
Para poder visualizar la imagen en una pÃ¡gina web se debe poner la URL de la peticiÃ³n en la propiedad src de la etiqueta img, la url debe tener los parÃ¡metros que recibe la funciÃ³n, el ejemplo lo podemos ver a continuaciÃ³n.  
  
<img src=" http://190.248.152.10:8011/datasnap/rest/TCatTerceros/GetFotoTercero/{"init":"856956254"}  
/265262714643520/1022/12891266518477008">

  

Â©2016 InSoft Todos los derechos reservados.
