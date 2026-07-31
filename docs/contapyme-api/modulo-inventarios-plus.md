# Modulo inventarios plus

> Fuente original: https://www.contapyme.com/api/modulo-inventarios-plus.html

**MÃ³dulo inventarios plus**

CatÃ¡logos

Operaciones

Informes

|  |  |  |
| --- | --- | --- |
| **AÃºn no hay catÃ¡logos disponibles para Ã©ste mÃ³dulo.** | | |

|  |  |  |
| --- | --- | --- |
| **Operaciones** | | |
| **Operaciones de inventarios plus** | | |
| Nombre operaciÃ³n | Nombre corto | DescripciÃ³n |
| [GetConfigOprORD1()](050_INV_PLUS/020_Operaciones/010-GetConfigOprORD1.html) | Retorna la configuraciÃ³n de los campos de la operaciÃ³n de Pedido de un cliente (ORD1) | Esta funciÃ³n es la encargada de retornar la configuraciÃ³n de los campos de la operaciÃ³n de pedido, dicha configuraciÃ³n es asignada en el sistema ContaPyme / AgroWin en la opciÃ³n âConfiguraciÃ³nâ de dicha operaciÃ³n.   Por cada campo retorna si es visible, requerido, de solo lectura, valor por defecto, etiqueta y su configuraciÃ³n cuando es un campo de tipo lista.   TambiÃ©n retorna las opciones de configuraciÃ³n propias de la operaciÃ³n y la definiciÃ³n por defecto para las formas de cobro de la operaciÃ³n.   Para conocer los campos de la operaciÃ³n, consultar el documento: âDocumentaciÃ³n Json de la operaciÃ³n de pedido - ORD1â. |
| [GetConfigOprORD4()](050_INV_PLUS/020_Operaciones/020-GetConfigOprORD4.html) | Retorna la configuraciÃ³n de los campos de la operaciÃ³n de CotizaciÃ³n al cliente (ORD4) | Esta funciÃ³n es la encargada de retornar la configuraciÃ³n de los campos de la operaciÃ³n de cotizaciÃ³n al cliente, dicha configuraciÃ³n es asignada en el sistema ContaPyme / AgroWin en la opciÃ³n âConfiguraciÃ³nâ de dicha operaciÃ³n.   Por cada campo retorna si es visible, requerido, de solo lectura, valor por defecto, etiqueta y su configuraciÃ³n cuando es un campo de tipo lista.   TambiÃ©n retorna las opciones de configuraciÃ³n propias de la operaciÃ³n.   Para conocer los campos de la operaciÃ³n, consultar el documento: âDocumentaciÃ³n Json de la operaciÃ³n de CotizaciÃ³n â ORD4â. |
| [DocJsonOprORD1()](050_INV_PLUS/020_Operaciones/PDFS/010_DocJsonOprORD1.pdf) | Json de la operaciÃ³n de pedido de un cliente. | DocumentaciÃ³n del Json que se debe enviar para insertar una operaciÃ³n de pedido de un cliente en el sistema a travÃ©s del Agente. |
| [DocJsonOprORD4()](050_INV_PLUS/020_Operaciones/PDFS/030_DocJsonOprORD4.pdf) | Json de la operaciÃ³n de cotizaciÃ³n al cliente. | DocumentaciÃ³n del Json que se debe enviar para insertar una operaciÃ³n de cotizaciÃ³n a un cliente en el sistema a travÃ©s del Agente. |
| [DocJsonOprORD6()](050_INV_PLUS/020_Operaciones/PDFS/060_DocJsonOprORD6.pdf) | Json de la operaciÃ³n de recepciÃ³n de materiales. | DocumentaciÃ³n del Json que se debe enviar para insertar una operaciÃ³n de recepciÃ³n de materiales en el sistema a travÃ©s del Agente. |
| [DocJsonOprORD2()](050_INV_PLUS/020_Operaciones/PDFS/050_DocJsonOprORD2.pdf) | DocumentaciÃ³n del JSon de la operaciÃ³n de RemisiÃ³n al cliente. | DocumentaciÃ³n del Json que se debe enviar para insertar una operaciÃ³n de RemisiÃ³n al cliente en el sistema a travÃ©s del Agente. |
| [DocJsonOprORD5()](050_INV_PLUS/020_Operaciones/PDFS/070_DocJsonOprORD5.pdf) | DocumentaciÃ³n del Json de la operaciÃ³n de Orden de compra al proveedor. | DocumentaciÃ³n del Json que se debe enviar para insertar una operaciÃ³n de RemisiÃ³n al cliente en el sistema a travÃ©s del Agente. |
| [DocJsonOprOP1()](050_INV_PLUS/020_Operaciones/PDFS/080_DocJsonOprOP1.pdf) | DocumentaciÃ³n del Json de la operaciÃ³n de Orden de producciÃ³n. | DocumentaciÃ³n del Json que se debe enviar para insertar una operaciÃ³n de Orden de producciÃ³n en el sistema a travÃ©s del Agente. |
| **DocumentaciÃ³n de apoyo** | | |
| Nombre documento | Tipo | DescripciÃ³n |
| [DoExecuteOprAction()](010_MOD_BASICO/010_Catalogos/030_Cat_Operaciones/030-DoExecuteOprAction.html) | HTML | Permite la ejecuciÃ³n de una acciÃ³n sobre una o varias operaciones en el sistema. Acciones como Crear, guardar, cargar, procesar, desprocesar, verificar o anular. |
| [Modelo ER OprMaest](040_INVENTARIOS/020_Operaciones/010_DocApoyo/PDFS/OPRMAEST.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan la informaciÃ³n de encabezado de las operaciones. |
| [Modelo ER OprReferencias](040_INVENTARIOS/020_Operaciones/010_DocApoyo/PDFS/OPRREFERENCIAS.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan los nÃºmeros de documentos que se referencian en las operaciones del sistema. |
| [Modelo ER OprOp1](040_INVENTARIOS/020_Operaciones/010_DocApoyo/PDFS/OPROP1.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan el detalle de productos registrados en las operaciones de remisiÃ³n al cliente, orden de producciÃ³n, recepciÃ³n de materiales. |
| [Modelo ER OprTrazabProductos](040_INVENTARIOS/020_Operaciones/010_DocApoyo/PDFS/OPRTRAZABPRODUCTOS.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan la informaciÃ³n de cada serie/lote ingresado o egresado en las operaciones de inventarios e inventarios plus. |
| [Modelo ER OprOrd2\_Base](050_INV_PLUS/020_Operaciones/010_DocApoyo/PDFS/OPRORD2_BASE.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan los datos generales de una operaciÃ³n de RemisiÃ³n al cliente (GuÃ­a de despacho). |
| [Modelo ER OprOrd1](050_INV_PLUS/020_Operaciones/010_DocApoyo/PDFS/OPRORD1.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan los datos principales de la operaciÃ³n de Orden de compra al proveedor. |
| [Modelo ER OprOrd5\_Base](050_INV_PLUS/020_Operaciones/010_DocApoyo/PDFS/OPRORD5_BASE.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan los datos generales de una operaciÃ³n de Orden de compra al proveedor. |
| [**Arribaâ**](#inicio) | | |

  
  

|  |  |  |
| --- | --- | --- |
| **AÃºn no hay reportes disponibles para Ã©ste mÃ³dulo.** | | |

|  |  |  |
| --- | --- | --- |
| **Usted no tiene permisos para acceder a la documentaciÃ³n de la API del Agente de servicios web.** | | |
