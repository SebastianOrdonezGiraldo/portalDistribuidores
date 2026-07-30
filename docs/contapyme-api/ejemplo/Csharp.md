# Ejemplo C#.

> Fuente original: https://www.contapyme.com/api/ejemplo/Csharp.html

Solicitud REST al Agente de servicios web desde C#

[Objetivo](#primer_enlace)
[JSON](#segundo_enlace)
[Request](#tercer_enlace)
[Ejemplo](#cuarto_enlace)
[Descargar](CSHARP.zip)

## Objetivo

- Explicar de forma clara y precisa cÃ³mo es el manejo de JSON en el lenguaje C# Visual Studio.
- Explicar las funciones y objetos que ofrece C# para realizar una peticiÃ³n HTTP por medio del verbo POST.
- Presentar un ejemplo funcional donde se consuma recursos de la API que ofrece el agente de servicios web de ContaPyme desde el lenguaje de programaciÃ³n C# Visual Studio.

JSON

El manejo de JSON en el lenguaje de se puede realizar mediante la librerÃ­a llamada **"Newtonsoft.Json"** la cual se encuentra disponible en la dll **"Newtonsoft.Json.dll"**. Esta librerÃ­a ofrece diferentes funcionalidades para el manejo de JSON a travÃ©s de objetos de tipo **"Dictionary"** como tambiÃ©n permite la manipulaciÃ³n de JSON con los objetos JObject, JArray y JToken, y es una de la mÃ¡s recomendada por los programadores por su desempeÃ±o.


![](imagenes/performance.png)


Para la implementaciÃ³n de este documento se ha utilizado la manipulaciÃ³n de JSON a travÃ©s de listas Dictionary.

InstalaciÃ³n de la librerÃ­a para trabajar con JSON (desde C# Microsoft Visual Studio).

Primero necesitamos descargar la dll llamada Newtonsoft.Json.dll de su web oficial aquÃ­ <https://www.newtonsoft.com/json>, paso seguido debemos incorporarla a nuestro proyecto, para esto realizamos los siguientes pasos:

![](imagenes/php2.png)  

- Nos posicionamos sobre la soluciÃ³n en el Ã­tem **"referencias"** de nuestro proyecto y presionamos clic derecho y seleccionamos la opciÃ³n de **"agregar referencia".**
- Se nos abrirÃ¡ una ventana modal llamada **"Administrador de referencias"**, en esta ventana en la parte inferior derecha encontraremos un botÃ³n llamado **"Examinarâ¦"**, presionamos clic sobre Ã©l y seleccionamos la dll **Newtonsoft.Json.dll** de donde la hubiÃ©semos almacenado.

Objetos JSON

El manejo de objetos JSON se puede realizar mediante la clase llamada JObject, la cual ofrece mÃ©todos para agregar (add), remover (remove), verificar (TryGetValue) y obtener (Property y Properties) propiedades. Pero los objetos JSON tambiÃ©n se pueden representar en Diccionarios cuya llave sea una cadena y el valor un objeto en los cuales podremos utilizar los mÃ©todos ya conocidos de los diccionarios y en caso de necesitar la representaciÃ³n de un diccionario a un JSON utilizar la funciÃ³n **"JsonConvert.SerializeObject".**

| CÃ³digo C# | RepresentaciÃ³n en JSONObject |
| --- | --- |
| Obj = new Dictionary();   Obj.add("name","foo");   Obj.add("num",100);  Obj.add("balance",1000.21);  Obj.add("italia",true);  JsonConvert.SerializeObject(Obj); | { "name": " foo ", "num": 100, "balance": 1000.21, "Italia": true } |

Arreglos JSON

La clase JArray representa una secuencia ordenada de valores indexados donde los valores pueden ser de cualquier combinaciÃ³n (JSONObject, JSONArray, Integer, Double, Boolean, String), el uso de la librerÃ­a ofrece una serie de funciones para agregar (add), quitar (remove) y obtener (indexOf) Ã­tems de la lista. Pero los JSONArray tambiÃ©n los podremos definir como arreglos donde podremos utilizar los mÃ©todos y funciones ya conocidos de estos.

| CÃ³digo C# | RepresentaciÃ³n en JSONArray |
| --- | --- |
| arr = new Object[5];  arr[0] = "foo";  arr[1] = 100;  arr[2] = 1000.21;   arr[3] = true;  arr[4] = new Object();  JsonConvert.SerializeObject(arr); | { "name": " foo ", "num": 100, "balance": 1000.21, "Italia": true } |

Serializar y de serializar un JSON

La librerÃ­a tambiÃ©n ofrece funcionalidades que permiten convertir una cadena de texto en un objeto JSON la cual serÃ¡ muy Ãºtil para convertir la respuesta de la peticiÃ³n, para esto podremos usar la propiedad DeserializeObject del objeto JsonConvert:

| CÃ³digo C# para decodificar un JSON |
| --- |
| String str = '{  "name":"foo",   "num":100,   "balance": 1000.21,  "Italia":true,  "obj":{â¦â¦}  }'  Jbject obj = JsonConvert.DeserializeObject(str); |

La serializaciÃ³n que podemos definir como el proceso para convertir un JSON a una cadena de texto, la cual serÃ¡ muy Ãºtil para convertir un objeto JSON o Dictionary a un string para enviarlo en una peticiÃ³n la podremos realizar mediante el mÃ©todo SerializeObject de la clase JsonConvert.

| CÃ³digo C# | RepresentaciÃ³n del JSON |
| --- | --- |
| Obj = new Dictionary();   Obj.add("name","foo");   Obj.add("num",100);  Obj.add("balance",1000.21);   Obj.add("italia",true);  Obj.add("child",new Dictionary);  JsonConvert.SerializeObject(Obj); | { "name": " foo ", "num": 100, "balance": 1000.21, "is\_vip": true, "child":{} } |

Request

Objeto WebClient

Este objeto es utilizado para realizar las solicitudes de recursos a un servidor, para esto proporciona propiedades y mÃ©todo comunes para enviar y recibir datos de un recurso identificado por una URL o URI.

| CÃ³digo JAVA para abrir una solicitud HTTP |
| --- |
| //Se crea el Objeto para realizar la peticiÃ³n  WebClient webClient = new WebClient();  //En esta variable llegarÃ¡ la respuesta en un arreglo de Bytes  Byte[] resByte;  //Se define la URL a donde se realizarÃ¡ la solicitud  String URL = 'http://exapmple.com/idrecurso'  //Se definen los datos que se enviaran a la solicitud  Byte[] reqString = Encoding.Default.GetBytes('key=valor');  //Se genera la solicitud con los datos previos  resByte = webClient.UploadData(URL, "post", reqString);  //Por Ãºltimo se convierte la respuesta a un String  String resString = Encoding.Default.GetString(resByte); |

Ejemplo funcional

El siguiente ejemplo realiza una peticiÃ³n que muestra el listado de terceros obtenidos de un agente de servicios web de ContaPyme (ASW) en una tabla de tipo JTable. Para esto primero se debe hacer GetAuth contra el agente para obtener el KeyAgent con el cual se realizarÃ¡ la solicitud de listado de terceros.

RequestPOST

El objetivo de esta funciÃ³n es realizar una peticiÃ³n POST a una URL que recibe como parÃ¡metro, para esto la funciÃ³n tambiÃ©n recibe un objeto de tipo Dictionary que serÃ¡ el que enviarÃ¡ en el cuerpo de la peticiÃ³n, la funciÃ³n despuÃ©s de hacer la solicitud retornara un objeto JObject que se ha entregado como respuesta.

private JObject RequestPost(String URL, Dictionary JSonData) {  
   //Se crea el Objeto que realizarÃ¡ la solicitud REST   
   WebClient webClient = new WebClient();  
   try {  
      //Se convierte el dictionary que representa los datos en un String  
      String strJSON = JsonConvert.SerializeObject(JSonData, Formatting.None);  
      //El String con el JSON se convierte a un arreglo de Bytes  
      Byte[] reqString = Encoding.Default.GetBytes(strJSON);  
       //Se realiza la solicitud por el verbo POST enviando el JSON representado en Bytes  
      Byte[] resByte = webClient.UploadData(URL, "post", reqString);  
      //Se convierte la respuesta que es un arreglo de Bytes a un String   
      String resString = Encoding.Default.GetString(resByte);  
      //Se libera el Objeto de la conexiÃ³n  
      webClient.Dispose();  
      //Se retorna la respuestaString en formato JObject  
      return JsonConvert.DeserializeObject(resString);  
   } catch (Exception ex) {  
      //Se imprime en consola el error generado  
      Console.WriteLine(ex.Message);  
    }  
   //Se envÃ­a una respuesta vacia en caso de no pederse realizar la solicitud  
   return JsonConvert.DeserializeObject("{}");  
}

SendRequestGetAuth

Esta funciÃ³n construirÃ¡ el JSON necesario para la autenticaciÃ³n contra el Agente de servicios web (ASW), paso seguido realizarÃ¡ la solicitud de GetAuth, y retornarÃ¡ el KeyAgente entregado por el servidor. Para esto tomarÃ¡ los valores de usuario y contraseÃ±a de los dos textbox creados en diseÃ±o y utilizaremos la funciÃ³n MD5 para encriptar la contraseÃ±a.

private String SendRequestGetAuth() {  
   //Se define la URL a donde se realizarÃ¡ la peticion de GetAuth  
   String URLPost = "http://local.insoft.co:9000/datasnap/rest/TBasicoGeneral/\"GetAuth\"/";  
   //Se define el Objeto que se enviarÃ¡ para ser la autenticaciÃ³n  
   Dictionary ObjSend = new Dictionary();  
   //Se define el Objeto que tendrÃ¡ los datos de autenticaciÃ³n  
   Dictionary ObjParams = new Dictionary();  
   //Se adiciona el email al objeto a enviar  
   ObjParams.Add("email", EdEmail.Text);  
   //Se adiciona el identificador de la maquina  
   ObjParams.Add("idmaquina", "1");  
   //Se envia la contraseÃ±a encriptada en MD5  
   ObjParams.Add("password", MD5EncryptPass(EdClave.Text.ToUpper()));   
   //Se construye el arreglo donde se enviaran los 4 parÃ¡metros  
   string[] arrParams = new string[4];  
   //El primer parÃ¡metro es un String con los datos de autenticaciÃ³n  
   arrParams[0] = JsonConvert.SerializeObject(ObjParams, Formatting.None);  
   //El segundo parÃ¡metro es el key agente que no aplica pues es el dato que estamos pidiendo  
   arrParams[1] = "";  
   //El tercer parÃ¡metro es el cÃ³digo del APP configurado  
   arrParams[2] = "1001";  
   //El cuarto parÃ¡metro es un random  
   arrParams[3] = "5555";  
   //Se adiciona el arreglo de paramatros al objeto a enviar  
   ObjSend.Add("\_parameters", arrParams);  
   //Se realiza la solicitud REST y se almacena en el ObjResult  
  
   JObject ObjResult = RequestPost(URLPost, ObjSend);  
   //Se definen variables temporales que se usaran para acceder al JSON  
   JArray arrTemp = null;  
   JObject objTemp = null;  
   JToken tokTemp = null;  
   //Se defien que por defecto la respuesta serÃ¡ vacia  
   string str = "";  
   //Se extrae el Result de la respuesta  
   if (ObjResult.TryGetValue("result", out tokTemp) && (tokTemp != null)) {  
      //Se convierte a un JSON Array  
      arrTemp = (JArray)tokTemp;  
      //Se verifica que no este vacio y tenga un valor  
      if ((arrTemp != null) && (arrTemp.Count > 0)) {  
         //Se extreae el objeto que estÃ¡ en la primera posiciÃ³n del arreglo  
          objTemp = arrTemp[0] as JObject;  
         //Se extrea el ebjeto de la respuesta y se verifica que tenga valor  
         if (objTemp.TryGetValue("respuesta", out tokTemp) && (tokTemp != null)) {  
            //Se parsea el token extraido a un JObject  
            objTemp = (JObject)tokTemp;  
            //Se extrae el Objeto datos del objeto y se verifica que tenga valor  
            if (objTemp.TryGetValue("datos", out tokTemp) && (tokTemp != null)) {  
               //Se pasea el token a un objeto  
               objTemp = (JObject)tokTemp;  
               //Se extrea el key agente y se verifica que tenga valor  
               if (objTemp.TryGetValue("keyagente", out tokTemp) && (tokTemp != null))   
                 //Se convierte el token a un string  
                 str = (string)tokTemp;  
               }  
            }  
          }  
       }  
       //Se verifica si el keyAgente tiene algun valor  
      if (str == "")  
         MessageBox.Show("No se ha podido iniciar sesiÃ³n con el servidor", "", MessageBoxButtons.OK, MessageBoxIcon.Error);  
      //Se retorna el key agente  
       return str;  
}

getListaTerceros

Esta funciÃ³n recibe el keyAgente obtenido anteriormente y construye el JSON para realizar la peticiÃ³n de obtener el listado de terceros, como resultado esta funciÃ³n retornara un JSONArray con el listado de terceros.

private JArray GetListTerceros(String keyAgent) {  
   //Se define el Objeto que se enviarÃ¡ en el cuerpo de la peticiÃ³n  
   Dictionary ObjSend = new Dictionary();  
   //Se define el Objeto que tendrÃ¡ los datos de autenticaciÃ³n  
   Dictionary ObjParams = new Dictionary();  
   //Se define la URL donde se realizarÃ¡ la peticion de solicitud de listado de terceros  
   string URLPost =    "http://local.insoft.co:9000/datasnap/rest/TCatTerceros/\"GetListaTerceros\"/";  
   //Se define el Objeto con los datos de pÃ¡gina   
   Dictionary ObjDatosPagina = new Dictionary();  
   ObjDatosPagina.Add("cantidadregistros", "99");  
   ObjDatosPagina.Add("pagina", "1");  
   //Se agrega el objeto de los datos de la pÃ¡gina  
   ObjParams.Add("datospagina", ObjDatosPagina);  
   //Se agrega el arreglo con los datos que se solicitaran en la lista  
   ObjParams.Add("camposderetorno", new string[] { "init", "ntercero", "napellido" });  
   //Se construye el arreglo donde se enviaran los 4 parÃ¡metros  
   string[] arrParams = new string[4];  
   //El primer parÃ¡metro es el JSON con los datos a envÃ­ar  
   arrParams[0] = JsonConvert.SerializeObject(ObjParams, Formatting.None);  
   //El segundo parÃ¡metro es el KeyAgente entregado enla funcion de GetAuth  
   arrParams[1] = keyAgent;  
   //El tercer parÃ¡metro es el cÃ³digo del APP configurado  
   arrParams[2] = "1001";  
   //El cuarto parÃ¡metro es un random  
   arrParams[3] = "5555";  
   //Se adiciona el arreglo de paramatros al objeto a enviar  
   ObjSend.Add("\_parameters", arrParams);  
   //Se realiza la solicitud REST y se almacena en el ObjResult con la respuesta de la peticion  
   JObject ObjResult = RequestPost(URLPost, ObjSend);  
   //Se definen variables temporales que se usaran para acceder al JSON  
   JArray arrTemp = null;  
   JObject objTemp = null;  
   JToken tokTemp = null;  
   //Se extrae el Result de la respuesta  
   if (ObjResult.TryGetValue("result", out tokTemp) && (tokTemp != null)) {  
      //Se convierte a un JSON Array  
      arrTemp = (JArray)tokTemp;  
      //Se verifica que no este vacio y tenga un valor  
      if ((arrTemp != null) && (arrTemp.Count > 0)) {  
         //Se extreae el objeto que estÃ¡ en la primera posiciÃ³n del arreglo  
         objTemp = arrTemp[0] as JObject;  
         //Se extrea el ebjeto de la respuesta y se verifica que tenga valor  
         if (objTemp.TryGetValue("respuesta", out tokTemp) && (tokTemp != null)) {  
            //Se parsea el token extraido a un JObject  
            objTemp = (JObject)tokTemp;  
            //Se extrae el arreglo datos del objeto y se verifica que tenga valor  
            if (objTemp.TryGetValue("datos", out tokTemp) && (tokTemp != null)) {  
               //Se parsea el arreglo con los datos  
               arrTemp = (JArray)tokTemp;  
            }  
         }  
      }  
   }  
      //Se retorna el arreglo con los datos  
      return arrTemp;  
   }

ImplementaciÃ³n de las funciones

La implementaciÃ³n se realiza sobre el evento clic del botÃ³n, en este lo primero que se hace es obtener el keyAgente y validar que este sea diferente de vacÃ­o, paso seguido se crean los diferentes objetos que se necesitaran para mostrar la tabla con los datos y obtiene el listado de terceros el cual es almacenado en un JArray el cual se recorrerÃ¡ para obtener cada uno de los objetos que se encuentran definidos en este y agregarlos al control DataRow que finalmente se agregarÃ¡ a la tabla.

private void BtnRequest\_Click(object sender, EventArgs e) {  
   //Se solicita el keyAgente  
   string sKey = SendRequestGetAuth();  
   //Se verifica que el keyAgent tenga un valor  
   if (sKey != "") {  
      //Se escribe el keyAgente en el label  
      LblControlKey.Text = "Key de autorizaciÃ³n: " + sKey;  
      //Se desactivan los controles para no volver a solicitar inicio sesiÃ³n  
      ButtonGetAuth.Enabled = false;  
      EdEmail.Enabled = false;  
      EdClave.Enabled = false;  
      //Se crea una nueva tabla llamada terceros  
      DataTable tbl = new DataTable("Terceros");  
      //Se adicionan las columnas a la tabla con su respectivo tipo  
      tbl.Columns.Add(new DataColumn("ID", System.Type.GetType("System.String")));  
      tbl.Columns.Add(new DataColumn("Nombre", System.Type.GetType("System.String")));  
      tbl.Columns.Add(new DataColumn("Apellido", System.Type.GetType("System.String")));  
      //Se define una variable de tipo registro  
      DataRow registro;  
      //Se obtiene el listado de terceros  
      JArray arrTerceros = GetListTerceros(sKey);  
      //Se define un Objeto temporal para el momento de recorrer el arreglo  
      JObject JRecord = null;  
      //Se recorre el arreglo con el listado de terceros  
      for (int i = 0; i < arrTerceros.Count; i++) {  
         //Se extrea el objeto que viene la posicion i del arreglo  
         JRecord = (JObject)arrTerceros[i];  
         //Se define que el registro serÃ¡ una nueva fila  
         registro = tbl.NewRow();  
         //Se adicionan los datos  
         registro["ID"] = JRecord.GetValue("init");  
         registro["Nombre"] = JRecord.GetValue("ntercero");  
         registro["Apellido"] = JRecord.GetValue("napellido");  
         //Se agrega el registro a la tabla creada  
         tbl.Rows.Add(registro);  
      }  
      //Se agrega la tabla al DataSet  
      DataSetJSON.Tables.Add(tbl);  
      //Se define a la grid que muestre la tabla llamada terceros  
      dataGridViewJSON.DataSource = DataSetJSON.Tables["Terceros"];  
      }  
   }

Resultado de la implementaciÃ³n

![](imagenes/php3.png)
![](imagenes/php4.png)

Â©2016 InSoft Todos los derechos reservados.
