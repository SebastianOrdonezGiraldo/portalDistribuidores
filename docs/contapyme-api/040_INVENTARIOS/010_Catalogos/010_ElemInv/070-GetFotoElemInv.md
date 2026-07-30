# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/010_Catalogos/010_ElemInv/070-GetFotoElemInv.html

Â¿CÃ³mo establecer la foto de un tercero en el inventario?

GetFotoTercero (datajson, controlkey, iapp, random) : TMemoryStream

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la imagen que tenga asignada un elemento de inventario en el sistema. Un elemento de inventario puede tener asignadas varias imágenes, por lo cual es necesario enviar el código de la imagen que se desea obtener.

#### Resultado

Retorna un TMemoryStream que contiene la imagen del elemento de inventario.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **Irecurso**: CÃ³digo del elemento de inventario.  **Codimg**: CÃ³digo de la imagen que se desea obtener. | { "irecurso":"06120", "codimg":"1" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatElemInv/"GetFotoElemInv"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"irecurso":"DID003",
"codimg":"1"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/070-GetFotoElemInv.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

  

El resultado de la peticiÃ³n es un stream cuya presentaciÃ³n es algo como lo que sigue (extracto de los primeros caracteres):  
  
"Ã¿ÃÃ¿Ã \u0000\u0010JFIF\u0000\u0001\u0002\u0001\u0000`\u0000`\  
u0000\u0000Ã¿Ã®\u0000\u000eAdobe\u0000d\u0000\u0000\u0000\u0000\u0001Ã¿Ã¡\u0014#Exif\u0000\u0000MM\u0000\*\  
u0000\u0000\u0000\b\u0000\u0007\u00012\u0000\u0002\u0000\u0000\u0000\u0014"  
  
Para poder visualizar la imagen en una pÃ¡gina web se debe poner la URL de la peticiÃ³n en la propiedad src de la etiqueta img, la url debe tener los parÃ¡metros que recibe la funciÃ³n, el ejemplo lo podemos ver a continuaciÃ³n.  
  
<img src= http://190.248.152.10:8011/datasnap/rest/TCatElemInv/GetFotoElemInv/{"irecurso":"06120","codimg":"1"}  
/306407550588300/1028/12891266518477008>

  

Â©2016 InSoft Todos los derechos reservados.
