# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/005_INTRODUCCION/010-Test.html

Â¿CÃ³mo obtener el test?

Test () : string

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar mediante un código el estado en el que se encuentra el Agente de servicios web.

#### Resultado

Retorna un string con el cÃ³digo y el estado del Agente de Servicios web.

Seguridad

Aplica todas las configuraciones de seguridad de datos y de seguridad de acciones de ContaPyme / AgroWin.

Compatibilidad de la API

FunciÃ³n disponible desde ContaPyme/AgroWin VersiÃ³n 4 - Release 7.

## PeticiÃ³n

Requisitos

Debe haber realizado el logueo en el agente a travÃ©s de la funciÃ³n [GETAUTH().](020-GetAuth.html)

ParÃ¡metros

| Nombre parÃ¡metro | Tipo | DescripciÃ³n | Ejemplo |
| --- | --- | --- | --- |
| dataJSON | JSON | Es funciÃ³n no recibe ningÃºn parÃ¡metro. |  |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

âº EJECUTAR CODIGO

Ver otros ejemplos en:
[PHP](../ejemplo/PHP.html) ,
[JAVA](../ejemplo/JAVA.html),
[C#](../ejemplo/Csharp.html),
[Visual Basic.net](../ejemplo/visualBasic.html),
[Visual Basic 6](../ejemplo/visualBasic6.html),
[Delphi.](../ejemplo/Delphi.zip)

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/010-Test.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
"4|AGENTESERVICIOSWEB1 conectado al Ã¡rea de trabajo."
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| respuesta | JSON | Contiene el código y estado del Agente separados por el caracter "|".  Los estados en los que se puede encontrar un Agente son: 1|Servicio detenido. 2|Servicio en ejecución. 3|Servicio conectándose al área de trabajo. 4|Servicio conectado al área de trabajo.  5|Agente no conectado al área de trabajo. |

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
