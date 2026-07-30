# Inicio

> Fuente original: https://www.contapyme.com/api/

**API Agente de Servicios Web**

El web service (Agente) de ContaPyme, expone una serie de funciones que le permitirÃ¡n acceder a la informaciÃ³n almacenada en su sistema central. AquÃ­ encontrarÃ¡ la documentaciÃ³n de cada funciÃ³n con sus respectivos parÃ¡metros de entrada y de salida.
  
  
Tenga en cuenta que:
  
  

- El protocolo de transferencia de datos es Rest
- Los llamados y las respuestas de las peticiones estÃ¡n en formato Json

  
  

Â¿CÃ³mo establecer comunicaciÃ³n con el Agente?

---

Lo primero que usted debe hacer para trabajar con el Agente es iniciar sesiÃ³n, con ello obtendrÃ¡ un identificador de usuario que le permitirÃ¡ realizar cualquier peticiÃ³n al Agente.
  
La funciÃ³n que debe llamar para iniciar sesiÃ³n en el Agente es: GetAuth()
  
  
  
  
  

[Ver documentaciÃ³n](005_INTRODUCCION/020-GetAuth.html)

Â¿CÃ³mo realizar una peticiÃ³n al Agente?

---

DespuÃ©s de obtener el identificador del usuario, puede realizar cualquier peticiÃ³n al Agente para obtener o insertar informaciÃ³n en su sistema ContaPyme.
  
Para conocer los nombres de las funciones y sus respectivos parÃ¡metros, consulte la documentaciÃ³n de los mÃ³dulos disponibles

Â¿CÃ³mo registrar una operaciÃ³n a travÃ©s de el Agente?

---

Para registrar una operaciÃ³n en el sistema se debe llamar la funciÃ³n DoExecuteOprAction() enviando la acciÃ³n "New", el identificador del tipo de operaciÃ³n a crear y los datos a registrar de la operaciÃ³n.
  
Cada operaciÃ³n posee la documentaciÃ³n del Json que recibe la funciÃ³n, Ã©sta se encuentra en el mÃ³dulo al que pertenece la operaciÃ³n a crear.
  
  
  

[Ver documentaciÃ³n](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/030-DoExecuteOprAction.html)

  
  

Â¿CÃ³mo obtener un informe en PDF a travÃ©s del Agente?

---

A travÃ©s del Agente es posible obtener cualquier informe del sistema en formato PDF, para ello se debe llamar la funciÃ³n GetPDF() enviando el cÃ³digo del reporte a obtener y los parÃ¡metros necesarios para la generaciÃ³n del mismo.
  
La funciÃ³n retornarÃ¡ el informe en formato PDF.
  
  
  
  

[Ver documentaciÃ³n](010_MOD_BASICO/030_Informes/010-GetPDF.html)

Â¿CÃ³mo cerrar la conexiÃ³n con el Agente?

---

Para cerrar la conexiÃ³n con el Agente se debe llamar la funciÃ³n Logout() enviando sÃ³lo el identificador de la sesiÃ³n del usuario. La funciÃ³n retornarÃ¡ la confirmaciÃ³n del cierre de la sesiÃ³n del usuario.
  
  
  
  
  
  
  

[Ver documentaciÃ³n](005_INTRODUCCION/130-Logout.html)

Â¿CÃ³mo verificar el estado del Agente?

---

Por medio de la funciÃ³n Test() es posible conocer el estado del Agente, es decir si estÃ¡ en ejecuciÃ³n, detenido, conectado al Ã¡rea de trabajo o no conectado.
  
  
  
  
  
  
  
  
  

[Ver documentaciÃ³n](005_INTRODUCCION/010-Test.html)

  

|  |  |  |  |  |  |  |
| --- | --- | --- | --- | --- | --- | --- |
| **DocumentaciÃ³n por mÃ³dulo** | | | | | | |
|  | | | | | | |
|  |  | MÃ³dulo bÃ¡sico |  |  |  | MÃ³dulo cartera y proveedores |
|  | | | | | | |
|  |  | MÃ³dulo contabilidad |  |  |  | MÃ³dulo costos |
|  | | | | | | |
|  |  | MÃ³dulo inventarios |  |  |  | MÃ³dulo activos |
|  | | | | | | |
|  |  | MÃ³dulo inventarios plus |  |  |  | MÃ³dulo actividades |
|  | | | | | | |
|  |  | MÃ³dulo automatizaciÃ³n de documentos |  |  |  |  |

|  |  |  |  |
| --- | --- | --- | --- |
| **Usted no tiene permisos para acceder a la documentaciÃ³n de la API del Agente de servicios web.** | | | |
