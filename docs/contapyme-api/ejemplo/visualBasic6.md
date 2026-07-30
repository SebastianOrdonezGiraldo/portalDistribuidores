# Ejemplo Visual Basic 6

> Fuente original: https://www.contapyme.com/api/ejemplo/visualBasic6.html

Solicitud REST al Agente de servicios web desde Visual Basic 6

[Objetivo](#primer_enlace)
[JSON](#segundo_enlace)
[Request](#tercer_enlace)
[Ejemplo](#cuarto_enlace)
[Descargar](VISUAL BASIC 6.zip)

## Objetivo

- Explicar de forma clara y precisa cÃ³mo es el manejo de JSON en el lenguaje Visual Basic 6.
- Explicar las funciones y objetos que ofrece Visual Basic 6 para realizar una peticiÃ³n HTTP por medio del verbo POST.
- Presentar un ejemplo funcional donde se consuma recursos de la API que ofrece el agente de servicios web de ContaPyme desde el lenguaje de programaciÃ³n Visual Basic 6.

JSON

Visual basic 6 como tal no tiene objetos nativos para el manejo de JSON pero se puede realizar este manejo mediante la librerÃ­a llamada **âJsonConverterâ.** Esta librerÃ­a ofrece diferentes funcionalidades para el manejo de JSON a travÃ©s de objetos de tipo **âDictionaryâ** los cuales se pueden implementar bajo el mÃ³dulo de clase llamado Dictionary.





InstalaciÃ³n de los mÃ³dulos para manejo de JSON

MÃ³dulo Dictionary

![](imagenes/VB6.png)  

Primero necesitaremos adicionar el mÃ³dulo de clase para el manejo de diccionarios para el lenguaje de VB6 llamado **âVBA-Dictionaryâ** el cual podremos descargar desde su web oficial aquÃ­ <https://github.com/VBA-tools/VBA-Dictionary>, una vez descargado procedemos a adicionarlo a nuestro proyecto realizando los siguientes pasos:

- Nos posicionamos sobre nuestro proyecto en Visual Basic 6, presionamos clic derecho sobre el nombre del proyecto, nos posicionamos sobre la opciÃ³n de âAgregarâ y damos clic en la opciÃ³n llamada **âMÃ³dulo de claseâ.**

![](imagenes/VB6.1.png)

- En la ventana modal se nos presenta nos posicionamos sobre la pestaÃ±a llamada existente, buscamos y seleccionamos el archivo llamado **âDictionary.clsâ**, paso seguido presionamos clic sobre la opciÃ³n abrir.

MÃ³dulo JSONConverter

![](imagenes/VB6.2.png)

Para agregar este mÃ³dulo es requerido haber agregado al proyecto el mÃ³dulo de clase llamado **âDictionaryâ** puesto que es requerido por este. Este mÃ³dulo ofrecerÃ¡ las funciones y objetos necesarios para el manejo de JSON, permitiendo convertir cadenas de texto para convertirlas en diccionarios y viceversa, para descargar esta librerÃ­a lo podremos hacer desde la web oficial en <https://github.com/VBA-tools/VBA-JSON>, y una vez descargada ejecutar los siguientes pasos:

- Nos posicionamos sobre nuestro proyecto en Visual Basic 6, presionamos clic derecho sobre el nombre del proyecto, nos posicionamos sobre la opciÃ³n de âAgregarâ y damos clic en la opciÃ³n llamada âMÃ³duloâ.

![](imagenes/VB6.3.png)

En la ventana modal se nos presenta nos posicionamos sobre la pestaÃ±a llamada existente, buscamos y seleccionamos el archivo llamado âJsonConverter.basâ, paso seguido presionamos clic sobre la opciÃ³n abrir.

De esta manera tendremos las librerÃ­as agregadas a nuestro proyecto en Visual Basic 6 podremos trabajar con JSON.

Objetos JSON

El manejo de objetos JSON se realiza bajo objetos de tipo Dictionary y utilizando los mÃ©todos que este ofrece para adicionar pares (add), asignar un valor o agregarlos (item), remover una llave (remove), verificar si una llave existe (Exists) o para eliminar todos los Ã­tems de un objeto (removAll).

| CÃ³digo Visual Basic 6 | RepresentaciÃ³n en JSONObject |
| --- | --- |
| Dim Obj = as Dictionary  Set Obj = New Dictionary  Set Obj.Item(ânameâ) = âfooâ  'Obj.add(ânameâ,âfooâ)  Set Obj.Item(âbalanceâ) = 1000.21  'Obj.add(âbalanceâ, 1000.21)  Set Obj.Item(âitaliaâ) = true   'Obj.add(âitaliaâ, true) | { "name": " foo ", "num": 100, "balance": 1000.21, "Italia": true } |

Arreglos JSON

El manejo de arreglos JSON en Visual Basic 6 lo podemos hacer mediante arreglo convencionales los cuales se pueden agregar como valores a objetos previamente definidos o bajo objetos de tipo **âCollectionâ** segÃºn sea el caso, es decir si se necesita un JSONArray que sÃ³lo tendrÃ¡ llaves de tipo String es mÃ¡s fÃ¡cil definirlo con arreglos convencionales, pero si se necesita definir un arreglo con diferentes tipos de datos es mejor definirlo como una colecciÃ³n (Collection).

| CÃ³digo Visual Basic 6 | RepresentaciÃ³n en JSONArray |
| --- | --- |
| Dim Arr as Collection  Set Arr = New Collection  Arr.add "foo"  Arr.add 100  Arr.add 1000.21  Arr.add true  Arr.add new Dictionary | { "name": " foo ", "num": 100, "balance": 1000.21, "Italia": true } |

Serializar y de serializar un JSON

El mÃ³dulo de JSONConverter tambiÃ©n ofrece funcionalidades que permiten convertir una cadena de texto en un objeto JSON la cual serÃ¡ muy Ãºtil para convertir la respuesta de la peticiÃ³n, para esto podremos usar la propiedad ParseJson del objeto JsonConverter:

| CÃ³digo Visual Basic 6 para decodificar un JSON |
| --- |
| Dim str as String = '{  ânameâ:âfooâ,   ânumâ:100,   âbalanceâ: 1000.21,  âItaliaâ:true,  âobjâ:{â¦â¦}  }'  Dim obj as Dictionary   Set obj = JsonConverter.ParseJson(str) |

La serializaciÃ³n que podemos definir como el proceso para convertir un JSON a una cadena de texto, la cual serÃ¡ muy Ãºtil para convertir un objeto Dictionary a un string para enviarlo en una peticiÃ³n la podremos realizar mediante el mÃ©todo ConvertToJson de la clase JsonConvert.

| CÃ³digo Visual Basic 6 | RepresentaciÃ³n del JSON |
| --- | --- |
| Dim Obj = as Dictionary  Set Obj = New Dictionary   Obj.add(ânameâ,âfooâ)  Obj.add(âbalanceâ, 1000.21)   Obj.add(âitaliaâ, true)  Obj.add(âchildâ, New Dictionary)  Dim Str as String   str = JsonConvert. ConvertToJson(Obj) | { "name": " foo ", "num": 100, "balance": 1000.21, "is\_vip": true, "child":{} } |

Request

Objeto WinHttp.WinHttpRequest

Esta objeto es utilizado para realizar las solicitudes de recursos a un servidor, para esto proporciona propiedades y mÃ©todo comunes para enviar y recibir datos de un recurso identificado por una URL o URI.

| CÃ³digo Visual Basic 6 para definir una un objeto WinHttp.WinHttpRequest y hacer una solicitud |
| --- |
| //Se define la variable con el objeto  Dim winH As WinHttp.WinHttpRequest  //Se crea el Objeto para realizar la peticiÃ³n  Set winH = new WinHttp.WinHttpRequest  //En esta variable llegarÃ¡ la respuesta como un string  Dim strResult as String  //Se define la URL a donde se realizarÃ¡ la solicitud  Dim URL as String  URL = 'http://example.com/idrecurso'  //Se abre la conexiÃ³n con el servidor definiendo el verbo  winH.Open "post", URL  //Se genera la solicitud enviando un JSONObject  winH.Send â{}â  //Por Ãºltimo se lee la respuesta en formato string  strResult = winH.ResponseText |

Ejemplo funcional

El siguiente ejemplo realiza una peticiÃ³n que muestra el listado de terceros obtenidos de un agente de servicios web de ContaPyme (ASW) en un ListBox. Para esto primero se debe hacer GetAuth contra el agente para obtener el KeyAgent con el cual se realizarÃ¡ la solicitud de listado de terceros.

PostRequest

El objetivo de esta funciÃ³n es realizar una peticiÃ³n POST a una URL que recibe como parÃ¡metro, para esto la funciÃ³n tambiÃ©n recibe un objeto de tipo Dictionary que serÃ¡ el que enviarÃ¡ en el cuerpo de la peticiÃ³n, la funciÃ³n despuÃ©s de hacer la solicitud retornara un objeto de tipo Dictionary que se ha entregado como respuesta.

Private Function PostRequest(URL As String, Data As Dictionary) As Dictionary  
   'Se define el Objeto para realiza la peticiÃ³n POST  
   Dim winH As WinHttp.WinHttpRequest  
   'Se crea el objeto previamente definido  
   Set winH = New WinHttp.WinHttpRequest  
   'Se realiza la apertura de la conexiÃ³n a la URL con verbo POST  
   winH.Open "post", URL  
   'Se envÃ­a el string con el JSON de datos  
   winH.Send JsonConverter.ConvertToJson(Data)  
   'Se retorna el diccionario  
   Set PostRequest = JsonConverter.ParseJson(winH.ResponseText)  
   End Function

GetAuth

Esta funciÃ³n construirÃ¡ el JSON necesario para la autenticaciÃ³n contra el Agente de servicios web (ASW), paso seguido realizarÃ¡ la solicitud de GetAuth, y retornarÃ¡ el KeyAgente entregado por el servidor. Para esto tomarÃ¡ los valores de usuario y contraseÃ±a de los dos textbox creados en diseÃ±o.

Private Function GetAuth() As String  
   'Se define el objeto que se enviarÃ¡  
   Dim JSONSend As Dictionary  
   'Se define el objeto que tendrÃ¡ los parÃ¡metros a enviar  
   Dim JParams As Dictionary  
   'Se crean los objetos previamente definidos  
   Set JSONSend = New Dictionary  
   Set JParams = New Dictionary  
   'Se adicionan los parÃ¡metros necesarios para hacer el GetAuth  
   JParams.Item("email") = EdUser.Text  
   JParams.Item("password") = EdPassword.Text  
   JParams.Item("idmaquina") = "1"  
   'Se define el arreglo parÃ¡metros generales que se enviaran  
   Dim ArrParam(3) As String  
   'Se agregan los 4 parÃ¡metros al arreglo  
   ArrParam(0) = JsonConverter.ConvertToJson(JParams)  
   ArrParam(1) = "0"  
   ArrParam(2) = "1001"  
   ArrParam(3) = "123"  
   'Se agrega el arreglo al objeto a enviar  
   JSONSend.Item("\_parameters") = ArrParam  
   'Se define la URL a donde se realizarÃ¡ la peticiÃ³n  
   Dim URL As String  
   URL = "http://local.insoft.co:9000/datasnap/rest/TBasicoGeneral/""GetAuth""/"  
   'Se define el objeto que tendrÃ¡ la respuesta  
   Dim JSONResult As Dictionary  
   'Se realiza la peticiÃ³n con los datos previamente definidos  
   Set JSONResult = PostRequest(URL, JSONSend)  
   'Se defien el resultado como vacio por defecto  
   GetAuth = ""  
   'Se verifica que el JSON tenga un valor  
   If JsonConverter.ConvertToJson(JSONResult) <> "" Then  
      'Se verifica que no se existan eventualidades en la peticiÃ³n  
      If JSONResult("result")(1)("encabezado")("resultado") <> "true" Then  
         MsgBox JSONResult("result")(1)("encabezado")("mensaje")  
      Else  
         'Se asgina el keyAgente como resultado que se retornarÃ¡  
         GetAuth = JSONResult("result")(1)("respuesta")("datos")("keyagente")  
      End If  
   End If  
End Function

GetListTerceros

Esta funciÃ³n recibe el keyAgente obtenido previamente y construye el JSON para realizar la peticiÃ³n de obtener el listado de terceros, como resultado esta funciÃ³n retornara un Dictionary con el listado de terceros.

Private Function GetListTerceros(keyAgent As String) As Dictionary  
   'Se define y asigna valor a la URL que se solicitarÃ¡  
   Dim URLPost As String  
   URLPost = "http://local.insoft.co:9000/datasnap/rest/TCatTerceros/""GetListaTerceros""/"  
   'Se define el objeto que se enviarÃ¡  
   Dim JSONSend As Dictionary  
   'Se define el objeto que tendrÃ¡ los parÃ¡metros a enviar  
   Dim JParams As Dictionary  
   'Se crean los objetos previamente definidos  
   Set JSONSend = New Dictionary  
   Set JParams = New Dictionary  
   'Se agrega el objeto con los datos de paginaciÃ³n que se enviaran  
   Set JParams.Item("datospagina") =
   JsonConverter.ParseJson("   {""cantidadregistros"":""99"",""pagina"":""1""}")  
   'Se agregan el arreglo de campos que se solicitaran en el listado  
   Set JParams.Item("camposderetorno") =
   JsonConverter.ParseJson("   [""init"",""ntercero"",""napellido""]")  
   'Se define el arreglo parÃ¡metros generales que se enviaran  
   Dim arrParams(3) As String  
   'Se agregan los 4 parÃ¡metros al arreglo  
   arrParams(0) = JsonConverter.ConvertToJson(JParams)  
   arrParams(1) = keyAgent  
   arrParams(2) = "1001"  
   arrParams(3) = "5555"  
   'Se agrega el arreglo al objeto a enviar  
   JSONSend.Item("\_parameters") = arrParams  
   'Se define el objeto que tendrÃ¡ la respuesta  
   Dim ObjResult As Dictionary  
   'Se realiza la peticiÃ³n a la URL definida con el JSON previo  
   Set ObjResult = PostRequest(URLPost, JSONSend)  
   'Se define que el resultado serÃ¡ la respuesta que se encuentra dentro del JSON entregado  
   Set GetListTerceros = ObjResult("result")(1)("respuesta")  
End Function

ImplementaciÃ³n de las funciones

La implementaciÃ³n se realiza sobre el evento clic del botÃ³n, en este lo primero que se hace es obtener el keyAgente y validar que este sea diferente de vacÃ­o, paso seguido se obtiene el listado de terceros el cual es almacenado en un diccionario el cual se recorrerÃ¡ para obtener cada uno de los objetos que se encuentran definidos en este y agregarlos al control ListBox.

Private Sub ButtonAction\_Click()  
   'Se eliminan los elementos del listBox  
   List1.Clear  
   'Se define la variable que tendrÃ¡ el keyAgente  
   Dim sKey As String  
   'Se solicita el KeyAgente  
   sKey = GetAuth()  
   'Se verifica que el keyAgente tenga un valor  
   If sKey <> "" Then  
      'Se difine la variable que tendra la lista de terceros  
      Dim arrTerceros As Dictionary  
      'Se define el string que tendra la informaciÃ³n de cada tercero  
      Dim StrTem As String  
      'Se solicita y almacena el listado de terceros  
      Set arrTerceros = GetListTerceros(sKey)  
      'Se define una variable i para recorrer el listado  
      Dim i As Integer  
      'Se recorre el listado de terceros retornado  
      For i = 1 To arrTerceros("datos").Count - 1  
         'Se obtiene los datos de cada registro  
         StrTem = arrTerceros("datos")(i)("init")  
         StrTem = StrTem & " " & arrTerceros("datos")(i)("ntercero")  
         StrTem = StrTem & " " & arrTerceros("datos")(i)("napellido")  
         'Se agrega cada registro como item al ListBox  
         List1.AddItem (StrTem)  
      Next  
   End If  
End Sub

Resultado de la implementaciÃ³n

![](imagenes/VB6.4.png)
![](imagenes/VB6.5.png)

Â©2016 InSoft Todos los derechos reservados.
