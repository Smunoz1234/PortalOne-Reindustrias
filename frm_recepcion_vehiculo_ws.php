<?php require_once "includes/conexion.php";

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
$dir_name = "recepcion_vehiculos";
$dir_new = CrearObtenerDirAnx($dir_name);

//echo $dir . '<br>';
//echo $dir_firma . '<br>';
//echo $dir_new . '<br>';

if (isset($_POST['P']) && ($_POST['P'] == base64_encode('MM_frmHallazgos'))) {

    // Inicio, Enviar datos al WebService
    $Cabecera = $_POST;
    $return = base64_decode($Cabecera["return"]);

    unset($Cabecera["return"]);
    unset($Cabecera["SucursalCliente"]);
    unset($Cabecera["ContactoCliente"]);
    unset($Cabecera["Barrio"]);
    unset($Cabecera["Ciudad"]);
    unset($Cabecera["FechaCreacion"]);
    unset($Cabecera["HoraCreacion"]);
    unset($Cabecera["ResponsableCliente"]);
    unset($Cabecera["P"]);
    unset($Cabecera["swTipo"]);
    unset($Cabecera["swError"]);
    unset($Cabecera["tl"]);
    unset($Cabecera["d_LS"]);
    unset($Cabecera["IdFrm"]);
    unset($Cabecera["frm"]);

    $Cabecera["app"] = "PortalOne"; // Por defecto
    $Cabecera["estado"] = "O"; // Por defecto

    $Cabecera["id_consecutivo_direccion"] = 0; // NumeroLinea (Sap_Clientes_Sucursales)
    $Cabecera["id_direccion_destino"] = $_POST["SucursalCliente"];

    $Cabecera["hora_ingreso"] = FormatoFechaToSAP($Cabecera['fecha_ingreso'], $Cabecera['hora_ingreso']);
    $Cabecera["fecha_ingreso"] = FormatoFechaToSAP($Cabecera['fecha_ingreso']);

    $Cabecera["hora_aprox_entrega"] = FormatoFechaToSAP($Cabecera['fecha_aprox_entrega'], $Cabecera['hora_aprox_entrega']);
    $Cabecera["fecha_aprox_entrega"] = FormatoFechaToSAP($Cabecera['fecha_aprox_entrega']);

    $Cabecera["km_actual"] = intval($Cabecera["km_actual"] ?? 0);

    if (isset($Cabecera['fecha_autoriza_campana']) && $Cabecera['fecha_autoriza_campana'] != "") {
        $Cabecera["fecha_hora_autoriza_campana"] = FormatoFechaToSAP($Cabecera['fecha_autoriza_campana'], $Cabecera['hora_autoriza_campana']);
    } else {
        unset($Cabecera["fecha_autoriza_campana"]);
        unset($Cabecera["hora_autoriza_campana"]);
    }

    for ($i = 1; $i <= 25; $i++) {
        if (isset($Cabecera["id_pregunta_$i"])) {
            $Cabecera["id_pregunta_$i"] = intval($Cabecera["id_pregunta_$i"]);
        }
    }

    $Cabecera["id_empleado_tecnico"] = intval($_SESSION['CodigoSAP']);
    $Cabecera["empleado_tecnico"] = $_SESSION['NombreEmpleado'];

    $Cabecera["id_usuario_creacion"] = $_SESSION['CodUser'];

    if (isset($Cabecera["id_llamada_servicio"])) {
        $Cabecera["docentry_llamada_servicio"] = intval($Cabecera["id_llamada_servicio"]);

        $SQL_OT = Seleccionar("uvw_Sap_tbl_LlamadasServicios", "DocNum", "ID_LlamadaServicio=" . $Cabecera["docentry_llamada_servicio"]);
        $row_OT = sqlsrv_fetch_array($SQL_OT);
        $Cabecera["id_llamada_servicio"] = intval($row_OT['DocNum'] ?? 0);
    } else {
        $Cabecera["id_llamada_servicio"] = 0;
        $Cabecera["docentry_llamada_servicio"] = 0;
    }

    $dir_log = CrearObtenerDirRuta(ObtenerVariable("RutaAnexosPortalOne") . "/" . $_SESSION['User'] . "/" . $dir_name . "/");
    $dir_main = CrearObtenerDirRuta(ObtenerVariable("RutaAnexosRecepcionVehiculo"));

    // Inicio, agregar anexos al JSON.
    if (isset($Cabecera["Anexo0"])) {
        $a = 0;
        $Cabecera["anexos"] = array();
        while (isset($Cabecera["Anexo$a"])) {
            $ext = substr($Cabecera["Anexo$a"], -3);
            $hash = substr(uniqid(rand()), 0, 8);
            $nombre_anexo = "Anexo_$hash.$ext";

            array_push($Cabecera["anexos"], [
                "anexo" => $nombre_anexo,
            ]);

            // Inicio, copiar anexos a la ruta log y main.
            $source = CrearObtenerDirTemp() . $Cabecera["Anexo$a"];
            $dest = $dir_log . $nombre_anexo;
            copy($source, $dest);
            $dest = $dir_main . $nombre_anexo;
            copy($source, $dest);
            // Fin, copiar anexos a la ruta log y main.

            $a++;
        }
    }
    // Fin, agregar anexos al JSON.

    // Inicio, copiar firma a la ruta log y main, y agregarlas al JSON.
    $Cabecera["firma_responsable_cliente"] = base64_decode($Cabecera["SigCliente"]) ?? "";
    unset($Cabecera["SigCliente"]);

    if ($Cabecera["firma_responsable_cliente"] != "") {
        $source = CrearObtenerDirTempFirma() . $Cabecera["firma_responsable_cliente"];

        $dest = $dir_log . $Cabecera["firma_responsable_cliente"];
        copy($source, $dest);

        $dest = $dir_main . $Cabecera["firma_responsable_cliente"];
        copy($source, $dest);
    }
    // Fin, copiar firma a la ruta log y main, y agregarlas al JSON.

    // Inicio, copiar fotografias a la ruta log y main, y agregarlas al JSON.
    $Cabecera["fotografias"] = [
        array(
            "id_recepcion_vehiculo" => 0, // Por defecto
            "id_recepcion_fotografia" => 0, // Por defecto
            "anexo_frente" => $Cabecera["Img1"] ?? "",
            "anexo_lateral_izquierdo" => $Cabecera["Img2"] ?? "",
            "anexo_lateral_derecho" => $Cabecera["Img3"] ?? "",
            "anexo_trasero" => $Cabecera["Img4"] ?? "",
            "anexo_capot" => $Cabecera["Img5"] ?? "",
        ),
    ];

    if (isset($Cabecera["Img1"])) {
        $source = CrearObtenerDirTemp() . $Cabecera["Img1"];

        $dest = $dir_log . $Cabecera["Img1"];
        copy($source, $dest);

        $dest = $dir_main . $Cabecera["Img1"];
        copy($source, $dest);

        unset($Cabecera["Img1"]);
    }

    if (isset($Cabecera["Img2"])) {
        $source = CrearObtenerDirTemp() . $Cabecera["Img2"];

        $dest = $dir_log . $Cabecera["Img2"];
        copy($source, $dest);

        $dest = $dir_main . $Cabecera["Img2"];
        copy($source, $dest);

        unset($Cabecera["Img2"]);
    }

    if (isset($Cabecera["Img3"])) {
        $source = CrearObtenerDirTemp() . $Cabecera["Img3"];

        $dest = $dir_log . $Cabecera["Img3"];
        copy($source, $dest);

        $dest = $dir_main . $Cabecera["Img3"];
        copy($source, $dest);

        unset($Cabecera["Img3"]);
    }

    if (isset($Cabecera["Img4"])) {
        $source = CrearObtenerDirTemp() . $Cabecera["Img4"];

        $dest = $dir_log . $Cabecera["Img4"];
        copy($source, $dest);

        $dest = $dir_main . $Cabecera["Img4"];
        copy($source, $dest);

        unset($Cabecera["Img4"]);
    }

    if (isset($Cabecera["Img5"])) {
        $source = CrearObtenerDirTemp() . $Cabecera["Img5"];

        $dest = $dir_log . $Cabecera["Img5"];
        copy($source, $dest);

        $dest = $dir_main . $Cabecera["Img5"];
        copy($source, $dest);

        unset($Cabecera["Img5"]);
    }
    // Fin, Copiar fotografias a la ruta log y main.

    try {
        $Metodo = "RecepcionVehiculos";
        $Resultado = EnviarWebServiceSAP($Metodo, $Cabecera, true, true);

        if ($Resultado->Success == 0) {
            // $sw_error = 1;
            $msg_error = $Resultado->Mensaje;
            $msg = array(
                "title" => "¡Ha ocurrido un error!",
                "text" => "$msg_error",
                "icon" => "warning",
            );

            echo json_encode($msg);
        } else {
            $msg = array(
                "title" => "¡Listo!",
                "text" => "La recepción de vehículo ha sido creada exitosamente.",
                "icon" => "success",
                "return" => $return,
            );

            echo json_encode($msg);
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
            // header('Location:frm_hallazgos.php?id=' . $_GET['id_frm'] . '&tl=1&return=' . $_GET['return'] . '&pag=' . $_GET['pag'] . '&frm=' . $frm . '&a=' . base64_encode("OK_OpenFrm"));

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
            // header('Location:gestionar_hallazgos.php?id=' . $frm . '&a=' . base64_encode("OK_FrmDel"));

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

// Preguntas en la recepción de vehículo
$SQL_Preguntas = Seleccionar('tbl_RecepcionVehiculos_Preguntas', '*');

sqlsrv_close($conexion);
