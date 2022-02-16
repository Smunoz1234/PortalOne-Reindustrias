<?php require_once "includes/conexion.php";
PermitirAcceso(1706);
$IdFrm = "";
$msg_error = ""; //Mensaje del error
$dt_LS = 0; //sw para saber si vienen datos del SN. 0 no vienen. 1 si vienen.

//Nombre del formulario
if (isset($_REQUEST['frm']) && ($_REQUEST['frm'] != "")) {
    $frm = $_REQUEST['frm'];

    // Stiven Muñoz Murillo, 10/01/2022
    $SQL_Cat = Seleccionar("uvw_tbl_Categorias", "ID_Categoria, NombreCategoria, NombreCategoriaPadre, URL", "ID_Categoria = '" . base64_decode($frm) . "'");
} else {
    // Stiven Muñoz Murillo, 09/02/2022
    $frm = "";
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

    //Sucursal cliente, (Se agrego "TipoDireccion='S' AND ...")
    $SQL_SucursalCliente = Seleccionar('uvw_Sap_tbl_Clientes_Sucursales', '*', "TipoDireccion='S' AND CodigoCliente='" . base64_decode($_GET['Cardcode']) . "'", 'NombreSucursal');

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

    // Inicio, Enviar datos al WebService
    $Cabecera = $_POST;

    try {
        $Metodo = "RecepcionVehiculos";
        $Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true);

        if ($Resultado->Success == 0) {
            // $sw_error = 1;
            $msg_error = $Resultado->Mensaje;
            //header("Location:tarjeta_equipo.php?id=$IdTarjetaEquipo&swError=1&a=" . base64_encode($Msg));
            //echo "<script>alert('$msg_error'); location = 'tarjeta_equipo.php';</script>";
        }
    } catch (Exception $e) {
        echo 'Excepcion capturada: ', $e->getMessage(), "\n";
    }

    /*
    try {
    if ($Metodo == 1) { //Creando
    $Metodo = "TarjetaEquipos";
    $Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true);
    } else { //Editando
    $Metodo = "TarjetaEquipos/" . base64_decode($_POST['ID_TarjetaEquipo']);
    $Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true, "PUT");
    }

    if ($Resultado->Success == 0) {
    $sw_error = 1;
    $msg_error = $Resultado->Mensaje;
    $Cabecera_json = json_encode($Cabecera);
    //header("Location:tarjeta_equipo.php?id=$IdTarjetaEquipo&swError=1&a=" . base64_encode($Msg));
    //echo "<script>alert('$msg_error'); location = 'tarjeta_equipo.php';</script>";
    } else {
    $Msg = ($_POST['tl'] == 1) ? "OK_TarjetaEquipoUpdate" : "OK_TarjetaEquipoAdd";

    if ($_POST['tl'] == 1) {
    header('Location:tarjeta_equipo.php?id=' . $_POST['ID_TarjetaEquipo'] . '&tl=1&a=' . base64_encode($Msg));
    } else {
    $SQL_ID = Seleccionar('uvw_Sap_tbl_TarjetasEquipos', 'IdTarjetaEquipo', "SerialInterno='" . $row_json['SerialInterno'] . "'");
    $row_ID = sqlsrv_fetch_array($SQL_ID);

    header('Location:tarjeta_equipo.php?id=' . base64_encode($row_ID['IdTarjetaEquipo']) . '&tl=1&a=' . base64_encode($Msg));
    }

    $edit = 1;
    $_GET['a'] = base64_encode($Msg);
    }
    } catch (Exception $e) {
    echo 'Excepcion capturada: ', $e->getMessage(), "\n";
    }
     */
    // Fin, Enviar datos al WebService

    /*
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
 */
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

// Marcas de vehiculo en la tarjeta de equipo
$SQL_MarcaVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_MarcaVehiculo', '*');

// Lineas de vehiculo en la tarjeta de equipo
$SQL_LineaVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_LineaVehiculo', '*');

// Modelo o año de fabricación de vehiculo en la tarjeta de equipo
$SQL_ModeloVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_AñoModeloVehiculo', '*');

// SMM, 15/02/2022

// Colores de vehiculo en la tarjeta de equipo
$SQL_ColorVehiculo = Seleccionar('uvw_Sap_tbl_TarjetasEquipos_ColorVehiculo', '*');

// Preguntas en la recepción de vehículo
$SQL_Preguntas = Seleccionar('tbl_RecepcionVehiculos_Preguntas', '*');
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
			Swal.fire({
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
			Swal.fire({
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
		var borrarLineaModeloVehiculo = true;

		$("#id_socio_negocio").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var Cliente=document.getElementById('id_socio_negocio').value;

			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=2&id="+Cliente,
				success: function(response){
					$('#ContactoCliente').html(response).fadeIn();
					$('#ContactoCliente').trigger('change');
					//$('.ibox-content').toggleClass('sk-loading',false);
				}
			});

			<?php if ($dt_LS == 0) { //Para que no recargue las listas cuando vienen de una llamada de servicio. ?>
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=3&id="+Cliente,
					success: function(response){
						$('#SucursalCliente').html(response).fadeIn();
						$('#SucursalCliente').trigger('change');
					}
				});

				// Stiven Muñoz Murillo, 10/01/2022
				$.ajax({
					type: "POST",
					url: "ajx_cbo_select.php?type=6&id="+Cliente,
					success: function(response){
						$('#id_llamada_servicio').html(response).fadeIn();
						$('#id_llamada_servicio').trigger('change');
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

			// Stiven Muñoz Murillo, 20/01/2022
			$.ajax({
				url:"ajx_buscar_datos_json.php",
				data: {
					type: 45,
					id: Cliente
				},
				dataType:'json',
				success: function(data){
					console.log("677", data);

					document.getElementById('direccion_destino').value=data.Direccion;
					document.getElementById('celular').value=data.Celular;
					document.getElementById('Ciudad').value=data.Ciudad;
					document.getElementById('telefono').value=data.Telefono;
					document.getElementById('correo').value=data.Correo;
				},
				error: function(error) {
					console.error(error.responseText);
				}
			});

			$('.ibox-content').toggleClass('sk-loading',false);
		});
		$("#SucursalCliente").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);

			var Cliente=document.getElementById('id_socio_negocio').value;
			var Sucursal=document.getElementById('SucursalCliente').value;

			if(Sucursal !== "" && Sucursal !== null && Sucursal*1 !== -1) {
				$.ajax({
					url:"ajx_buscar_datos_json.php",
					data:{type:1,CardCode:Cliente,Sucursal:Sucursal},
					dataType:'json',
					success: function(data){
						document.getElementById('direccion_destino').value=data.Direccion;
						document.getElementById('Barrio').value=data.Barrio;
						document.getElementById('Ciudad').value=data.Ciudad;
						document.getElementById('telefono').value=data.TelefonoContacto;
						document.getElementById('correo').value=data.CorreoContacto;
					},
					error: function(error) {
						console.error("#SucursalCliente", error.responseText);
					}
				});
			}

			$('.ibox-content').toggleClass('sk-loading',false);
		});
		$("#ContactoCliente").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var Contacto=document.getElementById('ContactoCliente').value;

			if(Contacto !== "" && Contacto !== null) {
				$.ajax({
					url:"ajx_buscar_datos_json.php",
					data:{type:5,Contacto:Contacto},
					dataType:'json',
					success: function(data){
						document.getElementById('telefono').value=data.Telefono;
						document.getElementById('correo').value=data.Correo;
					},
					error: function(error) {
						console.error("#ContactoCliente", error.responseText);
					}
				});
			}

			$('.ibox-content').toggleClass('sk-loading',false);
		});

		// Stiven Muñoz Murillo, 10/01/2021
		$("#CDU_Marca").change(function(){
			$('.ibox-content').toggleClass('sk-loading',true);
			var marcaVehiculo=document.getElementById('CDU_Marca').value;

			$.ajax({
				type: "POST",
				url: "ajx_cbo_select.php?type=39&id="+marcaVehiculo,
				success: function(response){
					// console.log(response);

					if(borrarLineaModeloVehiculo) {
						$('#CDU_Linea').html(response).fadeIn();
						$('#CDU_Linea').trigger('change');
					} else {
						borrarLineaModeloVehiculo = true;
					}

					$('.ibox-content').toggleClass('sk-loading',false);
				},
				error: function(error) {
					console.error("#CDU_Marca", error.responseText);
					$('.ibox-content').toggleClass('sk-loading',false);
				}
			});
		});

		// Stiven Muñoz Murillo, 19/01/2021
		$("#id_llamada_servicio").change(function() {
			$('.ibox-content').toggleClass('sk-loading',true);

			$.ajax({
				url: "ajx_buscar_datos_json.php",
				data: {
					type: 44,
					id: '',
					ot: document.getElementById('id_llamada_servicio').value
				},
				dataType: 'json',
				success: function(data){
					console.log("Line 806", data);

					document.getElementById('placa').value = data.SerialInterno;
					document.getElementById('VIN').value = data.SerialFabricante;
					document.getElementById('no_motor').value = data.No_Motor;

					document.getElementById('responsable_cliente').value = data.CDU_NombreContacto; // SMM, 15/02/2022

					if(data.CDU_IdMarca !== null) {
						document.getElementById('id_marca').value = data.CDU_IdMarca;
						$('#id_marca').trigger('change');

						borrarLineaModeloVehiculo = false;
						document.getElementById('id_linea').value = data.CDU_IdLinea;
						$('#id_linea').trigger('change');

						document.getElementById('id_annio').value = data.CDU_Ano;
						$('#id_annio').trigger('change');

						document.getElementById('id_color').value = data.CDU_Color;
						$('#id_color').trigger('change');
					}

					$('.ibox-content').toggleClass('sk-loading',false);
				},
				error: function(error) {
					console.error("#id_llamada_servicio", error.responseText);
					$('.ibox-content').toggleClass('sk-loading',false);
				}
			});
		});
	});

function ConsultarDatosCliente(){
	var Cliente=document.getElementById('id_socio_negocio');
	if(Cliente.value!=""){
		self.name='opener';
		remote=open('socios_negocios.php?id='+Base64.encode(Cliente.value)+'&ext=1&tl=1','remote','location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}

// Stiven Muñoz Murillo, 12/01/2022
function ConsultarServicio(){
	var llamada=document.getElementById('id_llamada_servicio');
	if(llamada.value!=""){
		self.name='opener';
		remote=open('llamada_servicio.php?id='+Base64.encode(llamada.value)+'&ext=1&tl=1','remote','location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}

function AgregarArea(){
	var Cliente=document.getElementById('id_socio_negocio');
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
	Swal.fire({
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
                            <a href="#"><?php echo isset($row_Cat['NombreCategoriaPadre']) ? $row_Cat['NombreCategoriaPadre'] : " Formularios"; ?></a>
                        </li>
                        <li class="active">
                            <a href="<?php echo isset($row_Cat['URL']) ? $row_Cat['URL'] . "?id=" . $frm : "consultar_frm_recepcion_vehiculo.php" ?>"><?php echo isset($row_Cat['NombreCategoria']) ? $row_Cat['NombreCategoria'] : "Recepción de vehículos"; ?></a>
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
              <form action="frm_recepcion_vehiculo.php" method="post" class="form-horizontal" enctype="multipart/form-data" id="recepcionForm">
				<!-- IBOX, Inicio -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-user"></i> Datos del propietario</h5>
						 <a class="collapse-link pull-right" style="color: white;">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label"><i onClick="ConsultarDatosCliente();" title="Consultar cliente" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Cliente <span class="text-danger">*</span></label>

								<input name="id_socio_negocio" type="hidden" id="id_socio_negocio" value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['ID_CodigoCliente'];} elseif ($dt_LS == 1) {echo $row_Cliente['CodigoCliente'];}?>">
								<input name="socio_negocio" type="text" required="required" class="form-control" id="socio_negocio" placeholder="Digite para buscar..." <?php if ((($type_frm == 1) && ($row['Cod_Estado'] == '-1')) || ($dt_LS == 1)) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['NombreCliente'];} elseif ($dt_LS == 1) {echo $row_Cliente['NombreCliente'];}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Contacto</label>

								<select name="ContactoCliente" class="form-control" id="ContactoCliente" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<?php if ((($type_frm == 0) || ($sw_error == 1)) && ($dt_LS != 1)) {?><option value="">Seleccione...</option><?php }?>
								<?php if (($type_frm == 1) || ($sw_error == 1) || ($dt_LS == 1)) {while ($row_ContactoCliente = sqlsrv_fetch_array($SQL_ContactoCliente)) {?>
										<option value="<?php echo $row_ContactoCliente['CodigoContacto']; ?>" <?php if ((isset($row['ID_Contacto'])) && (strcmp($row_ContactoCliente['CodigoContacto'], $row['ID_Contacto']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ContactoCliente['ID_Contacto']; ?></option>
								<?php }}?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Sucursal</label>

								<select name="SucursalCliente" class="form-control select2" id="SucursalCliente" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
								<?php if ((($type_frm == 0) || ($sw_error == 1)) && ($dt_LS != 1)) {?><option value="">Seleccione...</option><?php }?>
								<?php if (($type_frm == 1) || ($sw_error == 1) || ($dt_LS == 1)) {while ($row_SucursalCliente = sqlsrv_fetch_array($SQL_SucursalCliente)) {?>
										<option value="<?php echo $row_SucursalCliente['NombreSucursal']; ?>" <?php if ((isset($row['NombreSucursal'])) && (strcmp($row_SucursalCliente['NombreSucursal'], $row['NombreSucursal']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_SucursalCliente['NombreSucursal']; ?></option>
								<?php }}?>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Teléfono <span class="text-danger">*</span></label>

								<input name="telefono" type="text" class="form-control" id="telefono" required="required" maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['TelefonoContacto'];} elseif ($dt_LS == 1) {echo isset($_GET['Telefono']) ? base64_decode($_GET['Telefono']) : "";}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Celular</label>

								<input name="celular" type="text" class="form-control" id="celular" maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['CelularContacto'];} elseif ($dt_LS == 1) {echo isset($_GET['Celular']) ? base64_decode($_GET['Celular']) : "";}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Correo</label>

								<input name="correo" type="text" class="form-control" id="correo" maxlength="100" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['CorreoContacto'];} elseif ($dt_LS == 1) {echo isset($_GET['Correo']) ? base64_decode($_GET['Correo']) : "";}?>">
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Dirección</label>

								<input name="direccion_destino" type="text" class="form-control" id="direccion_destino" maxlength="100" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['Direccion'];} elseif ($dt_LS == 1) {echo isset($_GET['Direccion']) ? base64_decode($_GET['Direccion']) : "";}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Barrio</label>

								<input name="Barrio" type="text" class="form-control" id="Barrio" maxlength="50" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['Barrio'];} elseif ($dt_LS == 1) {echo isset($_GET['Barrio']) ? base64_decode($_GET['Barrio']) : "";}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Ciudad</label>

								<input name="Ciudad" type="text" class="form-control" id="Ciudad" maxlength="100" value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['Ciudad'];} elseif ($dt_LS == 1) {echo base64_decode($_GET['Ciudad']);}?>" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?>>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-8 border-bottom">
								<label class="control-label text-danger">Información del servicio</label>
							</div>
						</div>
						<!-- Orden de servicio, Inicio -->
						<div class="form-group">
							<div class="col-lg-8">
								<label class="control-label"><i onClick="ConsultarServicio();" title="Consultar llamada de servicio" style="cursor: pointer" class="btn-xs btn-success fa fa-search"></i> Orden servicio <span class="text-danger">*</span></label>

								<select name="id_llamada_servicio" class="form-control select2" required="required" id="id_llamada_servicio" <?php if ($dt_LS == 1) {echo "disabled='disabled'";}?>>
									<?php if ($dt_LS != 1) {?><option value="">(Ninguna)</option><?php }?>
									<?php if ($sw_error == 1 || $dt_LS == 1 || $type_llmd == 1) {while ($row_OrdenServicioCliente = sqlsrv_fetch_array($SQL_OrdenServicioCliente)) {?>
										<option value="<?php echo $row_OrdenServicioCliente['ID_LlamadaServicio']; ?>" <?php if ((isset($row['ID_OrdenServicioActividad'])) && (strcmp($row_OrdenServicioCliente['ID_LlamadaServicio'], $row['ID_LlamadaServicio']) == 0)) {echo "selected=\"selected\"";} elseif (isset($_GET['LS']) && (strcmp($row_OrdenServicioCliente['ID_LlamadaServicio'], base64_decode($_GET['LS'])) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_OrdenServicioCliente['DocNum'] . " - " . $row_OrdenServicioCliente['AsuntoLlamada']; ?></option>
								  <?php }}?>
								</select>
							</div>
						</div>
						<!-- Orden de servicio, Fin -->
					</div>
				</div>
				<!-- IBOX, Fin -->
				<!-- IBOX, Inicio -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-car"></i> Datos del vehículo</h5>
						 <a class="collapse-link pull-right" style="color: white;">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Serial Interno (Placa) <span class="text-danger">*</span></label>
								<input <?php if ($dt_LS == 1) {echo "readonly='readonly'";}?> autocomplete="off" name="placa" type="text" required="required" class="form-control" id="placa" maxlength="150" value="<?php if (isset($row['SerialInterno'])) {echo $row['SerialInterno'];}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">Serial Fabricante (VIN) <span class="text-danger">*</span></label>
								<input <?php if ($dt_LS == 1) {echo "readonly='readonly'";}?> autocomplete="off" name="VIN" type="text" required="required" class="form-control" id="VIN" maxlength="150" value="<?php if (isset($row['SerialFabricante'])) {echo $row['SerialFabricante'];}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">No_Motor <span class="text-danger">*</span></label>
								<input autocomplete="off" name="no_motor" type="text" required="required" class="form-control" id="no_motor" maxlength="100"
								value="<?php if (isset($row['No_Motor'])) {echo $row['No_Motor'];}?>">
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Marca del vehículo <span class="text-danger">*</span></label>
								<select name="id_marca" class="form-control select2" required="required" id="id_marca"
								<?php if ($dt_LS == 1) {echo "disabled='disabled'";}?>>
									<option value="" disabled selected>Seleccione...</option>
								  <?php while ($row_MarcaVehiculo = sqlsrv_fetch_array($SQL_MarcaVehiculo)) {?>
									<option value="<?php echo $row_MarcaVehiculo['IdMarcaVehiculo']; ?>"
									<?php if ((isset($row['CDU_IdMarca'])) && (strcmp($row_MarcaVehiculo['IdMarcaVehiculo'], $row['CDU_IdMarca']) == 0)) {echo "selected=\"selected\"";}?>>
										<?php echo $row_MarcaVehiculo['DeMarcaVehiculo']; ?>
									</option>
								  <?php }?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Línea del vehículo <span class="text-danger">*</span></label>
								<select name="id_linea" class="form-control select2" required="required" id="id_linea"
								<?php if ($dt_LS == 1) {echo "disabled='disabled'";}?>>
										<option value="" disabled selected>Seleccione...</option>
								  <?php while ($row_LineaVehiculo = sqlsrv_fetch_array($SQL_LineaVehiculo)) {?>
										<option value="<?php echo $row_LineaVehiculo['IdLineaModeloVehiculo']; ?>"
										<?php if ((isset($row['CDU_IdLinea'])) && (strcmp($row_LineaVehiculo['IdLineaModeloVehiculo'], $row['CDU_IdLinea']) == 0)) {echo "selected=\"selected\"";}?>>
											<?php echo $row_LineaVehiculo['DeLineaModeloVehiculo']; ?>
										</option>
								  <?php }?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Modelo del vehículo <span class="text-danger">*</span></label>
								<select name="id_annio" class="form-control select2" required="required" id="id_annio"
								<?php if ($dt_LS == 1) {echo "disabled='disabled'";}?>>
										<option value="" disabled selected>Seleccione...</option>
								  <?php while ($row_ModeloVehiculo = sqlsrv_fetch_array($SQL_ModeloVehiculo)) {?>
										<option value="<?php echo $row_ModeloVehiculo['CodigoModeloVehiculo']; ?>"
										<?php if ((isset($row['CDU_Ano'])) && ((strcmp($row_ModeloVehiculo['CodigoModeloVehiculo'], $row['CDU_Ano']) == 0) || (strcmp($row_ModeloVehiculo['AñoModeloVehiculo'], $row['CDU_Ano']) == 0))) {echo "selected=\"selected\"";}?>>
											<?php echo $row_ModeloVehiculo['AñoModeloVehiculo']; ?>
										</option>
								  <?php }?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Color <span class="text-danger">*</span></label>
								<select name="id_color" class="form-control select2" required="required" id="id_color"
								<?php if ($dt_LS == 1) {echo "disabled='disabled'";}?>>
										<option value="" disabled selected>Seleccione...</option>
								  <?php while ($row_ColorVehiculo = sqlsrv_fetch_array($SQL_ColorVehiculo)) {?>
										<option value="<?php echo $row_ColorVehiculo['CodigoColorVehiculo']; ?>"
										<?php if ((isset($row['CDU_Color'])) && ((strcmp($row_ColorVehiculo['CodigoColorVehiculo'], $row['CDU_Color']) == 0) || (strcmp($row_ColorVehiculo['NombreColorVehiculo'], $row['CDU_Color']) == 0))) {echo "selected=\"selected\"";}?>>
											<?php echo $row_ColorVehiculo['NombreColorVehiculo']; ?>
										</option>
								  <?php }?>
								</select>
							</div>
						</div>
					</div>
				</div>
				<!-- IBOX, Fin -->
				<!-- IBOX, Inicio -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-info-circle"></i> Datos de recepción</h5>
						 <a class="collapse-link pull-right" style="color: white;">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Servicio de movilidad ofrecido</label>
								<select name="servicio_movil_ofrecido" class="form-control" id="servicio_movil_ofrecido" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
										<option value="SI">SI</option>
										<option value="NO">NO</option>
								<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
										<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
								<?php //}?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Se hizo prueba de ruta</label>
								<select name="hizo_prueba_ruta" class="form-control" id="hizo_prueba_ruta" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
										<option value="SI">SI</option>
										<option value="NO">NO</option>
								<?php //while ($row_EstadoLlamada = sqlsrv_fetch_array($SQL_EstadoLlamada)) {?>
										<!--option value="<?php echo $row_EstadoLlamada['Cod_Estado']; ?>" <?php if ((isset($row['Cod_Estado'])) && (strcmp($row_EstadoLlamada['Cod_Estado'], $row['Cod_Estado']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_EstadoLlamada['NombreEstado']; ?></option -->
								<?php //}?>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Campaña autorizada por cliente</label>
								<select name="campana_autorizada_cliente" class="form-control" id="campana_autorizada_cliente" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
									<option value="SI">SI</option>
									<option value="NO">NO</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">Nivel de combustible</label>
								<select name="nivel_combustible" class="form-control" id="nivel_combustible" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
									<option value="1/4">1/4</option>
									<option value="1/2">1/2</option>
									<option value="3/4">3/4</option>
									<option value="Full">Full</option>
								</select>
							</div>
							<div class="col-lg-4">
								<label class="control-label">Medio por el cual se informo campaña</label>
								<select name="medio_informa_campana" class="form-control" id="medio_informa_campana" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "disabled='disabled'";}?>>
										<option value="N/A">N/A</option>
								</select>
							</div>
						</div>
						<div class="form-group">
							<div class="col-lg-4">
								<label class="control-label">KM actual <span class="text-danger">*</span></label>
								<input autocomplete="off" name="km_actual" required="required" type="text" class="form-control" id="km_actual" maxlength="100"
								value="<?php if (isset($row['CDU_No_Motor'])) {echo $row['CDU_No_Motor'];}?>">
							</div>
							<div class="col-lg-4">
								<label class="control-label">No. Campaña <span class="text-danger">*</span></label>
								<input autocomplete="off" name="no_campana" required="required" type="text" class="form-control" id="no_campana" maxlength="100"
								value="<?php if (isset($row['CDU_No_Motor'])) {echo $row['CDU_No_Motor'];}?>">
							</div>
						</div>
						<!-- Inicio, crono-info -->
						<div class="form-group">
							<div class="col-lg-4 border-bottom ">
								<label class="control-label text-danger">Información cronológica de la recepción</label>
							</div>
						</div>
						<div class="form-group">
							<!-- Inicio, Componente Fecha y Hora -->
							<div class="col-lg-6">
								<div class="row">
									<label class="col-lg-6 control-label" style="text-align: left !important;">Fecha y hora de creación <span class="text-danger">*</span></label>
								</div>
								<div class="row">
									<div class="col-lg-6 input-group date">
										<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="FechaCreacion" type="text" autocomplete="off" class="form-control" id="FechaCreacion" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('Y-m-d');} else {echo date('Y-m-d');}?>" readonly='readonly' placeholder="YYYY-MM-DD" required>
									</div>
									<div class="col-lg-6 input-group clockpicker" data-autoclose="true">
										<input name="HoraCreacion" id="HoraCreacion" type="text" autocomplete="off" class="form-control" value="<?php if (($type_frm == 1) && ($row['FechaCreacion']->format('Y-m-d')) != "1900-01-01") {echo $row['FechaCreacion']->format('H:i');} else {echo date('H:i');}?>" readonly='readonly' placeholder="hh:mm" required>
										<span class="input-group-addon">
											<span class="fa fa-clock-o"></span>
										</span>
									</div>
								</div>
							</div>
							<!-- Fin, Componente Fecha y Hora -->
							<!-- Inicio, Componente Fecha y Hora -->
							<div class="col-lg-6">
								<div class="row">
									<label class="col-lg-6 control-label" style="text-align: left !important;">Fecha y hora de ingreso <span class="text-danger">*</span></label>
								</div>
								<div class="row">
									<div class="col-lg-6 input-group date">
										<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="fecha_ingreso" type="text" autocomplete="off" class="form-control" id="fecha_ingreso" value="<?php if (($type_frm == 1) && ($row['fecha_ingreso']->format('Y-m-d')) != "1900-01-01") {echo $row['fecha_ingreso']->format('Y-m-d');} //else {echo date('Y-m-d');}?>" placeholder="YYYY-MM-DD" required>
									</div>
									<div class="col-lg-6 input-group clockpicker" data-autoclose="true">
										<input name="hora_ingreso" id="hora_ingreso" type="text" autocomplete="off" class="form-control" value="<?php if (($type_frm == 1) && ($row['fecha_ingreso']->format('Y-m-d')) != "1900-01-01") {echo $row['fecha_ingreso']->format('H:i');} //else {echo date('H:i');}?>" placeholder="hh:mm" required>
										<span class="input-group-addon">
											<span class="fa fa-clock-o"></span>
										</span>
									</div>
								</div>
							</div>
							<!-- Fin, Componente Fecha y Hora -->
						</div>
						<div class="form-group">
							<!-- Inicio, Componente Fecha y Hora -->
							<div class="col-lg-6">
								<div class="row">
									<label class="col-lg-6 control-label" style="text-align: left !important;">Fecha y hora Aprox. Entrega <span class="text-danger">*</span></label>
								</div>
								<div class="row">
									<div class="col-lg-6 input-group date">
										<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="fecha_aprox_entrega" type="text" autocomplete="off" class="form-control" id="fecha_aprox_entrega" value="<?php if (($type_frm == 1) && ($row['fecha_aprox_entrega']->format('Y-m-d')) != "1900-01-01") {echo $row['fecha_aprox_entrega']->format('Y-m-d');} else {echo date('Y-m-d');}?>" placeholder="YYYY-MM-DD" required>
									</div>
									<div class="col-lg-6 input-group clockpicker" data-autoclose="true">
										<input name="hora_aprox_entrega" id="hora_aprox_entrega" type="text" autocomplete="off" class="form-control" value="<?php if (($type_frm == 1) && ($row['fecha_aprox_entrega']->format('Y-m-d')) != "1900-01-01") {echo $row['fecha_aprox_entrega']->format('H:i');} else {echo date('H:i');}?>" placeholder="hh:mm" required>
										<span class="input-group-addon">
											<span class="fa fa-clock-o"></span>
										</span>
									</div>
								</div>
							</div>
							<!-- Fin, Componente Fecha y Hora -->
							<!-- Inicio, Componente Fecha y Hora -->
							<div class="col-lg-6">
								<div class="row">
									<label class="col-lg-6 control-label" style="text-align: left !important;">Fecha hora propietario autoriza campaña</label>
								</div>
								<div class="row">
									<div class="col-lg-6 input-group date">
										<span class="input-group-addon"><i class="fa fa-calendar"></i></span><input name="fecha_autoriza_campana" type="text" autocomplete="off" class="form-control" id="fecha_autoriza_campana" value="<?php if (($type_frm == 1) && ($row['fecha_autoriza_campana']->format('Y-m-d')) != "1900-01-01") {echo $row['fecha_autoriza_campana']->format('Y-m-d');} //else {echo date('Y-m-d');}?>" placeholder="YYYY-MM-DD">
									</div>
									<div class="col-lg-6 input-group clockpicker" data-autoclose="true">
										<input name="hora_autoriza_campana" id="hora_autoriza_campana" type="text" autocomplete="off" class="form-control" value="<?php if (($type_frm == 1) && ($row['fecha_autoriza_campana']->format('Y-m-d')) != "1900-01-01") {echo $row['fecha_autoriza_campana']->format('H:i');} //else {echo date('H:i');}?>" placeholder="hh:mm">
										<span class="input-group-addon">
											<span class="fa fa-clock-o"></span>
										</span>
									</div>
								</div>
							</div>
							<!-- Fin, Componente Fecha y Hora -->
						</div>
						<!-- Fin, crono-info -->
					</div>
				</div>
				<!-- IBOX, Fin -->
				<!-- IBOX, Inicio -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-list"></i> Datos piezas de vehículo</h5>
						 <a class="collapse-link pull-right" style="color: white;">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<?php $count_rp = 0;?>

						<?php while ($row_Pregunta = sqlsrv_fetch_array($SQL_Preguntas)) {?>
							<?php $count_rp++;?>

							<input type="hidden" name="<?php echo "id_pregunta_$count_rp"; ?>" id="<?php echo "id_pregunta_$count_rp"; ?>" value="<?php echo $row_Pregunta['id_recepcion_pregunta']; ?>">
							<input type="hidden" name="<?php echo "pregunta_$count_rp"; ?>" id="<?php echo "pregunta_$count_rp"; ?>" value="<?php echo $row_Pregunta['recepcion_pregunta']; ?>">


							<div class="form-group">
								<div class="col-lg-4 border-bottom ">
									<label class="control-label text-danger"><?php echo $row_Pregunta['recepcion_pregunta']; ?></label>
								</div>
							</div>
							<div class="form-group">
								<label class="col-lg-1 control-label">Disponibilidad</label>
								<div class="col-lg-2">
									<select class="form-control" name="<?php echo "p_disponible_$count_rp"; ?>" id="<?php echo "p_disponible_$count_rp"; ?>" <?php if (false) {echo "disabled='disabled'";}?>>
										<option value="SI" <?php if ((isset($row["p_disponible_$count_rp"])) && (strcmp("si", $row["p_disponible_$count_rp"]) == 0)) {echo "selected=\"selected\"";}?>>
											Si
										</option>
										<option value="NO" <?php if ((isset($row["p_disponible_$count_rp"])) && (strcmp("no", $row["p_disponible_$count_rp"]) == 0)) {echo "selected=\"selected\"";}?>>
											No
										</option>
									</select>
								</div>
								<label class="col-lg-1 control-label">Estado</label>
								<div class="col-lg-2">
									<select class="form-control" name="<?php echo "p_estado_$count_rp"; ?>" id="<?php echo "p_estado_$count_rp"; ?>" <?php if (false) {echo "disabled='disabled'";}?>>
										<option value="BUENO" <?php if ((isset($row["p_estado_$count_rp"])) && (strcmp("BUENO", $row["p_estado_$count_rp"]) == 0)) {echo "selected=\"selected\"";}?>>
											Bueno
										</option>
										<option value="MALO" <?php if ((isset($row["p_estado_$count_rp"])) && (strcmp("MALO", $row["p_estado_$count_rp"]) == 0)) {echo "selected=\"selected\"";}?>>
											Malo
										</option>
									</select>
								</div>
							</div>
						<?php }?>
					</div>
				</div>
				<!-- IBOX, Fin -->
				<!-- IBOX, Inicio -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-image"></i> Registros fotográficos</h5>
						 <a class="collapse-link pull-right" style="color: white;">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<!-- Inicio, Foto 1 -->
						<div class="form-group">
							<label class="col-lg-1 control-label">Frente <span class="text-danger">*</span></label>
							<div class="col-lg-5">
								<div class="fileinput fileinput-new input-group" data-provides="fileinput">
									<div class="form-control" data-trigger="fileinput">
										<i class="glyphicon glyphicon-file fileinput-exists"></i>
									<span class="fileinput-filename"></span>
									</div>
									<span class="input-group-addon btn btn-default btn-file">
										<span class="fileinput-new">Seleccionar</span>
										<span class="fileinput-exists">Cambiar</span>
										<input name="Img1" type="file" id="Img1" onchange="uploadImage('Img1')" required="required"/>
									</span>
									<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
								</div>
								<div class="row">
									<div id="msgImg1" style="display:none" class="alert alert-info">
										<i class="fa fa-info-circle"></i> <span>Imagen cargada éxitosamente.<span>
									</div>
								</div>
							</div>
							<div class="col-lg-5">
								<img id="viewImg1" style="max-width: 100%; height: 100px;" src="">
							</div>
						</div>
						<!-- Inicio, Foto 1 -->
						<!-- Inicio, Foto 2 -->
						<div class="form-group">
							<label class="col-lg-1 control-label">Lateral Izquierdo <span class="text-danger">*</span></label>
							<div class="col-lg-5">
								<div class="fileinput fileinput-new input-group" data-provides="fileinput">
									<div class="form-control" data-trigger="fileinput">
										<i class="glyphicon glyphicon-file fileinput-exists"></i>
									<span class="fileinput-filename"></span>
									</div>
									<span class="input-group-addon btn btn-default btn-file">
										<span class="fileinput-new">Seleccionar</span>
										<span class="fileinput-exists">Cambiar</span>
										<input name="Img2" type="file" id="Img2" onchange="uploadImage('Img2')" required="required"/>
									</span>
									<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
								</div>
								<div class="row">
									<div id="msgImg2" style="display:none" class="alert alert-info">
										<i class="fa fa-info-circle"></i> <span>Imagen cargada éxitosamente.<span>
									</div>
								</div>
							</div>
							<div class="col-lg-5">
								<img id="viewImg2" style="max-width: 100%; height: 100px;" src="">
							</div>
						</div>
						<!-- Fin, Foto 2 -->
						<!-- Inicio, Foto 3 -->
						<div class="form-group">
							<label class="col-lg-1 control-label">Lateral Derecho <span class="text-danger">*</span></label>
							<div class="col-lg-5">
								<div class="fileinput fileinput-new input-group" data-provides="fileinput">
									<div class="form-control" data-trigger="fileinput">
										<i class="glyphicon glyphicon-file fileinput-exists"></i>
									<span class="fileinput-filename"></span>
									</div>
									<span class="input-group-addon btn btn-default btn-file">
										<span class="fileinput-new">Seleccionar</span>
										<span class="fileinput-exists">Cambiar</span>
										<input name="Img3" type="file" id="Img3" onchange="uploadImage('Img3')" required="required"/>
									</span>
									<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
								</div>
								<div class="row">
									<div id="msgImg3" style="display:none" class="alert alert-info">
										<i class="fa fa-info-circle"></i> <span>Imagen cargada éxitosamente.<span>
									</div>
								</div>
							</div>
							<div class="col-lg-5">
								<img id="viewImg3" style="max-width: 100%; height: 100px;" src="">
							</div>
						</div>
						<!-- Fin, Foto 3 -->
						<!-- Inicio, Foto 4 -->
						<div class="form-group">
							<label class="col-lg-1 control-label">Trasero <span class="text-danger">*</span></label>
							<div class="col-lg-5">
								<div class="fileinput fileinput-new input-group" data-provides="fileinput">
									<div class="form-control" data-trigger="fileinput">
										<i class="glyphicon glyphicon-file fileinput-exists"></i>
									<span class="fileinput-filename"></span>
									</div>
									<span class="input-group-addon btn btn-default btn-file">
										<span class="fileinput-new">Seleccionar</span>
										<span class="fileinput-exists">Cambiar</span>
										<input name="Img4" type="file" id="Img4" onchange="uploadImage('Img4')" required="required"/>
									</span>
									<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
								</div>
								<div class="row">
									<div id="msgImg4" style="display:none" class="alert alert-info">
										<i class="fa fa-info-circle"></i> <span>Imagen cargada éxitosamente.<span>
									</div>
								</div>
							</div>
							<div class="col-lg-5">
								<img id="viewImg4" style="max-width: 100%; height: 100px;" src="">
							</div>
						</div>
						<!-- Fin, Foto 4 -->
						<!-- Inicio, Foto 5 -->
						<div class="form-group">
							<label class="col-lg-1 control-label">Capot <span class="text-danger">*</span></label>
							<div class="col-lg-5">
								<div class="fileinput fileinput-new input-group" data-provides="fileinput">
									<div class="form-control" data-trigger="fileinput">
										<i class="glyphicon glyphicon-file fileinput-exists"></i>
									<span class="fileinput-filename"></span>
									</div>
									<span class="input-group-addon btn btn-default btn-file">
										<span class="fileinput-new">Seleccionar</span>
										<span class="fileinput-exists">Cambiar</span>
										<input name="Img5" type="file" id="Img5" onchange="uploadImage('Img5')" required="required"/>
									</span>
									<a href="#" class="input-group-addon btn btn-default fileinput-exists" data-dismiss="fileinput">Quitar</a>
								</div>
								<div class="row">
									<div id="msgImg5" style="display:none" class="alert alert-info">
										<i class="fa fa-info-circle"></i> <span>Imagen cargada éxitosamente.<span>
									</div>
								</div>
							</div>
							<div class="col-lg-5">
								<img id="viewImg5" style="max-width: 100%; height: 100px;" src="">
							</div>
						</div>
						<!-- Fin, Foto 5 -->
						<div class="form-group">
							<label class="col-lg-1 control-label">Observaciones <span class="text-danger">*</span></label>
							<div class="col-lg-8">
								<textarea name="observaciones" rows="5" type="text" class="form-control" required="required" id="texto_condiciones" <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?>><?php if (($type_frm == 1) || ($sw_error == 1)) {echo utf8_decode($row['ComentariosCierre']);}?></textarea>
							</div>
						</div>
					</div>
				</div>
				<!-- IBOX, Fin -->
				<!-- IBOX, Inicio -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-pencil-square-o"></i> Firmas</h5>
						 <a class="collapse-link pull-right" style="color: white;">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<div class="form-group">
							<label class="col-lg-1 control-label">Responsable del cliente <span class="text-danger">*</span></label>
							<div class="col-lg-4">
								<input autocomplete="off" name="responsable_cliente" type="text" class="form-control" required="required" id="responsable_cliente"  <?php if (($type_frm == 1) && ($row['Cod_Estado'] == '-1')) {echo "readonly='readonly'";}?> value="<?php if (($type_frm == 1) || ($sw_error == 1)) {echo $row['ResponsableCliente'];}?>">
							</div>
						</div>
						<div class="form-group">
							<label class="col-lg-1 control-label">Firma del cliente <span class="text-danger">*</span></label>
							<?php if ($type_frm == 1 && $row['FirmaCliente'] != "") {?>
							<div class="col-lg-4 lightBoxGallery">
								<a href="<?php echo $dir_new . $row['FirmaCliente']; ?>" title="Firma cliente" data-gallery=""><img src="<?php echo $dir_new . $row['FirmaCliente']; ?>" width="500" height="150"></a>
								<div id="blueimp-gallery" class="blueimp-gallery">
									<div class="slides"></div>
									<h3 class="title"></h3>
									<a class="prev">‹</a>
									<a class="next">›</a>
									<a class="close">×</a>
									<a class="play-pause"></a>
									<ol class="indicator"></ol>
								</div>
							</div>
							<?php } else {//LimpiarDirTempFirma();?>
							<div class="col-lg-5">
								<button class="btn btn-primary" type="button" id="FirmaCliente" onClick="AbrirFirma('SigCliente');"><i class="fa fa-pencil-square-o"></i> Realizar firma</button>
								<br><br>
								<input type="text" id="SigCliente" name="SigCliente" value="" required="required" readonly="readonly"/>
								<div id="msgInfoSigCliente" style="display: none;" class="alert alert-info"><i class="fa fa-info-circle"></i> El documento ya ha sido firmado.</div>
							</div>
							<div class="col-lg-5">
								<img id="ImgSigCliente" style="display: none; max-width: 100%; height: auto;" src="" alt="" />
							</div>
							<?php }?>
						</div>
						<!-- div class="form-group">
							<label class="col-lg-1 control-label">Firma del técnico</label>
							<?php if ($type_frm == 1 && $row['FirmaTecnico'] != "") {?>
							<div class="col-lg-4 lightBoxGallery">
								<a href="<?php echo $dir_new . $row['FirmaTecnico']; ?>" title="Firma tecnico" data-gallery=""><img src="<?php echo $dir_new . $row['FirmaTecnico']; ?>" width="500" height="150"></a>
								<div id="blueimp-gallery" class="blueimp-gallery">
									<div class="slides"></div>
									<h3 class="title"></h3>
									<a class="prev">‹</a>
									<a class="next">›</a>
									<a class="close">×</a>
									<a class="play-pause"></a>
									<ol class="indicator"></ol>
								</div>
							</div>
							<?php } else {?>
							<div class="col-lg-5">
								<button class="btn btn-primary" type="button" id="FirmaTecnico" onClick="AbrirFirma('SigTecnico');"><i class="fa fa-pencil-square-o"></i> Realizar firma</button>
								<input type="hidden" id="SigTecnico" name="SigTecnico" value="" />
								<div id="msgInfoSigTecnico" style="display: none;" class="alert alert-info"><i class="fa fa-info-circle"></i> El documento ya ha sido firmado.</div>
							</div>
							<div class="col-lg-5">
								<img id="ImgSigTecnico" style="display: none; max-width: 100%; height: auto;" src="" alt="" />
							</div>
							<?php }?>
						</div -->
					</div>
				</div>
				<!-- IBOX, Fin -->

				<?php
$EliminaMsg = array("&a=" . base64_encode("OK_FrmAdd"), "&a=" . base64_encode("OK_FrmUpd"), "&a=" . base64_encode("OK_FrmDel")); //Eliminar mensajes

if (isset($_GET['return'])) {
    $_GET['return'] = str_replace($EliminaMsg, "", base64_decode($_GET['return']));
}
if (isset($_GET['return'])) {
    $return = base64_decode($_GET['pag']) . "?" . $_GET['return'];
} else {
    // Stiven Muñoz Murillo, 10/01/2022
    $return = "consultar_frm_recepcion_vehiculo.php?id=" . $frm;
}?>

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
					<h5 class="collapse-link"><i class="fa fa-paperclip"></i> Fotos adicionales</h5>
					<a class="collapse-link pull-right" style="color: white;">
						<i class="fa fa-chevron-up"></i>
					</a>
				</div>
				<div class="ibox-content">
					<?php if ( /*$row['IdAnexoLlamada'] != 0*/false) {?>
						<div class="form-group">
							<div class="col-xs-12">
								<?php while ($row_AnexoLlamada = sqlsrv_fetch_array($SQL_AnexoLlamada)) {?>
									<?php $Icon = IconAttach($row_AnexoLlamada['FileExt']);?>
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
					<?php } else {echo "<!--p>Sin anexos.</p-->";}?>
					<div class="row">
						<!-- form action="upload.php" class="dropzone" id="dropzoneForm" name="dropzoneForm">
							<?php //if ($sw_error == 0) {LimpiarDirTemp();}?>
							<div class="fallback">
								<input name="File" id="File" type="file" form="dropzoneForm" />
							</div>
						</form -->
					</div>
				</div>
			</div>
			<!-- Fin Anexos -->

			<!-- Botones de acción al final del formulario, SMM -->
			   <div class="form-group">
					<div class="col-lg-9">
						 <br><br>
						<?php if (($type_frm == 1) && (PermitirFuncion(107) && (($row['Cod_Estado'] == '-3') || ($row['Cod_Estado'] == '-2')))) {?>
							<button class="btn btn-warning" type="submit" form="recepcionForm" id="Actualizar"><i class="fa fa-refresh"></i> Actualizar formulario</button>
						<?php }?>
						<?php if ($type_frm == 0) {?>
							<button class="btn btn-primary" form="recepcionForm" type="submit" id="Crear"><i class="fa fa-check"></i> Registrar formulario</button>
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
var photos = []; // SMM, 11/02/2022

// Stiven Muñoz Murillo, 11/01/2022
function uploadImage(refImage) {
	$('.ibox-content').toggleClass('sk-loading', true); // Carga iniciada.

	var formData = new FormData();
	var file = $(`#${refImage}`)[0].files[0];

	console.log("1664", file);
	formData.append('image', file);

	if(typeof file !== 'undefined'){
		fileSize = returnFileSize(file.size)

		if(fileSize.heavy) {
			console.error("Heavy");

			mostrarAlerta(`msg${refImage}`, 'danger', `La imagen no puede superar los 2MB, actualmente pesa ${fileSize.size}`);
			$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
		} else {
			// Inicio, AJAX
			$.ajax({
				url: 'upload_image.php',
				type: 'post',
				data: formData,
				contentType: false,
				processData: false,
				success: function(response) {
					json_response = JSON.parse(response);

					photo_name = json_response.nombre;
					photo_route = json_response.directorio + photo_name;

					testImage(photo_route).then(success => {
						console.log(success);
						console.log("1684", photo_route);

						photos[refImage] = photo_name; // SMM, 11/02/2022

						$(`#view${refImage}`).attr("src", photo_route);
						mostrarAlerta(`msg${refImage}`, 'info', `Imagen cargada éxitosamente con un peso de ${fileSize.size}`);
					})
					.catch(error => {
						console.error(error);
						console.error(response);

						mostrarAlerta(`msg${refImage}`, 'danger', 'Error al cargar la imagen.');
					});

					$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
				},
				error: function(response) {
					console.error("server error")
					console.error(response);

					mostrarAlerta(`msg${refImage}`, 'danger', 'Error al cargar la imagen en el servidor.');
					$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
				}
			});
			// Fin, AJAX
		}
	} else {
		console.log("Ninguna imagen seleccionada");

		$(`#msg${refImage}`).css("display", "none");
		$(`#view${refImage}`).attr("src", "");

		$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
	}
	return false;
}

// Stiven Muñoz Murillo, 13/01/2022
function mostrarAlerta(id, tipo, mensaje) {
	$(`#${id}`).attr("class", `alert alert-${tipo}`);
	$(`#${id} span`).text(mensaje);
	$(`#${id}`).css("display", "inherit");
}

function returnFileSize(number) {
	if (number < 1024) {
        return { heavy: false, size: (number + 'bytes') };
    } else if (number >= 1024 && number < 1048576) {
		number = (number / 1024).toFixed(1);
        return { heavy: false, size: (number + 'KB') };
    } else if (number >= 1048576) {
		number = (number / 1048576).toFixed(1);
		if(number > 2) {
			return { heavy: true, size: (number + 'MB') };
		} else {
			return { heavy: false, size: (number + 'MB') };
		}
    } else {
		return { heavy: true, size: Infinity }
	}
}

// Reference, https://stackoverflow.com/questions/9714525/javascript-image-url-verify
function testImage(url, timeoutT) {
    return new Promise(function (resolve, reject) {
        var timeout = timeoutT || 5000;
        var timer, img = new Image();
        img.onerror = img.onabort = function () {
            clearTimeout(timer);
            reject("error loading image");
        };
        img.onload = function () {
            clearTimeout(timer);
            resolve("image loaded successfully");
        };
        timer = setTimeout(function () {
            // reset .src to invalid URL so it stops previous
            // loading, but doesn't trigger new load
            img.src = "//!!!!/test.jpg";
            reject("timeout");
        }, timeout);
        img.src = url;
    });
}
</script>

<script>
$(document).ready(function(){
	$('#recepcionForm').on('submit', function (event) {
		event.preventDefault();
	});

	$("#recepcionForm").validate({
		submitHandler: function(form){
			// Stiven Muñoz Murillo, 08/02/2022
			// $('.ibox-content').toggleClass('sk-loading');
			// form.submit();

			let formData = new FormData(form);
			Object.entries(photos).forEach(([key, value]) => formData.append(key, value));

			// Agregar valores de las listas
			formData.append("id_llamada_servicio", $("#id_llamada_servicio").val());
			formData.append("id_marca", $("#id_marca").val());
			formData.append("id_linea", $("#id_marca").val());
			formData.append("id_annio", $("#id_marca").val());
			formData.append("id_color", $("#id_marca").val());
			

			let json = Object.fromEntries(formData);
			localStorage.recepcionForm = JSON.stringify(json);

			console.log("1790", json);

			// Inicio, AJAX
			$.ajax({
				url: 'frm_recepcion_vehiculo_ws.php',
				type: 'POST',
				data: formData,
				processData: false,  // tell jQuery not to process the data
  				contentType: false,   // tell jQuery not to set contentType
				success: function(response) {
					console.log("1805", response);
					Swal.fire(JSON.parse(response));
				},
				error: function(response) {
					console.error("server error")
					console.error(response);
					// $('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
				}
			});
			// Fin, AJAX
		}
	});
		 $(".alkin").on('click', function(){
				 $('.ibox-content').toggleClass('sk-loading');
			});

			// @autor SMM
			// @version 15/02/2022

			// Inicio, Sección de fechas y horas.
			if(!$('#fecha_ingreso').prop('readonly')){
				$('#fecha_ingreso').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					calendarWeeks: true,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true,
					endDate: '<?php echo date('Y-m-d'); ?>'
				});

				$('#hora_ingreso').clockpicker({
					donetext: 'Done'
				});
			}
			if(!$('#fecha_autoriza_campana').prop('readonly')){
				$('#fecha_autoriza_campana').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					calendarWeeks: true,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true
				});

				$('#hora_autoriza_campana').clockpicker({
					donetext: 'Done'
				});
			}
			if(!$('#fecha_aprox_entrega').prop('readonly')){
				$('#fecha_aprox_entrega').datepicker({
					todayBtn: "linked",
					keyboardNavigation: false,
					forceParse: false,
					calendarWeeks: true,
					autoclose: true,
					format: 'yyyy-mm-dd',
					todayHighlight: true
				});

				$('#hora_aprox_entrega').clockpicker({
					donetext: 'Done'
				});
			}
			// Fin, Sección de fechas y horas.


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
					var value = $("#socio_negocio").getSelectedItemData().CodigoCliente;
					$("#id_socio_negocio").val(value).trigger("change");
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

		$("#socio_negocio").easyAutocomplete(options);
		$("#Ciudad").easyAutocomplete(options2);

		<?php if ($dt_LS == 1) {?>
			$('#id_socio_negocio').trigger('change');

			// Stiven Muñoz Murillo, 20/01/2022
			$('#id_llamada_servicio').trigger('change');
	 	<?php }?>

		$(".btn_del").each(function (el){
			 $(this).bind("click",delRow);
		 });

		  <?php
if (($type_frm == 1) && (!PermitirFuncion(213))) {?>
				//$('#id_socio_negocioActividad option:not(:selected)').attr('disabled',true);
		 		$('#Revision option:not(:selected)').attr('disabled',true);
		<?php }?>
		 <?php
if ($dt_LS == 1) {?>
				//$('#id_socio_negocioActividad option:not(:selected)').attr('disabled',true);
		 		$('#SucursalCliente option:not(:selected)').attr('disabled',true);
		 		$('#id_llamada_servicio option:not(:selected)').attr('disabled',true);
		<?php }?>
	});
</script>
<?php if ($type_frm == 1) {?>
<script>
function Reabrir(){
	Swal.fire({
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