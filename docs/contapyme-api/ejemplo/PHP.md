# Ejemplo PHP

> Fuente original: https://www.contapyme.com/api/ejemplo/PHP.html

Solicitud REST al Agente de servicios web desde PHP

[Objetivo](#primer_enlace)
[JSON](#segundo_enlace)
[Request](#tercer_enlace)
[Ejemplo](#cuarto_enlace)
[Descargar](PHP.zip)

## Objetivo

- Explicar de forma clara y precisa cÃ³mo es el manejo de JSON en el lenguaje PHP.
- Explicar las funciones que ofrece PHP para realizar una peticiÃ³n HTTP por medio del verbo POST.
- Presentar un ejemplo funcional donde se consuma recursos de la API que ofrece el agente de servicios web de ContaPyme desde el lenguaje de programaciÃ³n PHP.

JSON

El manejo de JSON en el lenguaje de PHP se puede hacer mediante la definiciÃ³n de un objeto el cual se convertirÃ¡ despuÃ©s en un JSON o el mÃ¡s fÃ¡cil usando arreglos los cuales tendrÃ¡n un comportamiento dependiendo de cÃ³mo se haga se definiciÃ³n del arreglo.

Arreglos Asociativos

En este tipo de arreglo cada elemento se compone de una clave y un valor, por lo tanto cada clave dentro del arreglo debe ser Ãºnica, mientras que el valor se puede repetir en diferentes llaves, este tipo de arreglo servirÃ¡ para definir el tipo de dato JSONObject; ejemplo:




| Arreglo asociativo | RepresentaciÃ³n en JSON |
| --- | --- |
| array( "EspaÃ±a"=>"Madrid",   "Francia"=>"ParÃ­s",   "Inglaterra"=>"Londres",   "Italia"=>"Roma",   "Portugal"=>"Lisboa",  "Alemania"=>"BerlÃ­n"  ) | { "EspaÃ±a": "Madrid", "Francia": "ParÃ­s", "Inglaterra": "Londres", "Italia": "Roma", "Portugal": "Lisboa", "Alemania": "BerlÃ­n" } |

Arreglo Indexado

TambiÃ©n conocido como vectores o listas es la forma mÃ¡s simple de definir un arreglo en PHP, en esta podemos guardar varios datos o variables agrupados formando una lista en el cual el Ã­ndice serÃ¡ un nÃºmero que no hay que definir, este tipo de arreglo servirÃ¡ para definir el tipo de dato JSONArray; ejemplo:

| Arreglo indexado | RepresentaciÃ³n en JSON |
| --- | --- |
| array(  "foo",  12345,  true,   "hello",   "world"  ) | { "name": " foo ", "num": 100, "balance": 1000.21, "Italia": true } |

Json\_decode

FunciÃ³n implementada desde la versiÃ³n de PHP 5.2.0 que decodifica un String convirtiÃ©ndolo en un Array o en un objeto (previamente definido), como parÃ¡metro obligatorio recibe un String con el JSON a decodificar. Esta funciÃ³n sÃ³lo trabaja con cadenas codificadas en UTF-8. Si el valor que se quiere convertir a un JSON no es vÃ¡lido esta funciÃ³n retornarÃ¡ null. Ejemplo:

| AplicaciÃ³n del json\_decode | Resultado de json\_decode |
| --- | --- |
| php <br/ $json= '{"a":1,"b":2,"c":3,"d":4,"e":5}';  var\_dump(json\_decode($json, true));  ?> | array(5) { ["a"] => int(1) ["b"] => int(2) ["c"] => int(3) ["d"] => int(4) ["e"] => int(5) } |

Json\_encode

FunciÃ³n implementada desde la versiÃ³n de PHP 5.2.0 la cual retorna una cadena con la representaciÃ³n de un JSON; para esto la funciÃ³n recibe un Array asociativo o un objeto con sus atributos ya definidos, si la conversiÃ³n es correcta retornarÃ¡ el string pero en caso de presentarse algÃºn error retornarÃ¡ FALSE. Ejemplo:

| AplicaciÃ³n del json\_encode | Resultado de json\_encode |
| --- | --- |
| php<br/ $arr = array('a' => 1,   'b' => 2,   'c' => 3,   'd' => 4,   'e' => 5);  echo json\_encode($arr);  ?> | {"a":1,"b":2,"c":3,"d":4,"e":5} |

Request

stream\_context\_create

FunciÃ³n disponible desde la versiÃ³n de PHP 4.3.0 permite crear un contexto de flujo con cualquier opciÃ³n que se enviÃ© en el parÃ¡metro **options**. Es decir, esta funciÃ³n permitirÃ¡ definir cÃ³mo serÃ¡ el contexto de una peticiÃ³n, permitiendo definir el protocolo por el cual se harÃ¡ la peticiÃ³n (HTTP, FTP, HTTPS, SSL), verbo con el cual se realizarÃ¡ la solicitud (POST, GET, PUT, DELETE), hasta la definiciÃ³n de cabeceras y contenido que enviarÃ¡ la misma. Ejemplo:

| CÃ³digo de PHP | Resultado |
| --- | --- |
| $opts = array(  'http'=>array('method'=>"GET",'header'=>array("Accept-language: es", "Cookie: foo=bar","Custom-Header: value")   )   );   $context = stream\_context\_create($opts); | Este cÃ³digo crea el contexto para una peticiÃ³n GET a travÃ©s de HTTP que espera su respuesta en espaÃ±ol y envÃ­a en los header la variable cookie que representa âfoo=barâ y un header personalizado con su respectivo valor. |
| $options = array (  'http' => array (  'method' => 'POST',  'header'=> "Content-type:   application/JSON"  "Content-Length: ".strlen($data)  âcontentâ => $data  )  );   $context = context\_create\_stream($options) | Este flujo de contexto es para una peticiÃ³n HTTP por mÃ©todo post que envÃ­a un JSON en su content y en la cabecera indica el tipo de dato que estÃ¡ enviando y el tamaÃ±o del mismo. |

file\_get\_contents

Disponible desde la versiÃ³n de PHP 4.3.0 permite obtener un recurso publicado en una URL, el recurso obtenido serÃ¡ entregado en formato String, esta opciÃ³n es la recomendada para transmitir un recurso a una cadena, puesto que usa tÃ©cnicas de mapeado de memoria para mejorar el rendimiento de las solicitudes. Esta funciÃ³n recibe como parÃ¡metro la ubicaciÃ³n del archivo y el contexto con el cual se realizarÃ¡ la peticiÃ³n. Ejemplo:

| CÃ³digo PHP | DescripciÃ³n del cÃ³digo |
| --- | --- |
| $option = array(  'http'=>array(  'method'=>"GET",  'header'=>"Accept-language: en\r\nâ.  "Cookie: foo=bar\r\n"  )  );  $ctx = stream\_context\_create($option);  $str\_recurso = file\_get\_contents('http://www.example.com/', false, $ctx);  ?> | Primero se crea el contexto de la solicitud, en el cual se indica que serÃ¡ una peticiÃ³n HTTP por el verbo GET, ya una vez definido el contexto se procede a realizar la solicitud a la URL del recurso con la funciÃ³n file\_get\_contents en la cual se envÃ­a la URL y el contexto previamente definido, de esta manera la variable $str\_recurso tomarÃ¡ el valor representado en string del recurso. |

Ejemplo funcional

El siguiente ejemplo realiza una peticiÃ³n que muestra el listado de terceros obtenidos de un agente de servicios web de ContaPyme (ASW) en una tabla HTML. Para esto primero se debe hacer GetAuth contra el agente para obtener el KeyAgent con el cual se realizarÃ¡ la solicitud de listado de terceros.

requestPOST

Esta funciÃ³n recibe como parÃ¡metro la URL a la cual se realizarÃ¡ la solicitud POST y el JSON que se enviarÃ¡ en la peticiÃ³n, la funciÃ³n obtendrÃ¡ el String de la peticiÃ³n y convertirÃ¡ este en un JSON el cual retornarÃ¡.

function requestPOST($url, $data) {  
   //Se crea el Objeto de configuraciÃ³n  
   $options = array(  
      'http' => array(  
         //Se adiciona especifica que la peticiÃ³n es JSON  
         'header' => "Content-type: application/json",  
         //Se especifica el verbo de la solicitud  
         'method' => 'POST',  
         //Se adiciona el JSON al content en forma de string  
         'content' => json\_encode($data)  
      )  
   );  
   //Se crea el contexto con las opciones definidas  
   $context = stream\_context\_create($options);  
   //Se solicita el recurso a la URL con el contexto definido  
   $result = file\_get\_contents($url, false, $context);  
   //Se verifica que la respuesta sea correcta  
   if ($result != FALSE) {  
      //Se convierte el String del resultado a un JSON  
      $JResult = json\_decode($result);  
      //Se verifica si el JSON es invalido  
      if ($JResult == null) {  
         //Se define un JSON vacio cuando no hay result  
         $JResult = json\_decode("{}");  
      }  
   } else {  
      //Se define un JSON vacio cuando no hay conexiÃ³n  
      $JResult = json\_decode("{}");  
   }  
   //Se retorna la respuesta  
   return $JResult;  
}

getKeyAgent

Esta funciÃ³n construirÃ¡ el JSON necesario para la autenticaciÃ³n contra el Agente de servicios web (ASW), paso seguido realizarÃ¡ la solicitud, y retornarÃ¡ el KeyAgente entregado por el servidor.

function getKeyAgent() {  
   $keyAgente = "";  
   $urlGetAuth = 'http://local.insoft.co:9000/datasnap/rest/TBasicoGeneral/"GetAuth"/';  
   $JSONParameter = array(  
      "email" => "pruebas.api@contapyme.com",  
      "password" => "e10adc3949ba59abbe56e057f20f883e",  
      "idmaquina" => "1",  
   );  
   $JSONSend = array('\_parameters' => array(  
      json\_encode($JSONParameter),  
      "0",  
      "1001",  
      "0"  
   ));  
   $JResult = requestPOST($urlGetAuth, $JSONSend);  
   $arrTemp = $JResult->{"result"};  
   if (($arrTemp != null) and ( count($arrTemp) > 0)) {  
      $objTemp = $arrTemp[0];  
      if (($objTemp != null)) {  
         $objTemp = $objTemp->{"respuesta"};  
         if (($objTemp != null)) {  
            $objTemp = $objTemp->{"datos"};  
            if (($objTemp != null)) {  
               $keyAgente = $objTemp->{"keyagente"};  
            }  
         }  
      }  
   }  
   return $keyAgente;  
}

getListaTerceros

Esta funciÃ³n recibe el key Agente obtenido anteriormente y construye el JSON para realizar la peticiÃ³n de obtener el listado de terceros, como resultado esta funciÃ³n retornara un arreglo indexado con el listado de terceros.

function getListaTerceros($keyAgent) {  
   $urlGetAuth = 'http://local.insoft.co:9000/datasnap/rest/TCatTerceros/"GetListaTerceros"/';  
   $JSONParameter = array(  
      "datospagina" => array(  
         "cantidadregistros" => "99",  
         "pagina" => "1"  
      ),  
      "camposderetorno" => array("init", "ntercero", "napellido")  
   );  
   $JSONSend = array('\_parameters' => array(  
      json\_encode($JSONParameter),  
      $keyAgent,  
      "1001",  
      "0"  
   ));  
   $JResult = requestPOST($urlGetAuth, $JSONSend);  
   $arrTemp = $JResult->{"result"};  
   if (($arrTemp != null) and ( count($arrTemp) > 0)) {  
      $objTemp = $arrTemp[0];  
      if (($objTemp != null)) {  
         $objTemp = $objTemp->{"respuesta"};  
         if (($objTemp != null)) {  
            $arrTemp = $objTemp->{"datos"};  
         }  
      }  
   }  
   return $arrTemp;  
}

ImplementaciÃ³n de las funciones

En la implementaciÃ³n de las funciones previas, lo primero que se hace es construir el encabezado de la tabla, paso seguido se obtiene el keyAgent que se necesitara para obtener informaciÃ³n del ASW, una vez se tenga el KeyAgent se solicita el listado de terceros con el mismo y se recorre el arreglo para generar el HTML donde este serÃ¡ visible.

$table = "<tr>  
   <td> <b> CÃ³digo <b> <td>  
   <td> <b> Nombre <b> <td>  
   <td> <b> Apellido <b> <td>  
<tr>";  
$sKey = getKeyAgent();  
if ($sKey != "") {  
   $Arrterceros = getListaTerceros($sKey);  
    for ($i = 0; $i < count($Arrterceros); ++$i) {  
      $s = " <tr>  
         <td>" . $Arrterceros[$i]->{"init"} . "<td>  
         <td>" . $Arrterceros[$i]->{"ntercero"} . "<td>  
         <td>" . $Arrterceros[$i]->{"napellido"} . "<td>  
    <tr>";  
      $table = $table . $s;  
   }  
}  
echo "<table> border='1' style=' table-layout: fixed;width: 100%;border: 3px solid purple;'>" . $table . "<table>";  
?>

Resultado de la implementaciÃ³n

![](imagenes/PhpRes.png)

Â©2016 InSoft Todos los derechos reservados.
