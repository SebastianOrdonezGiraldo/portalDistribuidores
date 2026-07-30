# Ejemplo JAVA.

> Fuente original: https://www.contapyme.com/api/ejemplo/JAVA.html

Solicitud REST al Agente de servicios web desde JAVA

[Objetivo](#primer_enlace)
[JSON](#segundo_enlace)
[Request](#tercer_enlace)
[Ejemplo](#cuarto_enlace)
[Descargar](JAVA.zip)

## Objetivo

- Explicar de forma clara y precisa cÃ³mo es el manejo de JSON en el lenguaje Java.
- Explicar las funciones y objetos que ofrece Java para realizar una peticiÃ³n HTTP por medio del verbo POST.
- Presentar un ejemplo funcional donde se consuma recursos de la API que ofrece el agente de servicios web de ContaPyme desde el lenguaje de programaciÃ³n java.

JSON

El manejo de JSON en el lenguaje de JAVA se puede hacer mediante la librerÃ­a llamada JSON simple, esta librerÃ­a tiene la implementaciÃ³n para los objetos de tipo JSONObject y JSONArray; adicionalmente la librerÃ­a ofrece otras funciones para conversiones de JSON a String y viceversa.

InstalaciÃ³n de la librerÃ­a (NetBeans)

Primero necesitamos descargar la librerÃ­a llamada json-simple-1.1.1.jar de su web oficial aquÃ­ https://code.google.com/archive/p/json-simple/, paso seguido debemos incorporarla a nuestro proyecto, para esto realizamos los siguientes pasos:

![](imagenes/instalaciÃ³n_libreria.png)

- Nos posicionamos en la capeta libraries de nuestro proyecto en netbeans
- Seleccionamos la opciÃ³n de adicionar un JAR/Folder.
- En el selector de archivos que se nos presenta seleccionamos la librerÃ­a json-simple-1.1.1.jar de la carpeta donde este almacenado.

JSONObject

La clase JSONObject representa el valor de un objeto JSON inmutable (una colecciÃ³n desordenada de cero o mÃ¡s pares de nombre/valor). Las llaves del JSONObject solo pueden definirse como String mientras los valores de este JSONObject pueden ser de cualquier combinaciÃ³n (JSONObject, JSONArray, Integer, Double, Boolean, String)

| CÃ³digo JAVA | RepresentaciÃ³n en JSONObject |
| --- | --- |
| JSONObject obj = new JSONObject();  obj.put("name", "foo");  obj.put("num", new Integer(100));  obj.put("balance", new Double(1000.21));  obj.put("is\_vip", new Boolean(true));  System.out.print(obj); | { "name": " foo ", "num": 100, "balance": 1000.21, "Italia": true } |

JSONArray

La clase JSON Array representa una secuencia ordenada de valores indexados donde los valores pueden ser de cualquier combinaciÃ³n (JSONObject, JSONArray, Integer, Double, Boolean, String).

| CÃ³digo JAVA | RepresentaciÃ³n en JSONArray |
| --- | --- |
| JSONArray arr = new JSONArray();  arr.add("foo");  arr.add(new Integer(100));  arr.add(new Double(1000.21));   arr.add(new Boolean(true));  arr.add(new JSONObject());  System.out.print(arr); | { "name": " foo ", "num": 100, "balance": 1000.21, "Italia": true } |

CodificaciÃ³n y decodificaciÃ³n de JSON

La librerÃ­a tambiÃ©n ofrece funcionalidades que permiten convertir una cadena de texto en un objeto JSON la cual serÃ¡ muy Ãºtil para convertir la respuesta de la peticiÃ³n, para esto podremos usar el objeto JSONParser:

| CÃ³digo JAVA para decodificar un JSON |
| --- |
| String str = '{  "name":"foo",  "num":100,   "balance": 1000.21,  "Italia":true,  "obj":{â¦â¦}  }' |

La codificaciÃ³n que podemos definir como el proceso para convertir un JSON a una cadena de texto, es mÃ¡s sencillo pues es una propiedad que tienen todos los objetos de la librerÃ­a JSONSimple, la codificaciÃ³n es muy Ãºtil para el momento de convertir un JSON para ser enviado en una peticiÃ³n:

| CÃ³digo JAVA | RepresentaciÃ³n del JSON |
| --- | --- |
| JSONObject obj = new JSONObject();  obj.put("name", "foo");  obj.put("num", new Integer(100));  obj.put("balance", new Double(1000.21));   obj.put("is\_vip", new Boolean(true));  obj.put("detail", new JSONObject());  obj.toJSONString() | { "name": " foo ", "num": 100, "balance": 1000.21, "is\_vip": true, "Detail":{} } |

Request

Objeto URL

Esta clase es utilizada para establecer conexiÃ³n con un recurso, archivo o directorio que se encuentra publicado a travÃ©s de una URL, la definiciÃ³n de un objeto URL en cÃ³digo no representa una apertura de conexiÃ³n con el recurso, solo representa la definiciÃ³n de un recurso que se va a consumir. La ruta que se defina en el objeto URL debe de ser estricto es decir acompaÃ±ado de un protocolo, un host o ip, un puerto en caso de ser necesario y el path que indica la ubicaciÃ³n del recurso.

| CÃ³digo JAVA para definir una URL |
| --- |
| try {  URL myURL = new URL ('http://insoft:80//DSAdmin/GetPlatformName/');  }   catch (MalformedURLException e) {  // exception handler code here  // ...  } |

HttpURLConnection

Esta clase nos permitirÃ¡ abrir la conexiÃ³n con un objeto URL previamente definido, de esta manera podremos definir las cabeceras que queremos enviar, el mÃ©todo o verbo a utilizar en la solicitud HTTP y definir los datos que se enviarÃ¡n en la peticiÃ³n. Cada instancia de este objeto se utiliza para realizar una Ãºnica solicitud.

| CÃ³digo JAVA para abrir una solicitud HTTP |
| --- |
| URL myURL = new URL ('http://insoft:80//DSAdmin/GetPlatformName/');   HttpURLConnection conn = (HttpURLConnection) url.openConnection();  conn.setRequestMethod("POST");  conn.setDoOutput(true);  conn.getOutputStream().write('Texto enviado al server'); |

BufferedReader

Esta clase nos permite leer texto de una secuencia de entrada de caracteres, almacenando en el buffer caracteres para proporcionar la lectura eficiente. Esta clase nos serÃ¡ Ãºtil para leer la respuesta entregada por el HttpURLConnection pues tiene la funcionalidad de leer en un formato de codificaciÃ³n definido.

| CÃ³digo JAVA leer la respuesta en un ReaderL |
| --- |
| URL myURL = new URL ('http://insoft:80//DSAdmin/GetPlatformName/');  HttpURLConnection conn = (HttpURLConnection)url.openConnection();  conn.setRequestMethod("POST");  conn.setDoOutput(true);  conn.getOutputStream().write('Texto enviado al server');  Reader in = new BufferedReader(newInputStreamReader(conn.getInputStream(), "UTF-8")); |

Ejemplo funcional

El siguiente ejemplo realiza una peticiÃ³n que muestra el listado de terceros obtenidos de un agente de servicios web de ContaPyme (ASW) en una tabla de tipo JTable. Para esto primero se debe hacer GetAuth contra el agente para obtener el KeyAgent con el cual se realizara la solicitud de listado de terceros.

requestPOST

Esta funciÃ³n recibe como parÃ¡metro la URL a la cual se realizara la solicitud POST y un JSONObject que se enviarÃ¡ en la peticiÃ³n, la funciÃ³n obtendrÃ¡ el String de la peticiÃ³n y convertirÃ¡ este en un JSONObject el cual retornarÃ¡.

public JSONObject requestPost(String sURL, JSONObject JSONSend) throws Exception{   
   //se crea el objeto de tipo URL  
   URL url = new URL(sURL);  
   //Convierte el JSONObject a un string y despues a un arreglo de Bytes  
   byte[] postDataBytes = JSONSend.toJSONString().getBytes("UTF-8");  
   //Abre la conexiÃ³n y asigna el objeto de la conexion a conn  
   HttpURLConnection conn = (HttpURLConnection)url.openConnection();  
   //establece el metodo o verbo de la conexiÃ³n
   conn.setRequestMethod("POST");  
   //se establece que la solicitud tendrÃ¡ salida  
    conn.setDoOutput(true);  
   //se escribe el cuerpo de la solicitud con el JSON convertido a Bytes  
   conn.getOutputStream().write(postDataBytes);  
   //Se realiza la solicitud y se lee la respuesta de la misma en el Reader  
   Reader in = new BufferedReader(new InputStreamReader(conn.getInputStream(), "UTF-8"));  
   //Se convierte el Reader en un String  
   StringBuilder sb = new StringBuilder();  
   for (int c; (c = in.read()) >= 0;)  
   sb.append((char)c);  
   String response = sb.toString();  
   //Se crea el objeto que parsearÃ¡ la respuesta  
   JSONParser parser = new JSONParser();  
   //Se parsea la respuesta en un JSONObject  
   JSONObject jsonResult = (JSONObject) parser.parse(response);  
   //se Retorna la respuesta  
   return jsonResult;
}

}

getKeyAgent

Esta funciÃ³n construirÃ¡ el JSON necesario para la autenticaciÃ³n contra el Agente de servicios web (ASW), paso seguido realizarÃ¡ la solicitud de GetAuth, y retornarÃ¡ el KeyAgente entregado por el servidor. Para esto tomarÃ¡ los valores de usuario y contraseÃ±a de los dos textfield creados en diseÃ±o y utilizaremos la funciÃ³n MD5 para encriptar la contraseÃ±a.

public String getControlKey {   
   //se define que por defecto entregarÃ¡ un key vacio.  
   String sKey ="";  
   try {  
      //se define la URL a donde se realizarÃ¡ la solicitud.  
      String sUR1 ="http://local.insoft.co:9000/dataspan/restTBasicoGeneral/\"GetAuth\"/";  
      //Abre la conexiÃ³n y asigna el objeto de la conexiÃ³n a conn  
      HttpURLConnection conn = (HttpURLConnection)url.openConnection();  
      //establece el metodo o verbo de la conexiÃ³n  
      conn.setRequestMethod("POST");  
      //Se establece que la solicitud tendrÃ¡ salida  
      conn.setDoOutput(true);  
      //se escribe el cuerpo de la solicitud con el JSON convertido a Bytes  
      conn.getOutputStream().write(postDataBytes);  
      //se realiza la solicitud y se lee la respuesta de la misma en el Reader.  
      Reader in = new BufferedReader(new InputStreamReader(conn.getInputStream(), "UTF-8"));  
      //se convierte el reader en un String.  
      StringBuilder sb = new StringBuilder();  
      for (int c; (c = in.read()) >= 0;)  
      sb.append((char)c);  
       String response = sb.toString();  
      //se crea el objeto que parsearÃ¡ la respuesta  
      JSONParser parser = new JSONParser();  
      //se parsea la respues en un JSONObject  
      JSONObject jsonResult = (JSONObject) parser.parse(response);  
      //Retorna la respuesta  
      return jsonResult;
}

}

getListaTerceros

Esta funciÃ³n recibe el key Agente obtenido anteriormente y construye el JSON para realizar la peticiÃ³n de obtener el listado de terceros, como resultado esta funciÃ³n retornara un JSONArray con el listado de terceros.

public JSONArray getListaTerceros(String keyAgent){  
   //Se define el resultado por defecto  
   JSONArray jResult = null;  
   try {  
      //Se define la URL a la cual se realizarÃ¡ la solicitud  
      String sURl =       "http://local.insoft.co:9000/datasnap/rest/TCatTerceros/\"GetListaTerceros\"/";  
      //Se define el JSON que contendrÃ¡ todos los parametros a enviar  
      JSONObject obj = new JSONObject();  
      //Se define el Array que contiene los 4 parametros de entrada de la funciÃ³n  
      JSONArray arr = new JSONArray();  
      //se define el objeto que contiene los datos de autenticaciÃ³n  
      JSONObject objTemp = new JSONObject();  
      //Se crea un JSON parse para definir JSON genÃ©ricos  
      JSONParser parser = new JSONParser();  
       //Se parsea y adiciona informaciÃ³n de los datos de la pÃ¡gina  
      objTemp.put("datospagina", (JSONObject) parser.parse("       {\"cantidadregistros\":\"99\",\"pagina\":\"1\"}"));  
      //Se parsea y adiciona los datos que se solicitarÃ¡n  
      objTemp.put("camposderetorno",(JSONArray) parser.parse("       [\"init\",\"ntercero\",\"napellido\"]"));  
      //se adiciona el primer parÃ¡metro con el JSON de datos  
      arr.add(objTemp.toJSONString());  
      //Se adiciona el keyAgent obtenido previamente  
      arr.add(keyAgent);  
      //Se adiciona el tercer parÃ¡metro que es el cÃ³digo del APP configurado  
      arr.add("1001");  
      //Se envÃ­a en el cuarto parÃ¡metro un random  
      arr.add("123");   
      //Se adiciona el arreglo de parametros al objeto a enviar  
      obj.put("\_parameters", arr);  
      //se realiza la solicitud POST  
      JSONObject objResult = requestPost(sURl,obj);  
      //Se obtiene el Arreglo con los datos  
      arr = (JSONArray)objResult.get("result");  
      obj = (JSONObject)arr.get(0);  
      obj = (JSONObject)obj.get("respuesta");  
      jResult = (JSONArray)obj.get("datos");  
      }  
          catch (Exception e) {  
         System.out.println(e.getMessage());  
      }  
//Se retorna el arreglo  
return jResult;  
}

ImplementaciÃ³n de las funciones

La implementaciÃ³n se realiza sobre el evento clic del botÃ³n, en este lo primero que se hace es obtener el keyAgente y validar que este sea correcto, paso seguido se obtiene el listado de terceros el cual es almacenado en un JSONArray el cual se recorrerÃ¡ para obtener cada uno de los objetos que se encuentran definidos en este y agregarlos al control JTable.

private void BtnSendRequestActionPerformed(java.awt.event.ActionEvent evt) {  
   //Se obtiene el KeyAgent del ASW  
   String sKey = getControlKey();  
   //Se muestra el KeyAgente que se ha obtenido  
   LblKeyAgent.setText("key agente de conexiÃ³n "+sKey);  
   //Se verifica que el keyAgente tenga algÃºn valor  
   if (sKey != ""){  
      //Se desactivan los controles relacionados al getAuth  
      edUser.setEnabled(false);  
      edPassword.setEnabled(false);  
      BtnSendRequest.setEnabled(false);  
      //Se solicita el listado de terceros  
      JSONArray arrTerceros = getListaTerceros(sKey);  
      //Se obtiene el modelo de la tabla visual que se tiene  
      DefaultTableModel model = (DefaultTableModel) DateViewTableJSON.getModel();  
      //Se define un vector que harÃ¡ referencia a cada registro  
      Vector row;  
      //Se define el jsonObject temporal para recorrer los registros  
      JSONObject record;  
      //se recorre el arreglo de registros  
      for (int i=0; i < arrTerceros.size(); i++){  
         //Se extrae el JSON de cada registro  
         record = (JSONObject)arrTerceros.get(i);  
         //Se crea el vector   
         row = new Vector();  
         //Se adicionan los valores al vector  
         row.add(record.get("init"));  
         row.add(record.get("ntercero"));  
         row.add(record.get("napellido"));  
         //se adiciona el vector al modelo   
         model.addRow(row);  
       }  
   }  
}

Resultado de la implementaciÃ³n

![](imagenes/resultado_implementacion.png)
![](imagenes/resultado_implementacion2.png)

Â©2016 InSoft Todos los derechos reservados.
