# Modulo Contabilidad

> Fuente original: https://www.contapyme.com/api/modulo-contabilidad.html

**MÃ³dulo contabilidad**

CatÃ¡logos

Operaciones

Informes

[Estados financieros](#tc1)

[Conceptos de liquidaciÃ³n](#tc2)

[Conceptos de flujos de efectivo](#tc3)

[Monedas](#tc4)

[Tipos de movimientos bancarios](#tc5)

|  |  |  |
| --- | --- | --- |
| **CatÃ¡logo estados financieros** | | |
| **Funciones relacionadas con estados financieros** | | |
| Nombre funciÃ³n | Nombre corto | DescripciÃ³n |
| [GetInfoEstadosFinancieros()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/010-GetInfoEstadosFinancieros.html) | Retorna los saldos y movimientos de una cuenta o conjunto de cuentas a una determinada fecha | Esta funciÃ³n es la encargada de retornar la informaciÃ³n de saldos y movimientos contables de una cuenta o conjunto de cuentas.   Retorna datos como: Saldo anterior, movimientos dÃ©bito y crÃ©dito del perÃ­odo consultado, nuevo saldo, porcentaje de variaciÃ³n del saldo, entre otros.   Con esta funciÃ³n es posible obtener la informaciÃ³n contable de un conjunto de cuentas hijas o solo de las cuentas padre del PUC. |
| [GetSerieEstadosFinancieros()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/020-GetSerieEstadosFinancieros.html) | Retorna el movimiento de una o varias cuentas en una serie de tiempo | Esta funciÃ³n es la encargada de retornar el movimiento de una o varias cuentas en una serie de tiempo.   La serie de tiempo puede ser: diaria, semanal, mensual, bimestral, trimestral, cuatrimestral, semestral o anual. De acuerdo a la serie de tiempo solicitada, la funciÃ³n retornarÃ¡ los movimientos de la cuenta discriminados por dÃ­a, semana, mes entre otros. |
| [GetCategoriasFavoritos()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/030-GetCategoriasFavoritos.html) | Retorna el listado de categorÃ­as en las que se pueden agrupar las cuentas favoritas de un usuario | Esta funciÃ³n es la encargada de retornar el listado de categorÃ­as en las cuales se pueden agrupar las cuentas favoritas del usuario, es decir, las cuentas que consulta frecuentemente. Una categorÃ­a puede ser por ejemplo âIngresosâ y asociar a ella las cuentas de ingresos que el usuario consulta constantemente y asÃ­ obtener los saldos de las cuentas de manera rÃ¡pida y efectiva |
| [SetCuentasFavoritas()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/040-SetCuentasFavoritas.html) | Registra las cuentas favoritas de un usuario en la base de datos | Esta funciÃ³n es la encargada de registrar las cuentas favoritas de un usuario en la base de datos. Se utiliza tanto para registrar cuentas nuevas como para modificar las cuentas ya asociadas a favoritos. |
| [GetSaldosFavoritosCuentas()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/050-GetSaldosFavoritosCuentas.html) | Retorna los saldos de las cuentas favoritas del usuario | Esta funciÃ³n es la encargada de retornar los saldos de las cuentas favoritas asociadas a una categorÃ­a. |
| [GetFIniInfoContable()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/060-GetFIniInfoContable.html) | Retorna la fecha de inicio de registro de la informaciÃ³n contable de la empresa | Esta funciÃ³n es la encargada de retornar la fecha de inicio de registro de la informaciÃ³n contable de la empresa a la que se encuentra conectado el usuario. |
| [GetSaldoCuentas()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/070-GetSaldoCuentas.html) | Retorna el saldo total de los movimientos contables realizados para un conjunto de cuentas. | FunciÃ³n que retorna el saldo total de los movimientos contables realizados para un conjunto de cuentas en centros de costos indicados opcionalmente. |
| [GetDetalleFicha()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/080-GetDetalleFicha.html) | Retorna los movimientos y saldos de un tercero discriminados por perÃ­odos | Esta funciÃ³n es la encargada de retornar la informaciÃ³n financiera de un tercero (movimientos y saldos) discriminada por periodos y de acuerdo a la cuenta que se envÃ­a por parÃ¡metro.   Esta funciÃ³n es utilizada en la consulta de los favoritos financieros de un tercero de la aplicaciÃ³n ContaPyme mÃ³vil. |
| [GetInfoMovimientosPorCuentas()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/090-GetInfoMovimientosPorCuentas.html) | Retorna la informaciÃ³n de las operaciones que generaron los movimientos de una cuenta | Esta funciÃ³n es la encargada de retornar la informaciÃ³n de las operaciones que generaron los movimientos de una cuenta. |
| [SetCategoriaFavorita()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/100-SetCategoriaFavorita.html) | Renombra una categorÃ­a favorita de estados financieros | Esta funciÃ³n es la encargada de renombrar una categorÃ­a de favoritos financieros en la base de datos. |
| [DoEliminarFavoritoCuentas()](020_CONTABILIDAD/010_Catalogos/010_Estados_financieros/110-DoEliminarFavoritoCuentas.html) | Elimina una cuenta favorita | Esta funciÃ³n es la encargada de eliminar una cuenta favorita, es decir la borra de las cuentas favoritas pero sigue como una cuenta del plan de cuentas. |
| [**Arribaâ**](#inicio) | | |

|  |  |  |
| --- | --- | --- |
| **CatÃ¡logo de conceptos de liquidaciÃ³n** | | |
| **Funciones relacionadas con conceptos de liquidaciÃ³n** | | |
| Nombre funciÃ³n | Nombre corto | DescripciÃ³n |
| [GetListaConceptosLiquidacion()](020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/010-GetListaConceptosLiquidacion.html) | Retorna el listado de los conceptos de liquidaciÃ³n registrados en el sistema. | Esta funciÃ³n es la encargada de retornar la informaciÃ³n de un listado de conceptos de liquidaciÃ³n en ingreso o egreso registrados en el sistema.   Si se requiere obtener sÃ³lo los conceptos de liquidaciÃ³n en ingreso, se debe enviar en "datosfiltro":{"itdconc":"I"}, si por el contrario se requiere obtener sÃ³lo los conceptos de liquidaciÃ³n en egreso, se debe enviar en "datosfiltro":{"itdconc":"E"}   Esta funciÃ³n retorna para cada concepto de liquidaciÃ³n la informaciÃ³n que sea solicitada. |
| [GetListaSeleccion()](020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/020-GetListaSeleccion.html) | Retorna un listado de conceptos de liquidaciÃ³n para ser presentados en un selector. | Esta funciÃ³n es la encargada de retornar un listado de conceptos de liquidaciÃ³n en ingreso o egreso registrados en el sistema, generalmente retorna de cada concepto el cÃ³digo y el nombre.   Si se requiere obtener sÃ³lo los conceptos de liquidaciÃ³n en ingreso, se debe enviar en "datosfiltro":{"itdconc":"I"}, si por el contrario se requiere obtener sÃ³lo los conceptos de liquidaciÃ³n en egreso, se debe enviar en "datosfiltro":{"itdconc":"E"}   Esta funciÃ³n se debe utilizar cuando se requiera mostrar un listado de conceptos de liquidaciÃ³n en ingreso o egreso en un selector, es decir, una lista de selecciÃ³n en la que el usuari |
| **DocumentaciÃ³n de apoyo** | | |
| Nombre documento | Tipo | DescripciÃ³n |
| [InfoBasica](020_CONTABILIDAD/010_Catalogos/020_CatConceptosLiquidacion/DocApoyo/PDFS/InfoBasica.pdf) | PDF | DocumentaciÃ³n de los campos de la informaciÃ³n bÃ¡sica de los conceptos de liquidaciÃ³n. |
| [**Arribaâ**](#inicio) | | |

|  |  |  |
| --- | --- | --- |
| **CatÃ¡logo de conceptos de flujos de efectivo** | | |
| **Funciones relacionadas con conceptos de flujos de efectivo** | | |
| Nombre funciÃ³n | Nombre corto | DescripciÃ³n |
| [GetListaFlujosEfectivo()](020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/010-GetListaFlujosEfectivo.html) | Retorna el listado de los conceptos de flujos de efectivo registrados en el sistema. | Esta funciÃ³n es la encargada de retornar la informaciÃ³n de un listado de conceptos de flujos de efectivo registrados en el sistema. RetornarÃ¡ para cada concepto la informaciÃ³n que sea solicitada.   Esta funciÃ³n se debe utilizar cuando se van mostrar los conceptos de flujos de efectivo a manera de catÃ¡logo. |
| [GetListaSeleccion()](020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/020-GetListaSeleccion.html) | Retorna un listado de conceptos de flujos de efectivo para ser presentados en un selector. | Esta funciÃ³n es la encargada de retornar un listado de conceptos de flujos de efectivo registrados en el sistema, generalmente retorna de cada concepto el cÃ³digo y el nombre.   Esta funciÃ³n se debe utilizar cuando se requiera mostrar un listado de conceptos de flujos de efectivo en un selector, es decir, una lista de selecciÃ³n en la que el usuario pueda elegir una opciÃ³n. |
| **DocumentaciÃ³n de apoyo** | | |
| Nombre documento | Tipo | DescripciÃ³n |
| [InfoBasica](020_CONTABILIDAD/010_Catalogos/030_CatFlujosEfectivo/DocApoyo/PDFS/InfoBasica.pdf) | PDF | DocumentaciÃ³n de los campos de la informaciÃ³n bÃ¡sica de los conceptos de flujos de efectivo. |
| [**Arribaâ**](#inicio) | | |

|  |  |  |
| --- | --- | --- |
| **CatÃ¡logo de monedas** | | |
| **Funciones relacionadas con monedas** | | |
| Nombre funciÃ³n | Nombre corto | DescripciÃ³n |
| [GetListaMonedas()](020_CONTABILIDAD/010_Catalogos/040_CatMonedas/010-GetListaMonedas.html) | Retorna el listado de monedas registradas en el sistema. | Esta funciÃ³n es la encargada de retornar la informaciÃ³n de un listado de monedas registradas en el sistema. RetornarÃ¡ para cada moneda la informaciÃ³n que sea solicitada.   Esta funciÃ³n se debe utilizar cuando se van mostrar las monedas a manera de catÃ¡logo. |
| [GetListaSeleccion()](020_CONTABILIDAD/010_Catalogos/040_CatMonedas/020-GetListaSeleccion.html) | Retorna un listado de monedas para ser presentadas en un selector. | Esta funciÃ³n es la encargada de retornar un listado de monedas registradas en el sistema, generalmente retorna de cada moneda cÃ³digo y el nombre.   Esta funciÃ³n se debe utilizar cuando se requiera mostrar un listado de monedas en un selector, es decir, una lista de selecciÃ³n en la que el usuario pueda elegir una opciÃ³n. |
| **DocumentaciÃ³n de apoyo** | | |
| Nombre documento | Tipo | DescripciÃ³n |
| [InfoBasica](020_CONTABILIDAD/010_Catalogos/040_CatMonedas/DocApoyo/PDFS/InfoBasica.pdf) | PDF | DocumentaciÃ³n de los campos de la informaciÃ³n bÃ¡sica del catÃ¡logo de monedas. |
| [**Arribaâ**](#inicio) | | |

|  |  |  |
| --- | --- | --- |
| **CatÃ¡logo de tipos de movimientos bancarios** | | |
| **Funciones relacionadas con tipos de movimientos bancarios** | | |
| Nombre funciÃ³n | Nombre corto | DescripciÃ³n |
| [GetListaTiposMovBancarios()](020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/010-GetListaTiposMovBancarios.html) | Retorna el listado de tipos de movimientos bancarios registrados en el sistema. | Esta funciÃ³n es la encargada de retornar la informaciÃ³n de un listado de tipos de movimientos bancarios registrados en el sistema. RetornarÃ¡ para cada tipo de movimiento la informaciÃ³n que sea solicitada.   Esta funciÃ³n se debe utilizar cuando se van mostrar los tipos de movimientos bancarios a manera de catÃ¡logo. |
| [GetListaSeleccion()](020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/020-GetListaSeleccion.html) | Retorna un listado de tipos de movimientos bancarios para ser presentados en un selector. | Esta funciÃ³n es la encargada de retornar un listado de tipos de movimientos bancarios registrados en el sistema, generalmente retorna de cada tipo de movimiento cÃ³digo y el nombre.   Esta funciÃ³n se debe utilizar cuando se requiera mostrar un listado de tipos de movimientos bancarios en un selector, es decir, una lista de selecciÃ³n en la que el usuario pueda elegir una opciÃ³n. |
| **DocumentaciÃ³n de apoyo** | | |
| Nombre documento | Tipo | DescripciÃ³n |
| [InfoBasica](020_CONTABILIDAD/010_Catalogos/050_CatTiposMovBancarios/DocApoyo/PDFS/InfoBasica.pdf) | PDF | DocumentaciÃ³n de los campos de la informaciÃ³n bÃ¡sica de los tipos de movimientos bancarios. |
| [**Arribaâ**](#inicio) | | |

|  |  |  |
| --- | --- | --- |
| **Operaciones** | | |
| **Operaciones de contabilidad** | | |
| Nombre operaciÃ³n | Nombre corto | DescripciÃ³n |
| [DocJsonOprMov1()](020_CONTABILIDAD/020_Operaciones/PDFS/010_DocJsonOprMOV1.pdf) | DocumentaciÃ³n del JSon de la operaciÃ³n de Movimiento contable. | DocumentaciÃ³n del Json que se debe enviar para insertar una operaciÃ³n de Movimiento contable en el sistema a travÃ©s del Agente. |
| **DocumentaciÃ³n de apoyo** | | |
| Nombre documento | Tipo | DescripciÃ³n |
| [Modelo ER OprMaest](040_INVENTARIOS/020_Operaciones/010_DocApoyo/PDFS/OPRMAEST.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan la informaciÃ³n de encabezado de las operaciones. |
| [Modelo ER OprMov1\_Base](020_CONTABILIDAD/020_Operaciones/010_DocApoyo/PDFS/OPRMOV1_BASE.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan la informaciÃ³n bÃ¡sica de una operaciÃ³n de movimiento contable. |
| [Modelo ER OprMov1\_Detalle](020_CONTABILIDAD/020_Operaciones/010_DocApoyo/PDFS/OPRMOV1_DETALLE.pdf) | PDF | DocumentaciÃ³n de los campos que almacenan la informaciÃ³n de cada uno de los movimientos registrados en una operaciÃ³n de movimiento contable. |
| [**Arribaâ**](#inicio) | | |

|  |  |  |
| --- | --- | --- |
| **DocumentaciÃ³n de apoyo** | | |
| Nombre funciÃ³n | Nombre corto | DescripciÃ³n |
| [GetPDF()](010_MOD_BASICO/030_Informes/010-GetPDF.html) | Retorna el PDF de cualquier reporte disponible en el sistema. | Esta funciÃ³n es la encargada de retornar en formato PDF cualquier reporte disponible en el sistema.   Para llamar un reporte se debe definir en el parÃ¡metro âdatajsonâ los siguientes elementos:  - **keyaction:** CÃ³digo identificador del reporte en el sistema. - **xmlparams:** parÃ¡metros que se deben enviar al reporte para poderse generar, estos parÃ¡metros debe ir en formato XML.  Para conocer el keyaction y el xmlparams de cada reporte se debe consultar la documentaciÃ³n correspondiente al reporte en la secciÃ³n âInformesâ de cada mÃ³dulo |

|  |  |  |
| --- | --- | --- |
| **Usted no tiene permisos para acceder a la documentaciÃ³n de la API del Agente de servicios web.** | | |
