<?php require_once "includes/conexion.php";
PermitirAcceso(106);
$IdFrm = "";
$msg_error = ""; //Mensaje del error
$dt_LS = 0; //sw para saber si vienen datos del SN. 0 no vienen. 1 si vienen.

//Nombre del formulario
if (isset($_REQUEST['frm']) && ($_REQUEST['frm'] != "")) {
    $frm = $_REQUEST['frm'];

    // Stiven Muñoz Murillo, 10/01/2022
    $SQL_Cat = Seleccionar("uvw_tbl_Categorias", "ID_Categoria, NombreCategoria, NombreCategoriaPadre, URL", "ID_Categoria = '" . base64_decode($frm) . "'");
}

// Stiven Muñoz Murillo, 10/01/2022
$row_Cat = isset($SQL_Cat) ? sqlsrv_fetch_array($SQL_Cat) : [];

if (isset($_GET['id']) && ($_GET['id'] != "")) {
    $IdFrm = base64_decode($_GET['id']);
}

if (isset($_GET['tl']) && ($_GET['tl'] != "")) { //0 Creando el formulario. 1 Editando el formulario.
    $type_frm = $_GET['tl'];
} elseif (isset($_POST['tl']) && ($_POST['tl'] != "")) {
    $type_frm = $_POST['tl'];
} else {
    $type_frm = 0;
}

if (isset($_GET['dt_LS']) && ($_GET['dt_LS']) == 1) { //Verificar que viene de una Llamada de servicio
    $dt_LS = 1;

    //Clientes
    $SQL_Cliente = Seleccionar('uvw_Sap_tbl_Clientes', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreCliente');
    $row_Cliente = sqlsrv_fetch_array($SQL_Cliente);

    //Contacto cliente
    $SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreContacto');

    //Sucursal cliente
    $SQL_SucursalCliente = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreSucursal');

    //Orden de servicio
    $SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_LlamadaServicio='" . base64_decode($_GET['LS']) . "'");
}

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
    $sw_error = $_POST['swError'];
} else {
    $sw_error = 0;
}

if ($type_frm == 0) {
    $Title = "Crear nueva Recepción de vehículo";
} else {
    $Title = "Editar Recepción de vehículo";
}

$dir = CrearObtenerDirTemp();
$dir_firma = CrearObtenerDirTempFirma();
$dir_new = CrearObtenerDirAnx("formularios");

if (isset($_POST['P']) && ($_POST['P'] == base64_encode('MM_frmHallazgos'))) {

    //Insertar formulario
    try {
        //*** Foto evidencia ***

        $ImgEvidencia1 = "NULL";
        $ImgEvidencia2 = "NULL";
        $ImgEvidencia3 = "NULL";
        $ImgCierre = "NULL";
        $NombreFirmaCliente = "NULL";
        $NombreFirmaTecnico = "NULL";

        if ($_FILES['ImgEvidencia1']['tmp_name'] != "") {
            if (is_uploaded_file($_FILES['ImgEvidencia1']['tmp_name'])) {
                $Nombre_Archivo = $_FILES['ImgEvidencia1']['name'];
                //Sacar la extension del archivo
                $Ext = end(explode('.', $Nombre_Archivo));
                //Sacar el nombre sin la extension
                $OnlyName = substr($Nombre_Archivo, 0, strlen($Nombre_Archivo) - (strlen($Ext) + 1));
                //Reemplazar espacios
                $OnlyName = str_replace(" ", "_", $OnlyName);
                $Prefijo = substr(uniqid(rand()), 0, 3);
                $ImgEvidencia1 = LSiqmlObs($OnlyName) . "_" . date('Ymd') . $Prefijo . "." . $Ext;
                move_uploaded_file($_FILES['ImgEvidencia1']['tmp_name'], $dir_new . $ImgEvidencia1);
                if (!RedimensionarImagen($ImgEvidencia1, $dir_new . $ImgEvidencia1, 300, 400)) {
                    $sw_error = 1;
                    $msg_error = "Error al redimensionar el archivo Evidencia 1";
                }
                $ImgEvidencia1 = "'" . $ImgEvidencia1 . "'";
            } else {
                throw new Exception('No se pudo cargar el archivo Evidencia 1');
                sqlsrv_close($conexion);
            }
        }

        if ($_FILES['ImgEvidencia2']['tmp_name'] != "") {
            if (is_uploaded_file($_FILES['ImgEvidencia2']['tmp_name'])) {
                $Nombre_Archivo = $_FILES['ImgEvidencia2']['name'];
                //Sacar la extension del archivo
                $Ext = end(explode('.', $Nombre_Archivo));
                //Sacar el nombre sin la extension
                $OnlyName = substr($Nombre_Archivo, 0, strlen($Nombre_Archivo) - (strlen($Ext) + 1));
                //Reemplazar espacios
                $OnlyName = str_replace(" ", "_", $OnlyName);
                $Prefijo = substr(uniqid(rand()), 0, 3);
                $ImgEvidencia2 = LSiqmlObs($OnlyName) . "_" . date('Ymd') . $Prefijo . "." . $Ext;
                move_uploaded_file($_FILES['ImgEvidencia2']['tmp_name'], $dir_new . $ImgEvidencia2);
                if (!RedimensionarImagen($ImgEvidencia2, $dir_new . $ImgEvidencia2, 300, 400)) {
                    $sw_error = 1;
                    $msg_error = "Error al redimensionar el archivo Evidencia 2";
                }
                $ImgEvidencia2 = "'" . $ImgEvidencia2 . "'";
            } else {
                throw new Exception('No se pudo cargar el archivo Evidencia 2');
                sqlsrv_close($conexion);
            }
        }

        if ($_FILES['ImgEvidencia3']['tmp_name'] != "") {
            if (is_uploaded_file($_FILES['ImgEvidencia3']['tmp_name'])) {
                $Nombre_Archivo = $_FILES['ImgEvidencia3']['name'];
                //Sacar la extension del archivo
                $Ext = end(explode('.', $Nombre_Archivo));
                //Sacar el nombre sin la extension
                $OnlyName = substr($Nombre_Archivo, 0, strlen($Nombre_Archivo) - (strlen($Ext) + 1));
                //Reemplazar espacios
                $OnlyName = str_replace(" ", "_", $OnlyName);
                $Prefijo = substr(uniqid(rand()), 0, 3);
                $ImgEvidencia3 = LSiqmlObs($OnlyName) . "_" . date('Ymd') . $Prefijo . "." . $Ext;
                move_uploaded_file($_FILES['ImgEvidencia3']['tmp_name'], $dir_new . $ImgEvidencia3);
                if (!RedimensionarImagen($ImgEvidencia3, $dir_new . $ImgEvidencia3, 300, 400)) {
                    $sw_error = 1;
                    $msg_error = "Error al redimensionar el archivo Evidencia 2";
                }
                $ImgEvidencia3 = "'" . $ImgEvidencia3 . "'";
            } else {
                throw new Exception('No se pudo cargar el archivo Evidencia 3');
                sqlsrv_close($conexion);
            }
        }

        if ($_FILES['ImgCierre']['tmp_name'] != "") {
            if (is_uploaded_file($_FILES['ImgCierre']['tmp_name'])) {
                $Nombre_Archivo = $_FILES['ImgCierre']['name'];
                //Sacar la extension del archivo
                $Ext = end(explode('.', $Nombre_Archivo));
                //Sacar el nombre sin la extension
                $OnlyName = substr($Nombre_Archivo, 0, strlen($Nombre_Archivo) - (strlen($Ext) + 1));
                //Reemplazar espacios
                $OnlyName = str_replace(" ", "_", $OnlyName);
                $Prefijo = substr(uniqid(rand()), 0, 3);
                $ImgCierre = LSiqmlObs($OnlyName) . "_" . date('Ymd') . $Prefijo . "." . $Ext;
                move_uploaded_file($_FILES['ImgCierre']['tmp_name'], $dir_new . $ImgCierre);
                if (!RedimensionarImagen($ImgCierre, $dir_new . $ImgCierre, 300, 400)) {
                    $sw_error = 1;
                    $msg_error = "Error al redimensionar el archivo Evidencia cierre";
                }
                $ImgCierre = "'" . $ImgCierre . "'";
            } else {
                throw new Exception('No se pudo cargar el archivo Evidencia cierre');
                sqlsrv_close($conexion);
            }
        }

        //Firmas
        if ($_POST['SigCliente'] != "") {
            $NombreFirmaCliente = base64_decode($_POST['SigCliente']);
            if (copy($dir_firma . $NombreFirmaCliente, $dir_new . $NombreFirmaCliente)) {
                RedimensionarImagen($NombreFirmaCliente, $dir_new . $NombreFirmaCliente, 300, 300);
                $NombreFirmaCliente = "'" . $NombreFirmaCliente . "'";
            } else {
                $NombreFirmaCliente = "NULL";
                $sw_error = 1;
                $msg_error = "No se pudo mover la firma del cliente";
            }
        }

        if ($_POST['SigTecnico'] != "") {
            $NombreFirmaTecnico = base64_decode($_POST['SigTecnico']);
            if (copy($dir_firma . $NombreFirmaTecnico, $dir_new . $NombreFirmaTecnico)) {
                RedimensionarImagen($NombreFirmaTecnico, $dir_new . $NombreFirmaTecnico, 300, 300);
                $NombreFirmaTecnico = "'" . $NombreFirmaTecnico . "'";
            } else {
                $NombreFirmaTecnico = "NULL";
                $sw_error = 1;
                $msg_error = "No se pudo mover la firma del tecnico";
            }
        }

        //Insertar el registro en la BD
        if ($_POST['tl'] == 0) { //Insertando
            $Type = 1;
            $ID = "NULL";
        } else { //Actualizando
            $Type = 2;
            $ID = base64_decode($_POST['IdFrm']);
        }

        $ParamInsFrm = array(
            $ID,
            "'" . $_POST['Cliente'] . "'",
            "'" . $_POST['ContactoCliente'] . "'",
            "'" . $_POST['Telefono'] . "'",
            "'" . $_POST['Correo'] . "'",
            "'" . $_POST['SucursalCliente'] . "'",
            "'" . $_POST['Direccion'] . "'",
            "'" . $_POST['Ciudad'] . "'",
            "'" . $_POST['Barrio'] . "'",
            "'" . $_POST['ContactoSucursal'] . "'",
            "'" . $_POST['OrdenServicio'] . "'",
            "'" . $_POST['Area'] . "'",
            "'" . $_POST['TipoVisita'] . "'",
            "'" . $_POST['Empleado'] . "'",
            "'" . $_POST['Estado'] . "'",
            "'" . LSiqmlObs($_POST['Hallazgo']) . "'",
            "'" . LSiqmlObs($_POST['Recomendaciones']) . "'",
            "'" . $_POST['ResponsableCliente'] . "'",
            "'" . LSiqmlObs($_POST['ComentariosCierre']) . "'",
            "'" . FormatoFecha($_POST['FechaCreacion'], $_POST['HoraCreacion']) . "'",
            "'" . FormatoFecha($_POST['FechaCierre'], $_POST['HoraCierre']) . "'",
            "1",
            $ImgEvidencia1,
            $ImgEvidencia2,
            $ImgEvidencia3,
            $ImgCierre,
            $NombreFirmaCliente,
            $NombreFirmaTecnico,
            "'" . $_POST['Revision'] . "'",
            "'" . $_POST['EstadoCriticidad'] . "'",
            "'" . $_SESSION['CodUser'] . "'",
            "'" . $_SESSION['CodUser'] . "'",
            "'" . $Type . "'",
        );
        $SQL_InsFrm = EjecutarSP('sp_tbl_FrmHallazgos', $ParamInsFrm, 101);
        if ($SQL_InsFrm) {
            $row_NewIdFrm = sqlsrv_fetch_array($SQL_InsFrm);
            $IdFrm = $row_NewIdFrm[0];

            //Insertar plagas
            $Count = count($_POST['TipoPlaga']);
            $i = 0;
            while ($i < $Count) {
                if ($_POST['TipoPlaga'][$i] != "") {
                    //Cargar foto
                    if ($_FILES['FotoPlaga']['tmp_name'][$i] != "") {
                        if (is_uploaded_file($_FILES['FotoPlaga']['tmp_name'][$i])) {
                            $Nombre_Archivo = $_FILES['FotoPlaga']['name'][$i];
                            //Sacar la extension del archivo
                            $Ext = end(explode('.', $Nombre_Archivo));
                            //Sacar el nombre sin la extension
                            $OnlyName = substr($Nombre_Archivo, 0, strlen($Nombre_Archivo) - (strlen($Ext) + 1));
                            //Reemplazar espacios
                            $OnlyName = str_replace(" ", "_", $OnlyName);
                            $Prefijo = substr(uniqid(rand()), 0, 3);
                            $FotoPlaga = LSiqmlObs($OnlyName) . "_" . date('Ymd') . $Prefijo . "." . $Ext;
                            move_uploaded_file($_FILES['FotoPlaga']['tmp_name'][$i], $dir_new . $FotoPlaga);
                            if (!RedimensionarImagen($FotoPlaga, $dir_new . $FotoPlaga, 300, 400)) {
                                $sw_error = 1;
                                $msg_error = "Error al redimensionar la foto de la plaga";
                            }
                        } else {
                            $sw_error = 1;
                            $msg_error = "No se pudo cargar la foto de la plaga";
                        }
                    }
                    if ($_POST['tl'] == 0) { //Insertando
                        $Type = 1;
                        $ID = $IdFrm;
                    } else { //Actualizando
                        $Type = 2;
                        $ID = base64_decode($_POST['IdFrm']);
                    }
                    $ParamInsPlagas = array(
                        "'" . $ID . "'",
                        "'" . $_POST['TipoPlaga'][$i] . "'",
                        "'" . $_POST['Cantidad'][$i] . "'",
                        "'" . $FotoPlaga . "'",
                        "'" . $_SESSION['CodUser'] . "'",
                        "1",
                    );

                    $SQL_InsPlagas = EjecutarSP('sp_tbl_FrmHallazgos_Plagas', $ParamInsPlagas, 101);
                    if (!$SQL_InsPlagas) {
                        $sw_error = 1;
                        $msg_error = "Error al insertar las plagas";
                    }
                }
                $i = $i + 1;
            }

            //Insertar las recurrecias
            if ($_POST['tl'] == 1) { //Actualizando
                $ID = base64_decode($_POST['IdFrm']);

                if ($_POST['FechaRec'] != "") {
                    $ParamInsRec = array(
                        "'" . $ID . "'",
                        "'" . $_POST['FechaRec'] . "'",
                        "'" . $_POST['ComentariosRec'] . "'",
                        "'" . $_SESSION['CodUser'] . "'",
                        "1",
                    );

                    $SQL_InsRec = EjecutarSP('sp_tbl_FrmHallazgos_Rec', $ParamInsRec, 101);
                    if (!$SQL_InsRec) {
                        $sw_error = 1;
                        $msg_error = "Error al insertar la recurrencia del hallazgo";
                    }
                }
            }

            if ($_POST['tl'] == 0) {
                if (isset($_POST['chkEnvioMail']) && ($_POST['chkEnvioMail'] == 1)) {
                    //Enviar correo
                    $ParamEnviaMail = array(
                        "'" . $ID . "'",
                        "'1001'",
                        "'4'",
                    );
                    $SQL_EnviaMail = EjecutarSP('usp_CorreoEnvio', $ParamEnviaMail, 101);
                    if (!$SQL_EnviaMail) {
                        $sw_error = 1;
                        $msg_error = "Error al enviar el correo al usuario.";
                    }
                }
            }

            sqlsrv_close($conexion);
            if ($_POST['tl'] == 1) {
                header('Location:' . base64_decode($_POST['return']) . '&a=' . base64_encode("OK_FrmUpd"));
            } else {
                header('Location:' . base64_decode($_POST['return']) . '&a=' . base64_encode("OK_FrmAdd"));
            }

        } else {
            $sw_error = 1;
            $msg_error = "Error al crear el formulario";
        }
    } catch (Exception $e) {
        echo 'Excepcion capturada: ', $e->getMessage(), "\n";
    }
}

if (isset($_GET['POpen']) && ($_GET['POpen'] != "")) {

    //Insertar formulario
    try {

        $ParamInsFrm = array(
            "'" . base64_decode($_GET['id_frm']) . "'",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "'5'",
        );
        $SQL_InsFrm = EjecutarSP('sp_tbl_FrmHallazgos', $ParamInsFrm, 101);
        if ($SQL_InsFrm) {

            sqlsrv_close($conexion);
            header('Location:frm_hallazgos.php?id=' . $_GET['id_frm'] . '&tl=1&return=' . $_GET['return'] . '&pag=' . $_GET['pag'] . '&frm=' . $frm . '&a=' . base64_encode("OK_OpenFrm"));

        } else {
            $sw_error = 1;
            $msg_error = "Error al reabrir el formulario";
        }
    } catch (Exception $e) {
        echo 'Excepcion capturada: ', $e->getMessage(), "\n";
    }
}

if (isset($_GET['PDel']) && ($_GET['PDel'] != "")) {

    //Insertar formulario
    try {

        $ParamInsFrm = array(
            "'" . $_GET['id_frm'] . "'",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "NULL",
            "'4'",
        );
        $SQL_InsFrm = EjecutarSP('sp_tbl_FrmHallazgos', $ParamInsFrm, 101);
        if ($SQL_InsFrm) {

            sqlsrv_close($conexion);
            header('Location:gestionar_hallazgos.php?id=' . $frm . '&a=' . base64_encode("OK_FrmDel"));

        } else {
            $sw_error = 1;
            $msg_error = "Error al eliminar el formulario";
        }
    } catch (Exception $e) {
        echo 'Excepcion capturada: ', $e->getMessage(), "\n";
    }
}

if ($type_frm == 1) { //Editando el formulario
    //Llamada
    $SQL = Seleccionar('uvw_tbl_FrmHallazgos', '*', "ID_Frm='" . $IdFrm . "'");
    $row = sqlsrv_fetch_array($SQL);

    //Clientes
    $SQL_Cliente = Seleccionar("uvw_Sap_tbl_Clientes", "CodigoCliente, NombreCliente", "CodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'NombreCliente');

    //Contactos clientes
    $SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'NombreContacto');

    //Sucursales
    $SQL_SucursalCliente = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'NombreSucursal');

    //Orden de servicio
    $SQL_OrdenServicioCliente = Seleccionar('uvw_Sap_tbl_LlamadasServicios', '*', "ID_CodigoCliente='" . $row['ID_CodigoCliente'] . "' and NombreSucursal='" . $row['NombreSucursal'] . "'", 'AsuntoLlamada');

    //Anexos
    $SQL_AnexoLlamada = Seleccionar('uvw_tbl_DocumentosSAP_Anexos', '*', "ID_Documento='" . $row['ID_Frm'] . "'");

    //Areas del cliente
    $SQL_Areas = Seleccionar('uvw_tbl_Areas_Clientes', '*', "IdCodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'DeArea');

    //Plagas
    $SQL_Plagas = Seleccionar('uvw_tbl_FrmHallazgos_Plagas', '*', "ID_Frm='" . $row['ID_Frm'] . "'");
    $Num_Plagas = sqlsrv_num_rows($SQL_Plagas);

    //Recurrencias
    $SQL_Recurrencias = Seleccionar('uvw_tbl_FrmHallazgos_Rec', '*', "ID_Frm='" . $row['ID_Frm'] . "'", 'FechaRegistro', 'DESC');

}

if ($sw_error == 1) {
    //Si ocurre un error, vuelvo a consultar los datos insertados desde la base de datos.
    $SQL = Seleccionar('uvw_tbl_FrmHallazgos', '*', "ID_Frm='" . $IdFrm . "'");
    $row = sqlsrv_fetch_array($SQL);

    //Clientes
    $SQL_Cliente = Seleccionar("uvw_Sap_tbl_Clientes", "CodigoCliente, NombreCliente", "CodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'NombreCliente');

    //Contactos clientes
    $SQL_ContactoCliente = Seleccionar('uvw_Sap_tbl_ClienteContactos', '*', "CodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'NombreContacto');

    //Sucursales
    $SQL_SucursalCliente = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "CodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'NombreSucursal');

    //Anexos
    $SQL_AnexoLlamada = Seleccionar('uvw_tbl_DocumentosSAP_Anexos', '*', "ID_Documento='" . $IdFrm . "'");

    //Areas del cliente
    $SQL_Areas = Seleccionar('uvw_tbl_Areas_Clientes', '*', "IdCodigoCliente='" . $row['ID_CodigoCliente'] . "'", 'DeArea');

    //Plagas
    $SQL_Plagas = Seleccionar('uvw_tbl_FrmHallazgos_Plagas', '*', "ID_Frm='" . $IdFrm . "'");
    $Num_Plagas = sqlsrv_num_rows($SQL_Plagas);

    //Recurrencias
    $SQL_Recurrencias = Seleccionar('uvw_tbl_FrmHallazgos_Rec', '*', "ID_Frm='" . $IdFrm . "'", 'FechaRegistro', 'DESC');
}

//Tipo de visita
$SQL_TipoVisita = Seleccionar('uvw_Sap_tbl_TipoLlamadas', '*', '', 'DeTipoLlamada');

//Tipo de plaga
$SQL_TipoPlaga = Seleccionar('uvw_tbl_Plagas', '*', '', 'NombrePlaga');

//Empleados
if ($type_frm == 0) {
    $SQL_EmpleadoLlamada = Seleccionar('uvw_Sap_tbl_Empleados', '*', "ID_Empleado='" . $_SESSION['CodigoSAP'] . "'", 'NombreEmpleado');
} else {
    $SQL_EmpleadoLlamada = Seleccionar('uvw_Sap_tbl_Empleados', '*', '', 'NombreEmpleado');
}

//Estado formulario
$SQL_EstadoLlamada = Seleccionar('uvw_tbl_EstadoLlamada', '*');

//Estado criticidad
$SQL_EstadoCriticidad = Seleccionar('uvw_tbl_EstadoCriticidad', '*');

//Condiciones
$SQL_Condicion = Seleccionar('uvw_tbl_Areas_Condicion', '*');

// @author Stiven Muñoz Murillo
// @version 10/01/2022

// Marcas de vehiculo en la llamada de servicio
$SQL_MarcaVehiculo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_MarcaVehiculo', '*');

// Lineas de vehiculo en la llamada de servicio
$SQL_LineaVehiculo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_LineaVehiculo', '*');

// Modelo o año de fabricación de vehiculo en la llamada de servicio
$SQL_ModeloVehiculo = Seleccionar('uvw_Sap_tbl_LlamadasServicios_AñoModeloVehiculo', '*');
?>
<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo $Title; ?> | <?php echo NOMBRE_PORTAL; ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->
<?php
if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_OpenFrm"))) {
    echo "<script>
		$(document).ready(function() {
			swal({
                title: '¡Listo!',
                text: 'El formulario ha sido abierto nuevamente.',
                type: 'success'
            });
		});
		</script>";
}
if (isset($sw_error) && ($sw_error == 1)) {
    echo "<script>
		$(document).ready(function() {
			swal({
                title: '¡Ha ocurrido un error!',
                text: '" . $msg_error . "',
                type: 'error'
            });
		});
		</script>";
}
?>
<script type="text/javascript">
	$(document).ready(function() {//Cargar los combos dependiendo de otros
		$("#Cliente").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var Cliente=document.getElementById('Cliente').value;
			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=2&id="+Cliente,
				success: function(response){
					$('#ContactoCliente').html(response).fadeIn();
					$('#ContactoCliente').trigger('change');
					//$('.ibox-content').toggleClass('sk-loading',false);
				}
			});
			<?php if ($dt_LS == 0) { //Para que no recargue las listas cuando vienen de una llamada de servicio.?>
			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=3&id="+Cliente,
				success: function(response){
					$('#SucursalCliente').html(response).fadeIn();
					$('#SucursalCliente').trigger('change');
				}
			});
			<?php }?>
			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=16&id="+Cliente,
				success: function(response){
					$('#Area1').html(response).fadeIn();
				}
			});
			$('.ibox-content').toggleClass('sk-loading',false);
		});
		$("#SucursalCliente").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var Cliente=document.getElementById('Cliente').value;
			var Sucursal=document.getElementById('SucursalCliente').value;
			$.ajax({
				url:"ajx_buscar_datos_json.php",
				data:{type:1,CardCode:Cliente,Sucursal:Sucursal},
				dataType:'json',
				success: function(data){
					document.getElementById('Direccion').value=data.Direccion;
					// document.getElementById('Barrio').value=data.Barrio;
					// document.getElementById('Ciudad').value=data.Ciudad;
					// document.getElementById('ContactoSucursal').value=data.NombreContacto;
					document.getElementById('Telefono').value=data.TelefonoContacto;
					document.getElementById('Correo').value=data.CorreoContacto;
				}
			});
			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=4&id="+Cliente+"&suc="+Sucursal,
				success: function(response){
					$('#OrdenServicio').html(response).fadeIn();
					$('#OrdenServicio').val(null).trigger('change');
					$('.ibox-content').toggleClass('sk-loading',false);
				}
			});
			$('.ibox-content').toggleClass('sk-loading',false);
		});
		$("#ContactoCliente").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var Contacto=document.getElementById('ContactoCliente').value;
			$.ajax({
				url:"ajx_buscar_datos_json.php",
				data:{type:5,Contacto:Contacto},
				dataType:'json',
				success: function(data){
					//document.getElementById('Telefono').value=data.TelefonoContacto;
					//document.getElementById('Correo').value=data.Correo;
					$('.ibox-content').toggleClass('sk-loading',false);
				}
			});
		});

		// Stiven Muñoz Murillo, 10/01/2021
		$("#CDU_Marca").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var marcaVehiculo=document.getElementById('CDU_Marca').value;
			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=39&id="+marcaVehiculo,
				success: function(response){
					$('#CDU_Linea').html(response).fadeIn();
					$('#CDU_Linea').trigger('change');
					$('.ibox-content').toggleClass('sk-loading',false);
				}
			});
		});
	});

function ConsultarDatosCliente(){
	var Cliente=document.getElementById('Cliente');
	if(Cliente.value!=""){
		self.name='opener';
		remote=open('socios_negocios.php?id='+Base64.encode(Cliente.value)+'&ext=1&tl=1','remote','location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}

function AgregarArea(){
	var Cliente=document.getElementById('Cliente');
	if(Cliente.value!=""){
		self.name='opener';
		var altura=370;
		var anchura=500;
		var posicion_y=parseInt((window.screen.height/2)-(altura/2));
		var posicion_x=parseInt((window.screen.width/2)-(anchura/2));
		remote=open('popup_agregar_area.php?cardcode='+Base64.encode(Cliente.value)+'&tl=1','remote','width='+anchura+',height='+altura+',location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=no,status=yes,left='+posicion_x+',top='+posicion_y);
		remote.focus();
	}
}

function AbrirFirma(IDCampo){
	var posicion_x;
	var posicion_y;
	posicion_x=(screen.width/2)-(1200/2);
	posicion_y=(screen.height/2)-(500/2);
	self.name='opener';
	remote=open('popup_firma.php?id='+Base64.encode(IDCampo),'remote',"width=1200,height=500,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no,fullscreen=no,directories=no,status=yes,left="+posicion_x+",top="+posicion_y+"");
	remote.focus();
}

<?php if ($type_frm == 1) {?>
function Eliminar(){
	swal({
		title: "Eliminar",
		text: "¿Está seguro que desea eliminar este registro? Esta acción no tiene reversión.",
		type: "warning",
		showCancelButton: true,
		confirmButtonText: "Si, estoy seguro",
		cancelButtonText: "Cancelar",
		closeOnConfirm: false
	},
	function(){
		$('.ibox-content').toggleClass('sk-loading');
		location.href='frm_hallazgos.php?PDel=1&id_frm=<?php echo $IdFrm; ?>&frm=<?php echo $frm; ?>';
	});
}
<?php }?>
</script>
<!-- InstanceEndEditable -->
</head>

<body>

<div id="wrapper">

    <?php include "includes/menu.php";?>

    <div id="page-wrapper" class="gray-bg">
        <?php include "includes/menu_superior.php";?>
        <!-- InstanceBeginEditable name="Contenido" -->
        <div class="row wrapper border-bottom white-bg page-heading">
                <div class="col-sm-8">
                    <h2><?php echo $Title; ?></h2>
                    <ol class="breadcrumb">
                        <li>
                            <a href="index1.php">Inicio</a>
                        </li>
                        <li>
                            <a href="#"><?php echo isset($row_Cat['NombreCategoriaPadre']) ? $row_Cat['NombreCategoriaPadre'] : ""; ?></a>
                        </li>
                        <li class="active">
                            <a href="<?php echo isset($row_Cat['URL']) ? $row_Cat['URL'] : "" . "?id=" . $frm; ?>"><?php echo isset($row_Cat['NombreCategoria']) ? $row_Cat['NombreCategoria'] : ""; ?></a>
                        </li>
						<li class="active">
                            <strong><?php echo $Title; ?></strong>
                        </li>
                    </ol>
                </div>
            </div>

         <div class="wrapper wrapper-content">
			 <div class="ibox-content">
				  <?php include "includes/spinner.php";?>
          <div class="row">
           <div class="col-lg-12">
              <form action="frm_hallazgos.php" method="post" class="form-horizontal" enctype="multipart/form-data" id="CrearFrm">
				<div class="form-group">
					<label class="col-xs-12"><h3 class="bg-muted p-xs b-r-sm"><i class="fa fa-user"></i> Datos del propietario</h3></label>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label"><i onClick="ConsultarDatosCliente();" title="Consultar cliente" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span></label>
					<div class="col-lg-3">
						<input name="Cliente" type="hidden" id="Cliente" value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['ID_CodigoCliente'];} elseif ($dt_LS == 1) {echo $row_Cliente['CodigoCliente'];}?>">
						<input name="NombreCliente" type="text" required="required" class="form-control" id="NombreCliente" placeholder="Digite para buscar..." <?php if ((($type_frm == 1) && ($row['Cod_Estado'] == '-1')) || ($dt_LS == 1)) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['NombreCliente'];} elseif ($dt_LS == 1) {echo $row_Cliente['NombreCliente'];}?>">
               	  	</div>
					<label class="col-lg-1 control-label">Contacto</label>
					<div class="col-lg-3">
                    	<select name="ContactoCliente" class="form-control" id="ContactoCliente" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
						  <?php if ((($type_frm == 0) || ($sw_error == 1)) && ($dt_LS != 1)) {?><option value="">Seleccione...</option><?php }?>
                          <?php if (($type_frm == 1) || ($sw_error == 1) || ($dt_LS == 1)) {while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) {?>
								<option value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>" <?php if ((isset($row['ID_Contacto'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['ID_Contacto']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ContactoCliente['ID_Contacto']; ?></option>
						  <?php }}?>
						</select>
               	  	</div>
					<label class="col-lg-1 control-label">Sucursal</label>
				  	<div class="col-lg-3">
                    	<select name="SucursalCliente" class="form-control select2" id="SucursalCliente" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
						  <?php if ((($type_frm == 0) || ($sw_error == 1)) && ($dt_LS != 1)) {?><option value="">Seleccione...</option><?php }?>
                          <?php if (($type_frm == 1) || ($sw_error == 1) || ($dt_LS == 1)) {while ($row_SucursalCliente = sqlsrv_fetch_array($SQL_SucursalCliente)) {?>
								<option value="<?php echo $row_SucursalCliente['NombreSucursal']; ?>" <?php if ((isset($row['NombreSucursal'])) && (strcmp($row_SucursalCliente['NombreSucursal'], $row['NombreSucursal']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_SucursalCliente['NombreSucursal']; ?></option>
						  <?php }}?>
						</select>
               	  	</div>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Dirección</label>
					<div class="col-lg-3">
                    	<input name="Direccion" type="text" required="required" class="form-control" id="Direccion" maxlength="100" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['Direccion'];} elseif ($dt_LS == 1) {echo base64_decode($_GET['Direccion']);}?>">
               	  	</div>
					<label class="col-lg-1 control-label">Teléfono</label>
					<div class="col-lg-3">
                    	<input name="Telefono" type="text" class="form-control" id="Telefono" maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['TelefonoContacto'];} elseif ($dt_LS == 1) {echo base64_decode($_GET['Telefono']);}?>">
               	  	</div>
					<label class="col-lg-1 control-label">Celular</label>
					<div class="col-lg-3">
                    	<input name="Celular" type="text" class="form-control" id="Celular" maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['CelularContacto'];} elseif ($dt_LS == 1) {echo base64_decode($_GET['Celular']);}?>">
               	  	</div>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Correo</label>
					<div class="col-lg-3">
                    	<input name="Correo" type="text" class="form-control" id="Correo" maxlength="100" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['CorreoContacto'];} elseif ($dt_LS == 1) {echo base64_decode($_GET['Correo']);}?>">
               	  	</div>
				</div>
				<div class="form-group">
					<label class="col-xs-12"><h3 class="bg-muted p-xs b-r-sm"><i class="fa fa-info-circle"></i> Datos del vehículo</h3></label>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Serial Interno (Placa) <span class="text-danger">*</span></label>
					<div class="col-lg-3">
						<input <?php if (!PermitirFuncion(1602)) {echo "readonly='readonly'";}?> autocomplete="off" name="SerialInterno" type="text" required="required" class="form-control" id="SerialInterno" maxlength="150" value="<?php if (isset($row['SerialInterno'])) {echo $row['SerialInterno'];}?>">
					</div>
					<label class="col-lg-1 control-label">Serial Fabricante (VIN) <span class="text-danger">*</span></label>
					<div class="col-lg-3">
						<input <?php if (!PermitirFuncion(1602)) {echo "readonly='readonly'";}?> autocomplete="off" name="SerialFabricante" type="text" required="required" class="form-control" id="SerialFabricante" maxlength="150" value="<?php if (isset($row['SerialFabricante'])) {echo $row['SerialFabricante'];}?>">
					</div>
					<label class="col-lg-1 control-label">No_Motor</label>
					<div class="col-lg-3">
						<input <?php if (!PermitirFuncion(1602)) {echo "readonly='readonly'";}?> autocomplete="off" name="CDU_No_Motor" type="text" class="form-control" id="CDU_No_Motor" maxlength="100"
						value="<?php if (isset($row['CDU_No_Motor'])) {echo $row['CDU_No_Motor'];}?>">
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Marca del vehículo <span class="text-danger">*</span></label>
					<div class="col-lg-3">
						<select <?php if (!PermitirFuncion(1602)) {echo "disabled='disabled'";}?> name="CDU_Marca" class="form-control select2" required="required" id="CDU_Marca">
							<option value="" disabled selected>Seleccione...</option>
							<?php while ($row_MarcaVehiculo = sqlsrv_fetch_array($SQL_MarcaVehiculo)) {?>
							<option value="<?php echo $row_MarcaVehiculo['DeMarcaVehiculo']; //['IdMarcaVehiculo'];                                          ?>"
							<?php if ((isset($row['CDU_Marca'])) && (strcmp($row_MarcaVehiculo['DeMarcaVehiculo'], $row['CDU_Marca']) == 0)) {echo "selected=\"selected\"";}?>>
								<?php echo $row_MarcaVehiculo['DeMarcaVehiculo']; ?>
							</option>
							<?php }?>
						</select>
					</div>
					<label class="col-lg-1 control-label">Línea del vehículo <span class="text-danger">*</span></label>
					<div class="col-lg-3">
						<select <?php if (!PermitirFuncion(1602)) {echo "disabled='disabled'";}?> name="CDU_Linea" class="form-control select2" required="required" id="CDU_Linea">
								<option value="" disabled selected>Seleccione...</option>
							<?php while ($row_LineaVehiculo = sqlsrv_fetch_array($SQL_LineaVehiculo)) {?>
								<option value="<?php echo $row_LineaVehiculo['DeLineaModeloVehiculo']; //['IdLineaModeloVehiculo'];                                         ?>"
								<?php if ((isset($row['CDU_Linea'])) && (strcmp($row_LineaVehiculo['DeLineaModeloVehiculo'], $row['CDU_Linea']) == 0)) {echo "selected=\"selected\"";}?>>
									<?php echo $row_LineaVehiculo['DeLineaModeloVehiculo']; //. " - " . $row_LineaVehiculo['MarcaVehiculo'];                                                 ?>
								</option>
							<?php }?>
						</select>
					</div>
					<label class="col-lg-1 control-label">Modelo del vehículo</label>
					<div class="col-lg-3">
						<select <?php if (!PermitirFuncion(1602)) {echo "disabled='disabled'";}?> name="CDU_Ano" class="form-control select2" id="CDU_Ano">
								<option value="" disabled selected>Seleccione...</option>
							<?php while ($row_ModeloVehiculo = sqlsrv_fetch_array($SQL_ModeloVehiculo)) {?>
								<option value="<?php echo $row_ModeloVehiculo['AñoModeloVehiculo']; //['CodigoModeloVehiculo'];                                                        ?>"
								<?php if (isset($row['CDU_Ano']) && ((strcmp($row_ModeloVehiculo['CodigoModeloVehiculo'], $row['CDU_Ano']) == 0) || (strcmp($row_ModeloVehiculo['AñoModeloVehiculo'], $row['CDU_Ano']) == 0))) {echo "selected=\"selected\"";}?>>
									<?php echo $row_ModeloVehiculo['AñoModeloVehiculo']; ?>
								</option>
							<?php }?>
						</select>
					</div>
				</div>
				<div class="form-group">
					<label class="col-xs-12"><h3 class="bg-muted p-xs b-r-sm"><i class="fa fa-info-circle"></i> Datos de recepción</h3></label>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Fecha y hora de ingreso</label>
					<div class="col-lg-2 input-group date">
						 <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="FechaCreacion" type="text" class="form-control" id="FechaCreacion" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('Y-m-d');} else {echo date('Y-m-d');}?>" readonly='readonly' placeholder="YYYY-MM-DD" required>
					</div>
					<div class="col-lg-2 input-group clockpicker2" data-autoclose="true">
						<input name="HoraCreacion" id="HoraCreacion" type="text" class="form-control" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('H:i');} else {echo date('H:i');}?>" readonly='readonly' placeholder="hh:mm" required>
						<span class="input-group-addon">
							<span class="fa fa-clock-o"></span>
						</span>
					</div>

					<label class="col-lg-1 control-label">Fecha y hora Aprox. Entrega</label>
					<div class="col-lg-2 input-group date">
						 <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="FechaCreacion" type="text" class="form-control" id="FechaCreacion" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('Y-m-d');} else {echo date('Y-m-d');}?>" readonly='readonly' placeholder="YYYY-MM-DD" required>
					</div>
					<div class="col-lg-2 input-group clockpicker2" data-autoclose="true">
						<input name="HoraCreacion" id="HoraCreacion" type="text" class="form-control" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('H:i');} else {echo date('H:i');}?>" readonly='readonly' placeholder="hh:mm" required>
						<span class="input-group-addon">
							<span class="fa fa-clock-o"></span>
						</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">KM actual</label>
					<div class="col-lg-3">
						<input <?php if (!PermitirFuncion(1602)) {echo "readonly='readonly'";}?> autocomplete="off" name="CDU_No_Motor" type="text" class="form-control" id="CDU_No_Motor" maxlength="100"
						value="<?php if (isset($row['CDU_No_Motor'])) {echo $row['CDU_No_Motor'];}?>">
					</div>
					<label class="col-lg-1 control-label">Nivel de combustible</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="si">SI</option>
								<option value="no">NO</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
					<label class="col-lg-1 control-label">Campaña autorizada por cliente</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="si">SI</option>
								<option value="no">NO</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">No. Campaña</label>
					<div class="col-lg-3">
						<input <?php if (!PermitirFuncion(1602)) {echo "readonly='readonly'";}?> autocomplete="off" name="CDU_No_Motor" type="text" class="form-control" id="CDU_No_Motor" maxlength="100"
						value="<?php if (isset($row['CDU_No_Motor'])) {echo $row['CDU_No_Motor'];}?>">
					</div>
					<label class="col-lg-1 control-label">Medio por el cual se informo campaña</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="si">SI</option>
								<option value="no">NO</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Fecha hora propietario autoriza campaña</label>
					<div class="col-lg-2 input-group date">
						 <span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="FechaCreacion" type="text" class="form-control" id="FechaCreacion" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('Y-m-d');} else {echo date('Y-m-d');}?>" readonly='readonly' placeholder="YYYY-MM-DD" required>
					</div>
					<div class="col-lg-2 input-group clockpicker2" data-autoclose="true">
						<input name="HoraCreacion" id="HoraCreacion" type="text" class="form-control" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('H:i');} else {echo date('H:i');}?>" readonly='readonly' placeholder="hh:mm" required>
						<span class="input-group-addon">
							<span class="fa fa-clock-o"></span>
						</span>
					</div>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Servicio de movilidad ofrecido</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="si">SI</option>
								<option value="no">NO</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
					<label class="col-lg-1 control-label">Se hizo prueba de ruta</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="si">SI</option>
								<option value="no">NO</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
				</div>
				<div class="form-group">
					<label class="col-xs-12"><h3 class="bg-muted p-xs b-r-sm"><i class="fa fa-list"></i> Datos piezas de vehículo</h3></label>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Descripción de la pieza de vehículo (pieza)</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="si">SI</option>
								<option value="no">NO</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
					<label class="col-lg-1 control-label">Disponibilidad</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="si">SI</option>
								<option value="no">NO</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
						 <label class="col-lg-1 control-label">Estado</label>
					<div class="col-lg-2">
                    	<select name="Estado" class="form-control" id="Estado" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<option value="bueno">Bueno</option>
								<option value="malo">Malo</option>
						<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
								<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
						  <?php //}?>
						</select>
               	  	</div>
				</div>
				<div class="form-group">
					<label class="col-xs-12"><h3 class="bg-muted p-xs b-r-sm"><i class="fa fa-list"></i> Registros fotográficos</h3></label>
				</div>
				<!-- Inicio, Foto 1 -->
				<div class="form-group">
					<label class="col-lg-1 control-label">Frente</label>
					<div class="col-lg-5">
                    	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
							<div class="form-control" data-trigger="fileinput">
								<i class="glyphicon glyphicon-file fileinput-exists"></i>
							<span class="fileinput-filename"></span>
							</div>
							<span class="input-group-addon btn btn-default btn-file">
								<span class="fileinput-new">Seleccionar</span>
								<span class="fileinput-exists">Cambiar</span>
								<input name="Img1" type="file" id="Img1" onchange="uploadImage('Img1')"/>
							</span>
							<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
						</div>
               	  	</div>
				</div>
				<!-- Inicio, Foto 1 -->
				<!-- Inicio, Foto 2 -->
				<div class="form-group">
					<label class="col-lg-1 control-label">Lateral Izquierdo</label>
					<div class="col-lg-5">
                    	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
							<div class="form-control" data-trigger="fileinput">
								<i class="glyphicon glyphicon-file fileinput-exists"></i>
							<span class="fileinput-filename"></span>
							</div>
							<span class="input-group-addon btn btn-default btn-file">
								<span class="fileinput-new">Seleccionar</span>
								<span class="fileinput-exists">Cambiar</span>
								<input name="Img2" type="file" id="Img2" onchange="uploadImage('Img2')"/>
							</span>
							<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
						</div>
               	  	</div>
				</div>
				<!-- Fin, Foto 2 -->
				<!-- Inicio, Foto 3 -->
				<div class="form-group">
					<label class="col-lg-1 control-label">Lateral Derecho</label>
					<div class="col-lg-5">
                    	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
							<div class="form-control" data-trigger="fileinput">
								<i class="glyphicon glyphicon-file fileinput-exists"></i>
							<span class="fileinput-filename"></span>
							</div>
							<span class="input-group-addon btn btn-default btn-file">
								<span class="fileinput-new">Seleccionar</span>
								<span class="fileinput-exists">Cambiar</span>
								<input name="Img3" type="file" id="Img3" onchange="uploadImage('Img3')"/>
							</span>
							<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
						</div>
               	  	</div>
				</div>
				<!-- Fin, Foto 3 -->
				<!-- Inicio, Foto 4 -->
				<div class="form-group">
					<label class="col-lg-1 control-label">Trasero</label>
					<div class="col-lg-5">
                    	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
							<div class="form-control" data-trigger="fileinput">
								<i class="glyphicon glyphicon-file fileinput-exists"></i>
							<span class="fileinput-filename"></span>
							</div>
							<span class="input-group-addon btn btn-default btn-file">
								<span class="fileinput-new">Seleccionar</span>
								<span class="fileinput-exists">Cambiar</span>
								<input name="Img4" type="file" id="Img4" onchange="uploadImage('Img4')"/>
							</span>
							<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
						</div>
               	  	</div>
				</div>
				<!-- Fin, Foto 4 -->
				<!-- Inicio, Foto 5 -->
				<div class="form-group">
					<label class="col-lg-1 control-label">Capot</label>
					<div class="col-lg-5">
                    	<div class="fileinput fileinput-new input-group" data-provides="fileinput">
							<div class="form-control" data-trigger="fileinput">
								<i class="glyphicon glyphicon-file fileinput-exists"></i>
							<span class="fileinput-filename"></span>
							</div>
							<span class="input-group-addon btn btn-default btn-file">
								<span class="fileinput-new">Seleccionar</span>
								<span class="fileinput-exists">Cambiar</span>
								<input name="Img5" type="file" id="Img5" onchange="uploadImage('Img5')"/>
							</span>
							<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
						</div>
               	  	</div>
				</div>
				<!-- Fin, Foto 5 -->
				<div class="form-group">
					<label class="col-xs-12"><h3 class="bg-muted p-xs b-r-sm"><i class="fa fa-check-circle"></i> Fotos adicionales</h3></label>
				</div>
				<div class="form-group">
					<label class="col-lg-1 control-label">Texto de condiciones</label>
					<div class="col-lg-8">
						<textarea name="ComentariosCierre" rows="5" type="text" class="form-control" id="ComentariosCierre" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?>><?php if (($type_frm == 1) || ($sw_error == 1)) {echo utf8_decode($row['ComentariosCierre']);}?></textarea>
					</div>
				</div>

				<!-- Esto es otra cosa -->
				<input type="hidden" id="P" name="P" value="<?php echo base64_encode('MM_frmHallazgos') ?>" />
				<input type="hidden" id="swTipo" name="swTipo" value="0" />
				<input type="hidden" id="swError" name="swError" value="<?php echo $sw_error; ?>" />
				<input type="hidden" id="tl" name="tl" value="<?php echo $type_frm; ?>" />
				<input type="hidden" id="d_LS" name="d_LS" value="<?php echo $dt_LS; ?>" />
				<input type="hidden" id="IdFrm" name="IdFrm" value="<?php echo base64_encode($IdFrm); ?>" />
				<input type="hidden" id="return" name="return" value="<?php echo base64_encode($return); ?>" />
				<input type="hidden" id="frm" name="frm" value="<?php echo $frm; ?>" />
			</form>


			<!-- Stiven Muñoz Murillo, 10/01/2022 -->
			<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-paperclip"></i> Anexos</h5>
						 <a class="collapse-link pull-right">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<?php if ( /*$row['IdAnexoLlamada'] != 0*/false) {?>
								<div class="form-group">
									<div class="col-xs-12">
										<?php while ($row_AnexoLlamada = sqlsrv_fetch_array($SQL_AnexoLlamada)) {
    $Icon = IconAttach($row_AnexoLlamada['FileExt']);?>
											<div class="file-box">
												<div class="file">
													<a href="attachdownload.php?file=<?php echo base64_encode($row_AnexoLlamada['AbsEntry']); ?>&line=<?php echo base64_encode($row_AnexoLlamada['Line']); ?>" target="_blank">
														<div class="icon">
															<i class="<?php echo $Icon; ?>"></i>
														</div>
														<div class="file-name">
															<?php echo $row_AnexoLlamada['NombreArchivo']; ?>
															<br/>
															<small><?php echo $row_AnexoLlamada['Fecha']; ?></small>
														</div>
													</a>
												</div>
											</div>
										<?php }?>
									</div>
								</div>
						<?php } else {echo "<p>Sin anexos.</p>";}?>

						<div class="row">
							<form action="upload.php" class="dropzone" id="dropzoneForm" name="dropzoneForm">
								<?php if ($sw_error == 0) {LimpiarDirTemp();}?>
								<div class="fallback">
									<input name="File" id="File" type="file" form="dropzoneForm" />
								</div>
							 </form>
						</div>
					</div>
				</div>
				<!-- Fin Anexos -->
			<!-- Botones de acción al final del formulario, SMM -->
			   <div class="form-group">
				   <?php
$EliminaMsg = array("&a=" . base64_encode("OK_FrmAdd"), "&a=" . base64_encode("OK_FrmUpd"), "&a=" . base64_encode("OK_FrmDel")); //Eliminar mensajes

if (isset($_GET['return'])) {
    $_GET['return'] = str_replace($EliminaMsg, "", base64_decode($_GET['return']));
}
if (isset($_GET['return'])) {
    $return = base64_decode($_GET['pag']) . "?" . $_GET['return'];
} else {
    // Stiven Muñoz Murillo, 10/01/2022
    // $return = "gestionar_hallazgos.php?id=" . $frm;
}?>
					<div class="col-lg-9">
						 <br><br>
						<?php if (($type_frm == 1) && (PermitirFuncion(107) && (($row['Cod_Estado'] == '-3') || ($row['Cod_Estado'] == '-2')))) {?>
							<button class="btn btn-warning" type="submit" form="CrearFrm" id="Actualizar"><i class="fa fa-refresh"></i> Actualizar formulario</button>
						<?php }?>
						<?php if ($type_frm == 0) {?>
							<button class="btn btn-primary" form="CrearFrm" type="submit" id="Crear"><i class="fa fa-check"></i> Registrar formulario</button>
						<?php }?>
						<?php if (($type_frm == 1) && (PermitirFuncion(213) && ($row['Cod_Estado'] == '-1'))) {?>
							<button class="btn btn-success" type="button" onClick="Reabrir();"><i class="fa fa-reply"></i> Reabrir</button>
						<?php }?>
						<?php if ($type_frm == 1 && PermitirFuncion(213)) {?>
							<button class="btn btn-danger" type="button" onClick="Eliminar();"><i class="fa fa-times-circle"></i>&nbsp;Eliminar</button>
						<?php }?>
						<a href="<?php echo $return; ?>" class="alkin btn btn-outline btn-default"><i class="fa fa-arrow-circle-o-left"></i> Regresar</a>
					</div>
				</div>
			<!-- Pendiente a agregar al formulario, SMM -->
		   </div>
			</div>
          </div>
        </div>
        <!-- InstanceEndEditable -->
        <?php include "includes/footer.php";?>

    </div>
</div>
<?php include "includes/pie.php";?>
<!-- InstanceBeginEditable name="EditRegion4" -->

<script>
// Stiven Muñoz Murillo, 11/01/2022
Dropzone.options.dropzoneForm = {
	paramName: "File", // The name that will be used to transfer the file
	maxFilesize: "<?php echo ObtenerVariable("MaxSizeFile"); ?>", // MB
	maxFiles: "<?php echo ObtenerVariable("CantidadArchivos"); ?>",
	uploadMultiple: true,
	addRemoveLinks: true,
	dictRemoveFile: "Quitar",
	acceptedFiles: "<?php echo ObtenerVariable("TiposArchivos"); ?>",
	dictDefaultMessage: "<strong>Haga clic aqui para cargar anexos</strong><br>Tambien puede arrastrarlos hasta aqui<br><h4><small>(máximo <?php echo ObtenerVariable("CantidadArchivos"); ?> archivos a la vez)<small></h4>",
	dictFallbackMessage: "Tu navegador no soporta cargue de archivos mediante arrastrar y soltar",
	removedfile: function(file) {
		$.get( "includes/procedimientos.php", {
		type: "3",
		nombre: file.name
		}).done(function( data ) {
		var _ref;
		return (_ref = file.previewElement) !== null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
		});
		}
};
</script>

<script>
// Stiven Muñoz Murillo, 11/01/2022
function uploadImage(refImage) {
	var formData = new FormData();
	var files = $(`#${refImage}`)[0].files[0];

	if(typeof files !== 'undefined'){
		// console.log(files);
		formData.append('image',files);

		$.ajax({
			url: 'upload_image.php',
			type: 'post',
			data: formData,
			contentType: false,
			processData: false,
			success: function(response) {
				if (response != 0) {
					console.log(response);
				} else {
					swal({
						title: '¡Ha ocurrido un error!',
						text: 'Formato de imagen incorrecto.',
						type: 'error'
					});
				}
			}
		});
	}
	return false;
}
</script>

<script>
	 $(document).ready(function(){
		 $("#CrearFrm").validate({
			 submitHandler: function(form){
				 $('.ibox-content').toggleClass('sk-loading');
				 form.submit();
				}
			});
		 $(".alkin").on('click', function(){
				 $('.ibox-content').toggleClass('sk-loading');
			});
		 <?php if (PermitirFuncion(213)) {?>
		  $('#FechaCreacion').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: 'yyyy-mm-dd',
			 	todayHighlight: true,
			 	endDate: '<?php echo date('Y-m-d'); ?>'
            });
		 $('.clockpicker2').clockpicker();
	 	 <?php }?>
		 <?php if (!isset($row['Cod_Estado']) || ($row['Cod_Estado'] != '-1')) {?>
		 $('#FechaCierre').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: 'yyyy-mm-dd',
			 	todayHighlight: true,
			 	endDate: '<?php echo date('Y-m-d'); ?>'
            });
		 $('.clockpicker').clockpicker();
		<?php }?>
		<?php if ((!isset($row['Cod_Estado']) || ($row['Cod_Estado'] != '-1')) && ($type_frm == 1)) {?>
		 $('#FechaRec').datepicker({
                todayBtn: "linked",
                keyboardNavigation: false,
                forceParse: false,
                calendarWeeks: true,
                autoclose: true,
				format: 'yyyy-mm-dd',
			 	todayHighlight: true,
			 	endDate: '<?php echo date('Y-m-d'); ?>'
            });
	 	<?php }?>
		 $(".select2").select2();
		 $('.i-checks').iCheck({
			 checkboxClass: 'icheckbox_square-green',
             radioClass: 'iradio_square-green',
          });
		 var options = {
			url: function(phrase) {
				return "ajx_buscar_datos_json.php?type=7&id="+phrase;
			},

			getValue: "NombreBuscarCliente",
			requestDelay: 400,
			list: {
				match: {
					enabled: true
				},
				onClickEvent: function() {
					var value = $("#NombreCliente").getSelectedItemData().CodigoCliente;
					$("#Cliente").val(value).trigger("change");
				}
			}
		};
		 var options2 = {
			url: function(phrase) {
				return "ajx_buscar_datos_json.php?type=8&id="+phrase;
			},

			getValue: "Ciudad",
			requestDelay: 400,
			template: {
				type: "description",
				fields: {
					description: "Codigo"
				}
			},
			list: {
				match: {
					enabled: true
				}
			}
		};

		$("#NombreCliente").easyAutocomplete(options);
		$("#Ciudad").easyAutocomplete(options2);
		<?php if ($dt_LS == 1) {?>
		$('#Cliente').trigger('change');
	 	<?php }?>

		$(".btn_del").each(function (el){
			 $(this).bind("click",delRow);
		 });

		  <?php
if (($type_frm == 1) && (!PermitirFuncion(213))) {?>
				//$('#ClienteActividad option:not(:selected)').attr('disabled',true);
		 		$('#Revision option:not(:selected)').attr('disabled',true);
		<?php }?>
		 <?php
if ($dt_LS == 1) {?>
				//$('#ClienteActividad option:not(:selected)').attr('disabled',true);
		 		$('#SucursalCliente option:not(:selected)').attr('disabled',true);
		 		$('#OrdenServicio option:not(:selected)').attr('disabled',true);
		<?php }?>
	});
</script>
<?php if ($type_frm == 1) {?>
<script>
function Reabrir(){
	swal({
		title: "Reabrir",
		text: "¿Está seguro que desea volver a abrir este documento?",
		type: "warning",
		showCancelButton: true,
		confirmButtonText: "Si, estoy seguro",
		cancelButtonText: "Cancelar",
		closeOnConfirm: false
	},
	function(){
		$('.ibox-content').toggleClass('sk-loading');
		location.href='frm_hallazgos.php?POpen=1&id_frm=<?php echo base64_encode($IdFrm); ?>&return=<?php echo base64_encode($_GET['return']); ?>&pag=<?php echo $_GET['pag']; ?>&frm=<?php echo $frm; ?>';
	});
}
</script>
<?php }?>
<script>
function addField(btn){//Clonar div
	var clickID = parseInt($(btn).parent('div').parent('div').attr('id').replace('div_',''));
	//alert($(btn).parent('div').attr('id'));
	//alert(clickID);
	var newID = (clickID+1);

	$newClone = $('#div_'+clickID).clone(true);

	//div
	$newClone.attr("id",'div_'+newID);

	//select
	$newClone.children("div").eq(0).children("select").eq(0).attr('id','TipoPlaga_'+newID);

	//inputs
	$newClone.children("div").eq(1).children("input").eq(0).attr('id','Cantidad_'+newID);
	$newClone.children("div").eq(2).children("div").eq(0).children("span").eq(0).children("input").eq(0).attr('id','FotoPlaga_'+newID);

	//button
	$newClone.children("div").eq(3).children("button").eq(0).attr('id',''+newID);

	$newClone.insertAfter($('#div_'+clickID));

	//$("#"+clickID).val('Remover');
	document.getElementById(''+clickID).innerHTML="<i class='fa fa-minus'></i> Remover";
	document.getElementById(''+clickID).setAttribute('class','btn btn-warning btn-xs btn_del');
	document.getElementById(''+clickID).setAttribute('onClick','delRow2(this);');

	document.getElementById('FotoPlaga_'+newID).value='';

	//$("#"+clickID).addEventListener("click",delRow);

	//$("#"+clickID).bind("click",delRow);
}

function addHallazgo(btn){//Clonar div
	var clickID = parseInt($(btn).parent('div').parent('div').parent('div').attr('id').replace('divHallazgo',''));
	//alert($(btn).parent('div').attr('id'));
	//alert(clickID);
	var newID = (clickID+1);

	$('#Area'+clickID).select2("destroy");

	$newClone = $('#divHallazgo'+clickID).clone(true);

	//div
	$newClone.attr("id",'divHallazgo'+newID);

	//select
	$newClone.children("div").eq(0).children("div").eq(0).children("select").eq(0).attr('id','Area'+newID);


	$newClone.insertAfter($('#divHallazgo'+clickID));
	$('#Area'+clickID).select2();
	$('#Area'+newID).select2();
}
</script>
<script>
function delRow(){//Eliminar div
	$(this).parent('div').parent('div').remove();
}
function delRow2(btn){//Eliminar div
	$(btn).parent('div').parent('div').remove();
}
</script>
<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>