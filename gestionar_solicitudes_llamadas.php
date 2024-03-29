<?php require_once "includes/conexion.php";
PermitirAcceso(334);
$sw = 0;

// Estado llamada
$SQL_EstadoLlamada = Seleccionar('uvw_tbl_EstadoLlamada', '*');

// Estado servicio de la Solicitud de Llamada de servicio. SMM, 29/08/2023
$SQL_EstServLlamada = Seleccionar('tbl_SolicitudLlamadasServiciosEstadoServicios', '*');

// Tipo de problema llamada
$SQL_TipoProblema = Seleccionar('uvw_Sap_tbl_TipoProblemasLlamadas', '*', '', 'DeTipoProblemaLlamada');

// Serie de llamada
$ParamSerie = array(
    "'" . $_SESSION['CodUser'] . "'",
    "'191'",
    1,
);
$SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

// Fechas
if (isset($_GET['FechaInicial']) && $_GET['FechaInicial'] != "") {
    $FechaInicial = $_GET['FechaInicial'];
    $sw = 1;
} else {
    // Restar 7 dias a la fecha actual
    $fecha = date('Y-m-d');
    $nuevafecha = strtotime('-' . ObtenerVariable("DiasRangoFechasDocSAP") . ' day');
    $nuevafecha = date('Y-m-d', $nuevafecha);
    $FechaInicial = $nuevafecha;
}
if (isset($_GET['FechaFinal']) && $_GET['FechaFinal'] != "") {
    $FechaFinal = $_GET['FechaFinal'];
    $sw = 1;
} else {
    $FechaFinal = date('Y-m-d');
}

// Filtros
$Filtro = "";
if (isset($_GET['EstadoLlamada']) && $_GET['EstadoLlamada'] != "") {
    $Filtro .= " and [IdEstadoLlamada]='" . $_GET['EstadoLlamada'] . "'";
    $sw = 1;
}

// Cliente
if (isset($_GET['Cliente'])) {
    if ($_GET['Cliente'] != "") { // Si se selecciono el cliente
        $Filtro .= " and ID_CodigoCliente='" . $_GET['Cliente'] . "'";
        $sw_suc = 1; // Cuando se ha seleccionado una sucursal
        if (isset($_GET['Sucursal'])) {
            if ($_GET['Sucursal'] == "") {
                // Sucursales
                if (PermitirFuncion(205)) {
                    $Where = "CodigoCliente='" . $_GET['Cliente'] . "'";
                    $SQL_Sucursal = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "NombreSucursal", $Where);
                } else {
                    $Where = "CodigoCliente='" . $_GET['Cliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
                    $SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);
                }
                $j = 0;
                unset($WhereSuc);
                $WhereSuc = array();
                while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {
                    $WhereSuc[$j] = "NombreSucursal='" . $row_Sucursal['NombreSucursal'] . "'";
                    $j++;
                }
                $FiltroSuc = implode(" OR ", $WhereSuc);
                $Filtro .= " and (" . $FiltroSuc . ")";
            } else {
                $Filtro .= " and NombreSucursal='" . $_GET['Sucursal'] . "'";
            }
        }

    } else {
        if (!PermitirFuncion(205)) {
            $Where = "ID_Usuario = " . $_SESSION['CodUser'];
            $SQL_Cliente = Seleccionar("uvw_tbl_ClienteUsuario", "CodigoCliente, NombreCliente", $Where);
            $k = 0;
            while ($row_Cliente = sqlsrv_fetch_array($SQL_Cliente)) {

                // Sucursales
                $Where = "CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
                $SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);

                $j = 0;
                unset($WhereSuc);
                $WhereSuc = array();
                while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {
                    $WhereSuc[$j] = "NombreSucursal='" . $row_Sucursal['NombreSucursal'] . "'";
                    $j++;
                }

                $FiltroSuc = implode(" OR ", $WhereSuc);

                if ($k == 0) {
                    $Filtro .= " AND (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
                } else {
                    $Filtro .= " OR (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
                }

                $k++;
            }
        }
    }
} else {
    if (!PermitirFuncion(205)) {
        $Where = "ID_Usuario = " . $_SESSION['CodUser'];
        $SQL_Cliente = Seleccionar("uvw_tbl_ClienteUsuario", "CodigoCliente, NombreCliente", $Where);
        $k = 0;
        while ($row_Cliente = sqlsrv_fetch_array($SQL_Cliente)) {

            // Sucursales
            $Where = "CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
            $SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);

            $j = 0;
            unset($WhereSuc);
            $WhereSuc = array();
            while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) {
                $WhereSuc[$j] = "NombreSucursal='" . $row_Sucursal['NombreSucursal'] . "'";
                $j++;
            }

            $FiltroSuc = implode(" OR ", $WhereSuc);

            if ($k == 0) {
                $Filtro .= " AND (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
            } else {
                $Filtro .= " OR (ID_CodigoCliente='" . $row_Cliente['CodigoCliente'] . "' AND (" . $FiltroSuc . "))";
            }

            $k++;
        }
    }
}

if (isset($_GET['Series']) && $_GET['Series'] != "") {
    $Filtro .= " AND [IdSeries]='" . $_GET['Series'] . "'";
    $sw = 1;
} else {
    $FilSerie = "";
    $i = 0;
    while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {
        if ($i == 0) {
            $FilSerie .= "'" . $row_Series['IdSeries'] . "'";
        } else {
            $FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
        }
        $i++;
    }
    $Filtro .= " AND [IdSeries] IN ($FilSerie)";
    $SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);
}

if (isset($_GET['TipoProblema']) && $_GET['TipoProblema'] != "") {
    $Filtro .= " and [IdTipoProblemaLlamada]='" . $_GET['TipoProblema'] . "'";
    $sw = 1;
}

// SMM, 13/10/2023
if (isset($_GET['Tecnico']) && $_GET['Tecnico'] != "") {
    $Filtro .= " AND [IdTecnico]='" . $_GET['Tecnico'] . "'";
    $sw = 1;
}

if (isset($_GET['EstadoServicio']) && $_GET['EstadoServicio'] != "") {
    $Filtro .= " and [CDU_EstadoServicio]='" . $_GET['EstadoServicio'] . "'";
    $sw = 1;
}

if (isset($_GET['BuscarDato']) && $_GET['BuscarDato'] != "") {
    // Stiven Muñoz Murillo, 26/01/2022
    $BuscarDato = $_GET['BuscarDato'];
    $Filtro .= " AND ([DocNum] LIKE '%$BuscarDato%' OR [IdNumeroSerie] LIKE '%$BuscarDato%' OR [TelefonoContactoLlamada] LIKE '%$BuscarDato%' OR [CorreoContactoLlamada] LIKE '%$BuscarDato%' OR [AsuntoLlamada] LIKE '%$BuscarDato%' OR [ComentarioLlamada] LIKE '%$BuscarDato%' OR [ResolucionLlamada] LIKE '%$BuscarDato%' OR [DeTipoLlamada] LIKE '%$BuscarDato%' OR [NombreClienteLlamada] LIKE '%$BuscarDato%')";
    $sw = 1;
}

if ($sw == 1) {
    $Cons = "SELECT * FROM [uvw_tbl_SolicitudLlamadasServicios] WHERE (FechaCreacionLLamada BETWEEN '$FechaInicial' AND '$FechaFinal') $Filtro";
    $SQL = sqlsrv_query($conexion, $Cons);
    // echo "sw == 1";
} else {
    $Cons = "";
    $SQL = sqlsrv_query($conexion, $Cons);
    // echo "sw != 1";
}

// SMM, 08/08/2023
// echo "<br>$Cons";

if (isset($_GET['IDTicket']) && $_GET['IDTicket'] != "") {
    $Where = "ID_SolicitudLlamadaServicio LIKE '%" . trim($_GET['IDTicket']) . "%'";

    $FilSerie = "";
    $i = 0;
    while ($row_Series = sqlsrv_fetch_array($SQL_Series)) {
        if ($i == 0) {
            $FilSerie .= "'" . $row_Series['IdSeries'] . "'";
        } else {
            $FilSerie .= ",'" . $row_Series['IdSeries'] . "'";
        }
        $i++;
    }
    $Where .= " AND [IdSeries] IN ($FilSerie)";
    $SQL_Series = EjecutarSP('sp_ConsultarSeriesDocumentos', $ParamSerie);

    $SQL = Seleccionar('uvw_tbl_SolicitudLlamadasServicios', '*', $Where);
}

?>
<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
    <?php include_once "includes/cabecera.php"; ?>
    <!-- InstanceBeginEditable name="doctitle" -->
    <title>Solicitudes de Llamadas de servicio (Agenda) |
        <?php echo NOMBRE_PORTAL; ?>
    </title>
    <!-- InstanceEndEditable -->
    <!-- InstanceBeginEditable name="head" -->
    <?php
    if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_OTSolAdd"))) {
        echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La Solicitud de Llamada de servicio ha sido creada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
    }
    if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_OTSolUpd"))) {
        echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'La Solicitud de Llamada de servicio ha sido actualizada exitosamente.',
                icon: 'success'
            });
		});
		</script>";
    }
    ?>

    <script type="text/javascript">
        $(document).ready(function () {
            $("#NombreCliente").change(function () {
                var NomCliente = document.getElementById("NombreCliente");
                var Cliente = document.getElementById("Cliente");
                if (NomCliente.value == "") {
                    Cliente.value = "";
                    $("#Cliente").trigger("change");
                }
            });
            $("#Cliente").change(function () {
                var Cliente = document.getElementById("Cliente");
                $.ajax({
                    type: "POST",
                    url: "ajx_cbo_sucursales_clientes_simple.php?CardCode=" + Cliente.value,
                    success: function (response) {
                        $('#Sucursal').html(response).fadeIn();
                    }
                });
            });
        });
    </script>
    <!-- InstanceEndEditable -->
</head>

<body>

    <div id="wrapper">

        <?php include_once "includes/menu.php"; ?>

        <div id="page-wrapper" class="gray-bg">
            <?php include_once "includes/menu_superior.php"; ?>
            <!-- InstanceBeginEditable name="Contenido" -->
            <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <h2>Solicitudes de Llamadas de servicio (Agenda)</h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index1.php">Inicio</a>
                        </li>
                        <li>
                            <a href="#">Servicios</a>
                        </li>
                        <li class="active">
                            <strong>Solicitudes de Llamadas de servicio (Agenda)</strong>
                        </li>
                    </ol>
                </div>
                
                <?php if (PermitirFuncion(335)) { ?>
                    <div class="col-sm-4">
                        <div class="title-action">
                            <a href="solicitud_llamada.php" class="alkin btn btn-primary"><i class="fa fa-plus-circle"></i>
                                Crear Solicitud de Llamada (Agenda)</a>
                        </div>
                    </div>
                <?php } ?>
            </div>
            <div class="wrapper wrapper-content">
                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox-content">
                            <?php include "includes/spinner.php"; ?>
                            <form action="gestionar_solicitudes_llamadas.php" method="get" id="formBuscar"
                                class="form-horizontal">
                                <div class="form-group">
                                    <label class="col-xs-12">
                                        <h3 class="bg-success p-xs b-r-sm"><i class="fa fa-filter"></i> Datos para
                                            filtrar</h3>
                                    </label>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-1 control-label">Fechas</label>
                                    <div class="col-lg-3">
                                        <div class="input-daterange input-group" id="datepicker">
                                            <input name="FechaInicial" autocomplete="off" type="text"
                                                class="input-sm form-control" id="FechaInicial"
                                                placeholder="Fecha inicial" value="<?php echo $FechaInicial; ?>" />
                                            <span class="input-group-addon">hasta</span>
                                            <input name="FechaFinal" autocomplete="off" type="text"
                                                class="input-sm form-control" id="FechaFinal" placeholder="Fecha final"
                                                value="<?php echo $FechaFinal; ?>" />
                                        </div>
                                    </div>
                                    <label class="col-lg-1 control-label">Estado</label>
                                    <div class="col-lg-3">
                                        <select name="EstadoLlamada" class="form-control" id="EstadoLlamada">
                                            <option value="">(Todos)</option>
                                            <?php while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) { ?>
                                                <option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($_GET['EstadoLlamada'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $_GET['EstadoLlamada']) == 0)) {
                                                       echo "selected=\"selected\"";
                                                   } ?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                    <label class="col-lg-1 control-label">Serie</label>
                                    <div class="col-lg-2">
                                        <select name="Series" class="form-control" id="Series">
                                            <option value="">(Todos)</option>
                                            <?php while ($row_Series = sqlsrv_fetch_array($SQL_Series)) { ?>
                                                <option value="<?php echo $row_Series['IdSeries']; ?>" <?php if ((isset($_GET['Series'])) && (strcmp($row_Series['IdSeries'], $_GET['Series']) == 0)) {
                                                       echo "selected=\"selected\"";
                                                   } ?>><?php echo $row_Series['DeSeries']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="form-group">
                                    <label class="col-lg-1 control-label">Cliente</label>
                                    <div class="col-lg-3">
                                        <input name="Cliente" type="hidden" id="Cliente" value="<?php if (isset($_GET['Cliente']) && ($_GET['Cliente'] != "")) {
                                            echo $_GET['Cliente'];
                                        } ?>">
                                        <input name="NombreCliente" type="text" class="form-control" id="NombreCliente"
                                            placeholder="Para TODOS, dejar vacio..." value="<?php if (isset($_GET['NombreCliente']) && ($_GET['NombreCliente'] != "")) {
                                                echo $_GET['NombreCliente'];
                                            } ?>">
                                    </div>

                                    <label class="col-lg-1 control-label">Sucursal</label>
                                    <div class="col-lg-3">
                                        <select id="Sucursal" name="Sucursal" class="form-control">
                                            <option value="">(Todos)</option>
                                            <?php
                                            if ($sw_suc == 1) { //Cuando se ha seleccionado una opción
                                                if (PermitirFuncion(205)) {
                                                    $Where = "CodigoCliente='" . $_GET['Cliente'] . "'";
                                                    $SQL_Sucursal = Seleccionar("uvw_Sap_tbl_Clientes_Sucursales", "NombreSucursal", $Where);
                                                } else {
                                                    $Where = "CodigoCliente='" . $_GET['Cliente'] . "' and ID_Usuario = " . $_SESSION['CodUser'];
                                                    $SQL_Sucursal = Seleccionar("uvw_tbl_SucursalesClienteUsuario", "NombreSucursal", $Where);
                                                }
                                                while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) { ?>
                                                    <option value="<?php echo $row_Sucursal['NombreSucursal']; ?>" <?php if (strcmp($row_Sucursal['NombreSucursal'], $_GET['Sucursal']) == 0) {
                                                           echo "selected=\"selected\"";
                                                       } ?>><?php echo $row_Sucursal['NombreSucursal']; ?></option>
                                                <?php }
                                            } elseif ($sw_suc == 2) { //Cuando no se ha seleccionado todavia, al entrar a la pagina
                                                while ($row_Sucursal = sqlsrv_fetch_array($SQL_Sucursal)) { ?>
                                                    <option value="<?php echo $row_Sucursal['NombreSucursal']; ?>"><?php echo $row_Sucursal['NombreSucursal']; ?></option>
                                                <?php }
                                            } ?>
                                        </select>
                                    </div>

                                    <label class="col-lg-1 control-label">Buscar dato</label>
                                    <div class="col-lg-3">
                                        <input name="BuscarDato" type="text" class="form-control" id="BuscarDato"
                                            maxlength="100" value="<?php if (isset($_GET['BuscarDato']) && ($_GET['BuscarDato'] != "")) {
                                                echo $_GET['BuscarDato'];
                                            } ?>">
                                    </div>
                                </div>

                                <div class="form-group">
                                    <label class="col-lg-1 control-label">Técnico/Asesor</label>
                                    <div class="col-lg-3">
                                        <input name="Tecnico" type="hidden" id="Tecnico" value="<?php echo $_GET['Tecnico'] ?? ""; ?>">
                                        <input name="NombreTecnico" type="text" class="form-control" id="NombreTecnico"
                                            placeholder="Para TODOS, dejar vacio..." value="<?php echo $_GET['NombreTecnico'] ?? ""; ?>">
                                    </div>                                    

                                    <label class="col-lg-1 control-label">Estado servicio</label>
                                    <div class="col-lg-3">
                                        <select name="EstadoServicio" class="form-control" id="EstadoServicio">
                                            <option value="">(Todos)</option>
                                            <?php while ($row_EstServLlamada = sqlsrv_fetch_array($SQL_EstServLlamada)) { ?>
                                                <option value="<?php echo $row_EstServLlamada['id_tipo_estado_servicio_sol_llamada']; ?>" <?php if ((isset($_GET['EstadoServicio'])) && (strcmp($row_EstServLlamada['id_tipo_estado_servicio_sol_llamada'], $_GET['EstadoServicio']) == 0)) {
                                                       echo "selected";
                                                   } ?>><?php echo $row_EstServLlamada['tipo_estado_servicio_sol_llamada']; ?></option>
                                            <?php } ?>
                                        </select>
                                    </div>

                                    <label class="col-lg-1 control-label">ID Agenda</label>
                                    <div class="col-lg-2">
                                        <input name="IDTicket" type="text" class="form-control" id="IDTicket"
                                            maxlength="50"
                                            placeholder="Digite un número completo, o una parte del mismo..." value="<?php if (isset($_GET['IDTicket']) && ($_GET['IDTicket'] != "")) {
                                                echo $_GET['IDTicket'];
                                            } ?>">
                                    </div>

                                    <div class="col-lg-1 pull-right">
                                        <button type="submit" class="btn btn-outline btn-success pull-right"><i
                                                class="fa fa-search"></i> Buscar</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <br>
                <?php //echo $Cons;?>
                <div class="row">
                    <div class="col-lg-12">
                        <div class="ibox-content">
                            <?php include "includes/spinner.php"; ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-bordered table-hover dataTables-example">
                                    <thead>
                                        <tr>
                                            <th>ID</th>

                                            <th>Estado</th>
                                            <th>Acciones</th>
                                            <th>Estado servicio</th>

                                            <th>Técnico/Asesor</th> <!-- SMM, 14/09/2022 -->
                                            <th>Cargo Técnico/Asesor</th> <!-- SMM, 14/09/2022 -->

                                            <th>Asunto</th>
                                            <th>Tipo problema</th>

                                            <th>Subtipo problema</th>

                                            <th>Tipo llamada</th>
                                            <th>Cliente</th>
                                            <th>Sucursal</th>

                                            <th>Marca</th> <!-- SMM, 21/09/2022 -->
                                            <th>Línea</th> <!-- SMM, 21/09/2022 -->

                                            <th>Serial Interno</th>
                                            <th>Fecha creación</th>

                                            <th>Documento destino</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
                                            <tr class="gradeX">
                                                <td>
                                                    <?php echo $row['ID_SolicitudLlamadaServicio']; ?>
                                                </td>

                                                <td>
                                                    <span
                                                        class="label <?php if ($row['IdEstadoLlamada'] == -3) {
                                                            echo "label-primary";
                                                        } else {
                                                            echo "label-danger";
                                                        } ?>"><?php echo $row['NombreEstado'] ?? ""; ?></span>
                                                </td>

                                                <td>
                                                    <a href="solicitud_llamada.php?id=<?php echo base64_encode($row['ID_SolicitudLlamadaServicio']); ?>&tl=1&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('gestionar_solicitudes_llamadas.php'); ?>"
                                                        class="alkin btn btn-success btn-xs"><i
                                                            class="fa fa-folder-open-o"></i> Abrir</a>
                                                    <a href="sapdownload.php?id=<?php echo base64_encode('15'); ?>&type=<?php echo base64_encode('2'); ?>&DocKey=<?php echo base64_encode($row['ID_SolicitudLlamadaServicio']); ?>&ObType=<?php echo base64_encode('20008'); ?>&IdFrm=<?php echo base64_encode(0); ?>"
                                                        target="_blank" class="btn btn-warning btn-xs"><i
                                                            class="fa fa-download"></i> Descargar</a>
                                                </td>

                                                <td>
                                                    <span class="label" style="color: white; background-color: <?php echo $row['ColorEstadoServicioLlamada']; ?>;">
                                                        <?php echo $row['DeEstadoServicio']; ?>
                                                    </span>
                                                </td>

                                                <td>
                                                    <?php echo $row['NombreTecnicoAsesor'] ?? ""; ?>
                                                </td>
                                                <td>
                                                    <?php echo $row['CargoTecnicoAsesor'] ?? ""; ?>
                                                </td>
                                                <td>
                                                    <?php echo $row['AsuntoLlamada']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $row['DeTipoProblemaLlamada']; ?>
                                                </td>

                                                <td>
                                                    <?php echo $row['DeSubTipoProblemaLlamada']; ?>
                                                </td>

                                                <td>
                                                    <?php echo $row['DeTipoLlamada']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $row['NombreClienteLlamada']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $row['NombreSucursal']; ?>
                                                </td>

                                                <td>
                                                    <?php echo $row['DeMarcaVehiculo'] ?? ""; ?>
                                                </td>
                                                <td>
                                                    <?php echo $row['DeLineaModeloVehiculo'] ?? ""; ?>
                                                </td>

                                                <td>
                                                    <?php echo $row['IdNumeroSerie']; ?>
                                                </td>
                                                <td>
                                                    <?php echo $row['FechaHoraCreacionLLamada']->format('Y-m-d H:i'); ?>
                                                </td>

                                                <td>
							                        <?php if ($row['DocDestinoDocEntry'] != "") {?><a href="llamada_servicio.php?id=<?php echo base64_encode($row['DocDestinoDocEntry']); ?>&return=<?php echo base64_encode($_SERVER['QUERY_STRING']); ?>&pag=<?php echo base64_encode('gestionar_solicitudes_llamadas.php'); ?>&tl=1" target="_blank"><?php echo $row['DocDestinoDocNum']; ?></a><?php } else {echo "--";}?>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- InstanceEndEditable -->
            <?php include_once "includes/footer.php"; ?>

        </div>
    </div>
    <?php include_once "includes/pie.php"; ?>
    <!-- InstanceBeginEditable name="EditRegion4" -->
    <script>
        $(document).ready(function () {
            $("#formBuscar").validate({
                submitHandler: function (form) {
                    $('.ibox-content').toggleClass('sk-loading');
                    form.submit();
                }
            });

            $(".alkin").on('click', function () {
                $('.ibox-content').toggleClass('sk-loading');
            });

            $('#FechaInicial').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
                todayHighlight: true,
                format: 'yyyy-mm-dd'
            });

            $('#FechaFinal').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
                todayHighlight: true,
                format: 'yyyy-mm-dd'
            });

            $('.chosen-select').chosen({ width: "100%" });

            let options = {
                url: function (phrase) {
                    return `ajx_buscar_datos_json.php?type=7&id=${phrase}`;
                },
                getValue: "NombreBuscarCliente",
                requestDelay: 400,
                list: {
                    match: {
                        enabled: true
                    },
                    onClickEvent: function () {
                        let value = $("#NombreCliente").getSelectedItemData().CodigoCliente;
                        $("#Cliente").val(value).trigger("change");
                    }
                }
            };
            $("#NombreCliente").easyAutocomplete(options);

            let options2 = {
                url: function (phrase) {
                    return `ajx_buscar_datos_json.php?type=49&id=${phrase}`;
                },
                getValue: "NombreBuscarEmpleado",
                requestDelay: 400,
                list: {
                    match: {
                        enabled: true
                    },
                    onClickEvent: function () {
                        let value = $("#NombreTecnico").getSelectedItemData().ID_Empleado;
                        $("#Tecnico").val(value).trigger("change");
                    }
                }
            };
            $("#NombreTecnico").easyAutocomplete(options2);

            $('.dataTables-example').DataTable({
                pageLength: 25,
                order: [[0, "desc"]],
                dom: '<"html5buttons"B>lTfgitp',
                language: {
                    "decimal": "",
                    "emptyTable": "No se encontraron resultados.",
                    "info": "Mostrando _START_ - _END_ de _TOTAL_ registros",
                    "infoEmpty": "Mostrando 0 - 0 de 0 registros",
                    "infoFiltered": "(filtrando de _MAX_ registros)",
                    "infoPostFix": "",
                    "thousands": ",",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "loadingRecords": "Cargando...",
                    "processing": "Procesando...",
                    "search": "Filtrar:",
                    "zeroRecords": "Ningún registro encontrado",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "aria": {
                        "sortAscending": ": Activar para ordenar la columna ascendente",
                        "sortDescending": ": Activar para ordenar la columna descendente"
                    }
                },
                buttons: []

            });

        });

    </script>
    <!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>