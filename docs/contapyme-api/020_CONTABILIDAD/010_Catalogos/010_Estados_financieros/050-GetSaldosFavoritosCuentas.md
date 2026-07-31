# DocumentaciÃ³n API.

> Fuente original: https://www.contapyme.com/api/020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/050-GetSaldosFavoritosCuentas.html

Â¿CÃ³mo obtener los saldos de las cuentas favoritas?

GetSaldosFavoritosCuentas (datajson, controlkey, iapp, random) : json

[DescripciÃ³n](#primer_enlace)
[PeticiÃ³n](#segundo_enlace)
[Respuesta](#tercer_enlace)

## DescripciÃ³n

Esta función es la encargada de retornar los saldos de las cuentas favoritas asociadas a una categoría.

#### Resultado

Retorna un Json con los saldos de las cuentas favoritas.

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
| dataJSON | JSON | Json que contiene en su interior la siguiente estructura.  **categoria:** Nombre de la categoría de la cual se desean obtener las cuentas favoritas con sus respectivos saldos.  (requerido)  **fecha:** Fecha para la cual se calculan los saldos, si no se indica, se toma la fecha actual, esta fecha debe de ir en formato mes/día/año. | { "categoria": "Ingresos", "fecha": "12/31/2013" } |
| controlkey | Varchar | Corresponde al keyagente obtenido en el logueo (requerido). | "564654" | "222912" |
| iapp | Varchar | CÃ³digo que identifica a la aplicaciÃ³n que interactÃºa con el Agente (requerido) | "1068" |
| random | Varchar | Cadena aleatoria que se crea en el lado del cliente, esto con el fin de que las peticiones no sean cacheadas por el navegador Internet Explorer (para aplicaciones web). (Opcional). | "54654" |

Ejemplo de la ejecuciÃ³n en JavaScript

//Escriba a continuaciÃ³n la URL donde se encuentra su Agente de servicios web de ContaPyme.
var URLUbicacion = 'http://local.insoft.co:9000'
var URLFuncion = '/datasnap/rest/TEstadosFinancieros/"GetSaldosFavoritosCuentas"/';
//Se construye la URL completa la cual es la concatenaciÃ³n de la ubicaciÃ³n y la funciÃ³n
var URL = URLUbicacion + URLFuncion;
//Invocamos la funciÃ³n que retorna controlKey para modo aprendizaje
var controlkey = getControlKey(URLUbicacion);
//1001 es el iapp configurado para agente de servicios web de ContaPyme.
var iapp = "1001";
//dataJSON: parÃ¡metros de entrada para la funciÃ³n
var dataJSON = {
"categoria": "Ingresos",
"fecha": "12/31/2013"
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

Ver documentaciÃ³n de la peticiÃ³n por [GET.](PDFS/050-GetSaldosFavoritosCuentas.pdf)

## Respuesta

JSON[Ir arriba](#arriba)

{
"result": [
{
"encabezado": {
"resultado": "true",
"imensaje": "",
"mensaje": "",
"tiempo": "4"
},
"respuesta": {
"datos": [
{
"idato": "2",
"categoria": "Ingresos",
"nombre": "Ingresosventacomputadores",
"orden": "1",
"iclasifop": "0",
"cuentas": [
"415530"
],
"ccs": [
"10102"
],
"naturaleza": "C",
"itdcuenta": "4",
"msaldoant": "596000",
"mdebito": "1562000",
"mcredito": "165200",
"msaldo": "1396800"
}
]
}
}
]
}

DescripciÃ³n del JSON[Ir arriba](#arriba)

| Nombre parÃ¡metro | Tipo | DescripciÃ³n |
| --- | --- | --- |
| encabezado | JSON | Json que contiene en su interior los siguientes datos:  **resultado (varchar)**: Retorna true siempre que la petición se ejecute satisfactoriamente. **imensaje (varchar)**: Código del mensaje de eventualidad o error en caso de presentarse. **mensaje (varchar)**: Mensaje de eventualidad o error en caso de presentarse. **tiempo (varchar)**: Tiempo que se tardó el Agente en resolver la petición, este tiempo está dado en milisegundos. |
| respuesta | JSON | Json que contiene en su interior el arreglo de objetos “datos” que se describe a continuación:  **idato (varchar)**: identificador del registro **categoria (varchar)**: Nombre de la categoría a la que pertenece el favorito. **nombre (varchar)**: Nombre de la cuenta asociada a favoritos. **orden (varchar)**: Número que especifica el orden en que se visualizará la cuenta favorita. **iclasifop (varchar)**: Clase de operación por la cual se filtra el saldo del favorito. **cuentas (arreglo)**: Arreglo de cuentas que pertenecen al favorito. **ccs (arreglo)**: Arreglo de centros de costos por los cuales se filtrará el saldo del favorito. **naturaleza (varchar)**: Indica la naturaleza de la cuenta. D: Débito, C: Crédito **itdcuenta (varchar)**: Identificador del tipo de cuenta.1: Activo, 2: Pasivo, 3: Patrimonio, 4: Ingresos, 5: Egresos, 6: De orden deudora, 7: De orden acreedora. msaldoant (double): Saldo de la cuenta hasta la fecha inicial del período de cálculo (saldo anterior). **mdebito (double)**: Débitos que recibió la cuenta durante el período de cálculo. **mcredito (double)**: Créditos que recibió la cuenta durante el período de cálculo. **msaldo (double)**: Nuevo saldo de la cuenta (débitos - créditos) |

Eventualidades[Ir arriba](#arriba)

Para esta funciÃ³n se pueden presentar las siguientes eventualidades o errores:

- 0: Error en la aplicación, errores no controlados.
- 1: Mensaje que le indica al usuario que debe corregir errores (errores controlados).
- 10: No se ingresÃ³ un Json como parÃ¡metro.
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
