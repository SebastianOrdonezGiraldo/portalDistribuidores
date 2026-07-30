# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/010_MOD_BASICO/010_Catalogos/100_Cat_Plan_cuentas/060-GetConfigCampos.html

Â¿CÃ³mo verificar la configuraciÃ³n de los campos?

GetConfigCampos (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar la configuración para cada campo del catálogo de Plan de cuentas, dicha configuración es asignada en el sistema ContaPyme / AgroWin en la opción “Configuración” del catálogo de Plan de cuentas.  
Por cada campo retorna: Si es visible, requerido o de solo lectura, valor por defecto, etiqueta, tipo de lista y configuración para las listas.

#### Resultado

Retorna un Json con la configuraciÃ³n de cada uno de los campos del catÃ¡logo de Plan de cuentas.

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
| dataJSON | JSON | En esta función este parámetro no se utiliza pero se define debido que puede ser requerido para usos futuros en el Agente CP. | {} |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript[Ir arriba](#arriba)

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TCatPlanCuentas/"GetConfigCampos"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {};
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/060-GetConfigCampos.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "96"
},
"respuesta": {
"datos": {
"infobasica": {
"clase1": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"etiqueta": "Clase 1",
"valorpordefecto": ""
},
"clase2": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"etiqueta": "Clase 2",
"valorpordefecto": ""
},
"valor1": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"etiqueta": "Valor 1",
"valorpordefecto": ""
},
"valor2": {
"bvisible": "F",
"blectura": "F",
"brequerido": "F",
"etiqueta": "Valor 2",
"valorpordefecto": ""
},
"idescuento": {
"bvisible": "T",
"blectura": "F",
"etiqueta": "IVA"
},
"icargo": {
"bvisible": "T",
"blectura": "F",
"etiqueta": "RetenciÃ³n 2"
},
"icargo2": {
"bvisible": "T",
"blectura": "F",
"etiqueta": "Rete-ICA 2"
},
"icargo3": {
"bvisible": "T",
"blectura": "F",
"etiqueta": "CREE"
},
"icargo4": {
"bvisible": "T",
"blectura": "F",
"etiqueta": "Otro"
},
"icargo5": {
"bvisible": "T",
"blectura": "F",
"etiqueta": "Otro 2"
}
},
"cfg": {
"blocalniif": "F",
"bmanejoflujoefectivo": "T",
"bexigeflujoefectivo": "T",
"bmonedaextranjera": "T",
"bconciliacionbancaria": "T",
"bctrlestrictoconciliacionbanca": "T"
}
}
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar):** Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar):** Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar):** Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar):** Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el objeto “datos” que se describe a continuación:  **infobasica (objeto):** Contiene para uno de los campos configurables del catálogo la siguiente información:  **bvisible**: Contiene T cuando el campo es visible en el formulario y F cuando no lo es.  **blectura**: Contiene T cuando el campo es de solo lectura (es decir, no se puede modificar) y F cuando no lo es.  **brequerido**: Contiene T cuando el campo es obligatorio en el formulario y F cuando es opcional.  **etiqueta**: contiene el nombre del campo definido por el usuario. Hay algunos campos que permiten configurar su nombre según las necesidades del usuario, en ese caso, este parámetro contendrá el nombre que definió el usuario para el campo.  **valorpordefecto**: valor por defecto configurado por el usuario para el campo.  **cfg (objeto)**: Objeto que contiene las configuraciones adicionales que se deben tener en cuenta para el manejo del catálogo de Plan de cuentas, estas configuraciones pueden ser definidas por el usuario. Las configuraciones del catálogo de Plan de cuentas son:  **bmanejoflujoefectivo**: Contiene T cuando está habilitado el manejo de flujo de efectivo al realizar movimientos en las cuentas de caja y bancos. Y contiene F cuando no está habilitado este manejo.  **bexigeflujoefectivo**: Contiene T cuando es obligatorio indicar el flujo de efectivo en operaciones del sistema en donde se ingrese o egrese dinero afectando las cajas o bancos.  **bmonedaextranjera**: Contiene T cuando está habilitado el manejo de moneda extranjera en el sistema, y F cuando no lo está.  **bconciliacionbancaria:** Contiene T cuando está habilitado el control de movimientos para la conciliación bancaria, y F cuando no está habilitado.  **bctrlestrictoconciliacionbanca**: Contiene T cuando está habilitado el control estricto de los tipos de movimientos bancarios que se utilicen en las cuentas de conciliación. Y contiene F cuando no está habilitado este manejo. |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
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
