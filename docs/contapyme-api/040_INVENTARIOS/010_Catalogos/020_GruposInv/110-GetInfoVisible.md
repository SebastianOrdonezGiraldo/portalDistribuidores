# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/040_INVENTARIOS/010_Catalogos/020_GruposInv/110-GetInfoVisible.html

Â¿CÃ³mo obtener la informaciÃ³n visible?

GetInfoVisible (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

En la edición de un grupo de inventario la información está agrupada por pasos y dichos pasos son visibles de acuerdo a la configuración que tenga asignada el grupo de inventario, esta función se encarga de analizar y retornar los pasos visibles en la edición del grupo de inventario.

#### Resultado

Retorna un Json con el listado de pasos del catÃ¡logo de grupos de inventario que deben estar visibles.

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
| dataJSON | JSON | Json que contiene en su interior la información de configuración del grupo de inventario, los parámetros son los siguientes:  **bcontrolinv:** Indica que los productos asociados al grupo de inventario controlan cantidades en el inventario (T o F).  **bconsumo:** Indica que los productos asociados al grupo de inventario podrá ser destinados para consumo interno (T o F).  **bventa:** Indica que los productos asociados al grupo de inventario está disponibles para la venta (T o F)  **bproducto:** Indica que los productos asociados al grupo de inventario son fruto de producción interna (T o F)  **bservicio:** Indica que los elementos asociados al grupo de inventario son servicios (T o F)  Si alguna de las banderas descritas anteriormente no llega en el JSon, se asumirá como False. | { "bcontrolinv": "T", "bconsumo": "F", "bventa": "T", "bproducto": "T", "bservicio": "F" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript[Ir arriba](#arriba)

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatGrupoInv/"GetInfoVisible"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"bcontrolinv": "T",
"bconsumo": "F",
"bventa": "T",
"bproducto": "T",
"bservicio": "F"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/110-GetInfoVisible.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "5"
},
"respuesta": {
"datos": [
{
"icuentavta": "T"
},
{
"icuentacostos": "T"
},
{
"icuentaegr": "F"
},
{
"icuentainv": "F"
},
{
"icuentadevcompra": "F"
},
{
"icuentadevventa": "F"
},
{
"iconceptoventa1": "F"
},
{
"iconceptocompra1": "T"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje** (**varchar**): Código del mensaje de eventualidad o error en caso de presentarse. **mensaje** (**varchar**): Mensaje de eventualidad o error en caso de presentarse. **tiempo** (**varchar**): Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” que se describe a continuación:  **icuentavta:** Pestaña de edición “Cuenta de ingresos **(venta)”.** **icuentacostos:** Pestaña de edición “Cuenta de costo de **ventas”** **icuentaegr:** Pestaña de edición “Cuenta de **egresos”** **icuentainv:** Pestaña de edición “Cuenta de **inventarios”** **icuentadevcompra:** Pestaña de edición “Cuenta de devolución en **compras”** **icuentadevventa:** Pestaña de edición “Cuenta de devolución en **ventas”** **iconceptoventa1:** Pestaña de edición “Impuestos en **ventas”** **iconceptocompra1:** Pestaña de edición “Impuestos en compras” |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
- 40: Usuario no logueado.
- 1008: El cÃ³digo de la aplicaciÃ³n es incorrecto, informar de este error.

Un ejemplo del JSON que retorna la funciÃ³n cuando se genera una eventualidad es el siguiente:

{
"result":[{
"encabezado":{"resultado":"false","imensaje":"40","mensaje":"Usuario no logueado."},
"respuesta":{"datos":""}
}]
}

Â©2016 InSoft Todos los derechos reservados.
