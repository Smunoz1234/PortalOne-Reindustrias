<?php
require_once "includes/conexion.php";
PermitirAcceso(1204);

// Dimensiones, SMM 31/08/2022
$DimSeries = intval(ObtenerVariable("DimensionSeries"));
$SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', "DimActive='Y'");

// Pruebas, SMM 31/08/2022
// $SQL_Dimensiones = Seleccionar('uvw_Sap_tbl_Dimensiones', '*', 'DimCode IN (1,2)');

$array_Dimensiones = [];
while ($row_Dimension = sqlsrv_fetch_array($SQL_Dimensiones)) {
    array_push($array_Dimensiones, $row_Dimension);
}
// Hasta aquí, SMM 31/08/2022

$sw = 0;
//$Proyecto="";
$Almacen = "";
$AlmacenDestino = "";
$CardCode = "";
$Id = "";
$Evento = "";
$Usuario = "";
$type = 1;
$Estado = 1; //Abierto
$Lotes = 0; //Cantidad de articulos con lotes
$Seriales = 0; //Cantidad de articulos con

// Se eliminaron las dimensiones, 31/08/2022

if (isset($_GET['id']) && ($_GET['id'] != "")) {
    if ($_GET['type'] == 1) {
        $type = 1;
    } else {
        $type = $_GET['type'];
    }
    if ($type == 1) { //Creando Traslado de inventario
        $SQL = Seleccionar("uvw_tbl_TrasladoInventarioDetalleCarrito", "*", "Usuario='" . $_GET['usr'] . "' and CardCode='" . $_GET['cardcode'] . "'");

        //Contar si hay articulos con lote
        $SQL_Lotes = Seleccionar("uvw_tbl_TrasladoInventarioDetalleCarrito", "Count(ID_TrasladoInvDetalleCarrito) AS Cant", "Usuario='" . $_GET['usr'] . "' and CardCode='" . $_GET['cardcode'] . "' and ManBtchNum='Y'");
        $row_Lotes = sqlsrv_fetch_array($SQL_Lotes);
        $Lotes = $row_Lotes['Cant'];

        //Contar si hay articulos con seriales
        $SQL_Seriales = Seleccionar("uvw_tbl_TrasladoInventarioDetalleCarrito", "Count(ID_TrasladoInvDetalleCarrito) AS Cant", "Usuario='" . $_GET['usr'] . "' and CardCode='" . $_GET['cardcode'] . "' and ManSerNum='Y'");
        $row_Seriales = sqlsrv_fetch_array($SQL_Seriales);
        $Seriales = $row_Seriales['Cant'];

        if ($SQL) {
            $sw = 1;
            $CardCode = $_GET['cardcode'];
            //$Proyecto=$_GET['prjcode'];
            //$Almacen=$_GET['whscode'];
            //$AlmacenDestino=$_GET['towhscode'];
            $Usuario = $_GET['usr'];
        } else {
            $CardCode = "";
            //$Proyecto="";
            $Almacen = "";
            $AlmacenDestino = "";
            $Usuario = "";
        }

    } else { //Editando Traslado de inventario
        if (isset($_GET['status']) && (base64_decode($_GET['status']) == "C")) {
            $Estado = 2;
        } else {
            $Estado = 1;
        }

        $Id = base64_decode($_GET['id']);
        $Evento = base64_decode($_GET['evento']);

        $SQL = Seleccionar("uvw_tbl_TrasladoInventarioDetalle", "*", "ID_TrasladoInv='" . base64_decode($_GET['id']) . "' and IdEvento='" . base64_decode($_GET['evento']) . "' and Metodo <> 3");

        //Contar si hay articulos con lote
        $SQL_Lotes = Seleccionar("uvw_tbl_TrasladoInventarioDetalle", "Count(ID_TrasladoInv) AS Cant", "ID_TrasladoInv='" . base64_decode($_GET['id']) . "' and IdEvento='" . base64_decode($_GET['evento']) . "' and Metodo <> 3 and ManBtchNum='Y'");
        $row_Lotes = sqlsrv_fetch_array($SQL_Lotes);
        $Lotes = $row_Lotes['Cant'];

        //Contar si hay articulos con seriales
        $SQL_Seriales = Seleccionar("uvw_tbl_TrasladoInventarioDetalle", "Count(ID_TrasladoInv) AS Cant", "ID_TrasladoInv='" . base64_decode($_GET['id']) . "' and IdEvento='" . base64_decode($_GET['evento']) . "' and Metodo <> 3 and ManSerNum='Y'");
        $row_Seriales = sqlsrv_fetch_array($SQL_Seriales);
        $Seriales = $row_Seriales['Cant'];

        if ($SQL) {
            $sw = 1;
        }
    }
}

//Servicios
$SQL_Servicios = Seleccionar("uvw_Sap_tbl_OrdenesVentasDetalleServicios", "*", "", "DeServicio");

//Metodo de apliacion
$SQL_MetodoAplicacion = Seleccionar("uvw_Sap_tbl_OrdenesVentasDetalleMetodoAplicacion", "*", "", "DeMetodoAplicacion");

//Tipo de plagas
$SQL_TipoPlaga = Seleccionar("uvw_Sap_tbl_OrdenesVentasDetalleTipoPlagas", "*", "", "DeTipoPlagas");

//$Almacen=$row['WhsCode'];
$ParamAlmacen = array(
    "'" . $_SESSION['CodUser'] . "'",
    "'67'",
);
$SQL_Almacen = EjecutarSP('sp_ConsultarAlmacenesUsuario', $ParamAlmacen);

$ParamAlmacenDest = array(
    "'" . $_SESSION['CodUser'] . "'",
    "'67'",
    "2",
);

$SQL_ToAlmacen = EjecutarSP('sp_ConsultarAlmacenesUsuario', $ParamAlmacenDest);

// Se eliminaron las dimensiones, 31/08/2022

//Proyectos
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');
?>
<!doctype html>
<html>
<head>
<?php include_once "includes/cabecera.php";?>
<style>
	.ibox-content{
		padding: 0px !important;
	}
	body{
		background-color: #ffffff;
		overflow-x: auto;
	}
	.form-control{
		width: auto;
		height: 28px;
	}
	.table > tbody > tr > td{
		padding: 1px !important;
		vertical-align: middle;
	}
</style>
<script>
var json=[];
var cant=0;

function BuscarLote(){
	var posicion_x;
	var posicion_y;
	posicion_x=(screen.width/2)-(1200/2);
	posicion_y=(screen.height/2)-(500/2);
	<?php if ($type == 1) { //Creando Entrega de venta ?>
		var Almacen='<?php echo $Almacen; ?>';
		var CardCode='<?php echo $CardCode; ?>';
		var Lotes='<?php echo $Lotes; ?>'
		if(CardCode!=""&&Lotes>0){
			remote=open('popup_lotes_sap.php?docentry=0&evento=0&edit=<?php echo $type; ?>&usuario=<?php echo $Usuario; ?>&cardcode=<?php echo $CardCode; ?>&objtype=67','remote',"width=1200,height=500,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no,fullscreen=no,directories=no,status=yes,left="+posicion_x+",top="+posicion_y+"");
			remote.focus();
		}
	<?php } else { //Editando Entrega de venta ?>
		remote=open('popup_lotes_sap.php?id=<?php if ($type == 2) {echo $_GET['id'];} else {echo "0";}?>&evento=<?php if ($type == 2) {echo $_GET['evento'];} else {echo "0";}?>&docentry=<?php if ($type == 2) {echo $_GET['docentry'];} else {echo "0";}?>&edit=<?php echo $type; ?>&objtype=67','remote',"width=1200,height=500,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no,fullscreen=no,directories=no,status=yes,left="+posicion_x+",top="+posicion_y+"");
		remote.focus();
	<?php }?>
}

function BuscarSerial(){
	var posicion_x;
	var posicion_y;
	posicion_x=(screen.width/2)-(1200/2);
	posicion_y=(screen.height/2)-(500/2);
	<?php if ($type == 1) { //Creando Entrega de venta ?>
		var Almacen='<?php echo $Almacen; ?>';
		var CardCode='<?php echo $CardCode; ?>';
		var Seriales='<?php echo $Seriales; ?>'
		if(CardCode!=""&&Seriales>0){
			remote=open('popup_seriales_sap.php?docentry=0&evento=0&edit=<?php echo $type; ?>&usuario=<?php echo $Usuario; ?>&cardcode=<?php echo $CardCode; ?>&objtype=67','remote',"width=1200,height=500,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no,fullscreen=no,directories=no,status=yes,left="+posicion_x+",top="+posicion_y+"");
			remote.focus();
		}
	<?php } else { //Editando Entrega de venta ?>
		remote=open('popup_seriales_sap.php?id=<?php if ($type == 2) {echo $_GET['id'];} else {echo "0";}?>&evento=<?php if ($type == 2) {echo $_GET['evento'];} else {echo "0";}?>&docentry=<?php if ($type == 2) {echo $_GET['docentry'];} else {echo "0";}?>&edit=<?php echo $type; ?>&objtype=67','remote',"width=1200,height=500,location=no,scrollbars=yes,menubars=no,toolbars=no,resizable=no,fullscreen=no,directories=no,status=yes,left="+posicion_x+",top="+posicion_y+"");
		remote.focus();
	<?php }?>
}

function BorrarLinea(LineNum){
	if(confirm(String.fromCharCode(191)+'Est'+String.fromCharCode(225)+' seguro que desea eliminar este item? Este proceso no se puede revertir.')){
		$.ajax({
			type: "GET",
			<?php if ($type == 1) {?>
			url: "includes/procedimientos.php?type=13&edit=<?php echo $type; ?>&linenum="+json+"&cardcode=<?php echo $CardCode; ?>",
			<?php } else {?>
			url: "includes/procedimientos.php?type=13&edit=<?php echo $type; ?>&linenum="+LineNum+"&id=<?php echo base64_decode($_GET['id']); ?>&evento=<?php echo base64_decode($_GET['evento']); ?>",
			<?php }?>
			success: function(response){
				window.location.href="detalle_traslado_inventario.php?<?php echo $_SERVER['QUERY_STRING']; ?>";
			}
		});
	}
}

function Totalizar(num){
	//alert(num);
	var SubTotal=0;
	var Descuentos=0;
	var Iva=0;
	var Total=0;
	var i=1;
	for(i=1;i<=num;i++){
		var TotalLinea=document.getElementById('LineTotal'+i);
		var PrecioLinea=document.getElementById('Price'+i);
		var PrecioIVALinea=document.getElementById('PriceTax'+i);
		var TarifaIVALinea=document.getElementById('TarifaIVA'+i);
		var ValorIVALinea=document.getElementById('VatSum'+i);
		var PrcDescuentoLinea=document.getElementById('DiscPrcnt'+i);
		var CantLinea=document.getElementById('Quantity'+i);

		var Precio=parseFloat(PrecioLinea.value.replace(/,/g, ''));
		var PrecioIVA=parseFloat(PrecioIVALinea.value.replace(/,/g, ''));
		var TarifaIVA=TarifaIVALinea.value.replace(/,/g, '');
		var ValorIVA=ValorIVALinea.value.replace(/,/g, '');
		var Cant=parseFloat(CantLinea.value.replace(/,/g, ''));
		//var TotIVA=((parseFloat(Precio)*parseFloat(TarifaIVA)/100)+parseFloat(Precio));
		//ValorIVALinea.value=number_format((parseFloat(Precio)*parseFloat(TarifaIVA)/100),2);
		//PrecioIVALinea.value=number_format(parseFloat(TotIVA),2);
		var SubTotalLinea=Precio*Cant;
		var PrcDesc=parseFloat(PrcDescuentoLinea.value.replace(/,/g, ''));
		var TotalDesc=(PrcDesc*SubTotalLinea)/100;
		//TotalLinea.value=number_format(SubTotalLinea-TotalDesc,2);

		SubTotal=parseFloat(SubTotal)+parseFloat(SubTotalLinea);
		Descuentos=parseFloat(Descuentos)+parseFloat(TotalDesc);
		Iva=parseFloat(Iva)+parseFloat(ValorIVA);
		//var Linea=document.getElementById('LineTotal'+i).value.replace(/,/g, '');
	}
	Total=parseFloat(Total)+parseFloat((SubTotal-Descuentos)+Iva);
	//return Total;
	//alert(Total);
	window.parent.document.getElementById('SubTotal').value=number_format(parseFloat(SubTotal),2);
	window.parent.document.getElementById('Descuentos').value=number_format(parseFloat(Descuentos),2);
	window.parent.document.getElementById('Impuestos').value=number_format(parseFloat(Iva),2);
	window.parent.document.getElementById('TotalTraslado').value=number_format(parseFloat(Total),2);
	window.parent.document.getElementById('TotalItems').value=num;
}

function ActualizarDatos(name,id,line){//Actualizar datos asincronicamente
	$.ajax({
		type: "GET",
		<?php if ($type == 1) {?>
		url: "registro.php?P=36&doctype=6&type=1&name="+name+"&value="+Base64.encode(document.getElementById(name+id).value)+"&line="+line+"&cardcode=<?php echo $CardCode; ?>&whscode=<?php echo $Almacen; ?>&actodos=0",
		<?php } else {?>
		url: "registro.php?P=36&doctype=6&type=2&name="+name+"&value="+Base64.encode(document.getElementById(name+id).value)+"&line="+line+"&id=<?php echo base64_decode($_GET['id']); ?>&evento=<?php echo base64_decode($_GET['evento']); ?>&actodos=0",
		<?php }?>
		success: function(response){
			if(response!="Error"){
				window.parent.document.getElementById('TimeAct').innerHTML="<strong>Actualizado:</strong> "+response;
			}
		}
	});
}

function ActStockAlmacen(name,id,line){//Actualizar el stock al cambiar el almacen
	$.ajax({
		type: "GET",
		url: "includes/procedimientos.php?type=34&edit=<?php echo $type; ?>&whscode="+document.getElementById(name+id).value+"&linenum="+line+"&cardcode=<?php echo $CardCode; ?>&id=<?php echo $Id; ?>&evento=<?php echo $Evento; ?>&tdoc=67",
		success: function(response){
			if(response!="Error"){
				document.getElementById("OnHand"+id).value=number_format(response,2);
			}
		}
	});
}

function Seleccionar(ID){
	var btnBorrarLineas=document.getElementById('btnBorrarLineas');
	var Check = document.getElementById('chkSel'+ID).checked;
	var sw=-1;
	json.forEach(function(element,index){
//		console.log(element,index);
//		console.log(json[index])deta
		if(json[index]==ID){
			sw=index;
		}

	});

	if(sw>=0){
		json.splice(sw, 1);
		cant--;
	}else if(Check){
		json.push(ID);
		cant++;
	}
	if(cant>0){
		$("#btnBorrarLineas").removeClass("disabled");
	}else{
		$("#btnBorrarLineas").addClass("disabled");
	}

	//console.log(json);
}

function SeleccionarTodos(){
	var Check = document.getElementById('chkAll').checked;
	if(Check==false){
		json=[];
		cant=0;
		$("#btnBorrarLineas").addClass("disabled");
	}
	$(".chkSel").prop("checked", Check);
	if(Check){
		$(".chkSel").trigger('change');
	}
}
</script>
</script>
</head>

<body>
<form id="from" name="form">
	<div class="">
	<table width="100%" class="table table-bordered">
		<thead>
			<tr>
				<?php if ($Estado == 1) {?>
					<th class="text-center form-inline w-80"><div class="checkbox checkbox-success"><input type="checkbox" id="chkAll" value="" onChange="SeleccionarTodos();" title="Seleccionar todos"><label></label></div> <button type="button" id="btnBorrarLineas" title="Borrar lineas" class="btn btn-danger btn-xs disabled" onClick="BorrarLinea();"><i class="fa fa-trash"></i></button></th>
				<?php } else {?>
					<th>&nbsp;</th>
				<?php }?>
				<th>Código artículo</th>
				<th>Nombre artículo</th>
				<th>Unidad</th>
				<th>Cantidad<?php if ($Lotes > 0) {?><span class="badge badge-info pull-right" title="Ver lotes (Alt+Q)" style="cursor: pointer;" onClick="BuscarLote();"><i class="fa fa-tasks"></i></span><?php }?><?php if ($Seriales > 0) {?><span class="badge badge-success pull-right" title="Ver seriales (Alt+Y)" style="cursor: pointer;" onClick="BuscarSerial();"><i class="fa fa-barcode"></i></span><?php }?></th>
				<th>Cant. Pendiente</th>
				<th>Almacén origen</th>
				<th>Almacén destino</th>
				<th>Stock almacén</th>
				
				<!-- Dimensiones dinámicas, SMM 31/08/2022 -->
				<?php foreach ($array_Dimensiones as &$dim) {?>
					<th><?php echo $dim["DimDesc"]; ?></th>
				<?php }?>
				<!-- Dimensiones dinámicas, hasta aquí -->

				<th>Proyecto</th>
				<th>Texto libre</th>
				<th>Precio</th>
				<th>Precio con IVA</th>
				<th>% Desc.</th>
				<th>Total</th>
				<th><i class="fa fa-refresh"></i></th>
			</tr>
		</thead>
		<tbody>
		<?php
if ($sw == 1) {
    $i = 1;
    while ($row = sqlsrv_fetch_array($SQL)) {
        /**** Campos definidos por el usuario ****/
        sqlsrv_fetch($SQL_Almacen, SQLSRV_SCROLL_ABSOLUTE, -1);
        sqlsrv_fetch($SQL_ToAlmacen, SQLSRV_SCROLL_ABSOLUTE, -1);

        // Se eliminaron las dimensiones, 31/08/2022

        sqlsrv_fetch($SQL_Proyecto, SQLSRV_SCROLL_ABSOLUTE, -1);
        ?>
		<tr>
			<td class="text-center">
				<?php if (($row['TreeType'] != "T") && ($row['LineStatus'] == "O") && ($Estado == 1)) {?>
					<div class="checkbox checkbox-success no-margins">
						<input type="checkbox" class="chkSel" id="chkSel<?php echo $row['LineNum']; ?>" value="" onChange="Seleccionar('<?php echo $row['LineNum']; ?>');" aria-label="Single checkbox One"><label></label>
					</div>
				<?php }?>
			</td>
			<td><input size="20" type="text" id="ItemCode<?php echo $i; ?>" name="ItemCode[]" class="form-control" readonly value="<?php echo $row['ItemCode']; ?>"><input type="hidden" name="LineNum[]" id="LineNum<?php echo $i; ?>" value="<?php echo $row['LineNum']; ?>"></td>
			<td><input size="50" type="text" id="ItemName<?php echo $i; ?>" name="ItemName[]" class="form-control" value="<?php echo $row['ItemName']; ?>" maxlength="100" onChange="ActualizarDatos('ItemName',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || $Estado == 2) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="UnitMsr<?php echo $i; ?>" name="UnitMsr[]" class="form-control" readonly value="<?php echo $row['UnitMsr']; ?>"></td>
			<td><input size="15" type="text" id="Quantity<?php echo $i; ?>" name="Quantity[]" class="form-control" value="<?php echo number_format($row['Quantity'], 2); ?>" onChange="ActualizarDatos('Quantity',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" <?php if ($row['LineStatus'] == 'C' || $Estado == 2) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="CantInicial<?php echo $i; ?>" name="CantInicial[]" class="form-control" value="<?php echo number_format($row['CantInicial'], 2); ?>" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" readonly></td>

			<td>
				<select id="WhsCode<?php echo $i; ?>" name="WhsCode[]" class="form-control select2" onChange="ActualizarDatos('WhsCode',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);ActStockAlmacen('WhsCode',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || ($type == 2)) {echo "disabled='disabled'";}?>>
				  <option value="">Seleccione...</option>
				  <?php while ($row_Almacen = sqlsrv_fetch_array($SQL_Almacen)) {?>
						<option value="<?php echo $row_Almacen['WhsCode']; ?>" <?php if ((isset($row['WhsCode'])) && (strcmp($row_Almacen['WhsCode'], $row['WhsCode']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Almacen['WhsName']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td>
				<select id="ToWhsCode<?php echo $i; ?>" name="ToWhsCode[]" class="form-control select2" onChange="ActualizarDatos('ToWhsCode',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || ($type == 2)) {echo "disabled='disabled'";}?>>
				  <option value="">Seleccione...</option>
				  <?php while ($row_ToAlmacen = sqlsrv_fetch_array($SQL_ToAlmacen)) {?>
						<option value="<?php echo $row_ToAlmacen['ToWhsCode']; ?>" <?php if ((isset($row['ToWhsCode'])) && (strcmp($row_ToAlmacen['ToWhsCode'], $row['ToWhsCode']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_ToAlmacen['ToWhsName']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td><input size="15" type="text" id="OnHand<?php echo $i; ?>" name="OnHand[]" class="form-control" value="<?php echo number_format($row['OnHand'], 2); ?>" readonly></td>

			<!-- Dimensiones dinámicas, SMM 31/08/2022 -->
			<?php foreach ($array_Dimensiones as &$dim) {?>
				<?php $DimCode = intval($dim['DimCode']);?>
				<?php $OcrId = ($DimCode == 1) ? "" : $DimCode;?>

				<td>
					<select id="OcrCode<?php echo $OcrId . $i; ?>" name="OcrCode<?php echo $OcrId; ?>[]" class="form-control select2" onChange="ActualizarDatos('OcrCode<?php echo $OcrId; ?>',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || (!PermitirFuncion(402))) {echo "disabled='disabled'";}?>>
						<option value="">(NINGUNO)</option>

						<?php $SQL_Dim = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', "DimCode=$DimCode");?>
						<?php while ($row_Dim = sqlsrv_fetch_array($SQL_Dim)) {?>
							<option value="<?php echo $row_Dim['OcrCode']; ?>" <?php if ((isset($row["OcrCode$OcrId"])) && (strcmp($row_Dim['OcrCode'], $row["OcrCode$OcrId"]) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Dim['OcrName']; ?></option>
						<?php }?>
					</select>
				</td>
			<?php }?>
			<!-- Dimensiones dinámicas, hasta aquí -->

			<td>
				<select id="PrjCode<?php echo $i; ?>" name="PrjCode[]" class="form-control select2" onChange="ActualizarDatos('PrjCode',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || ($type == 2)) {echo "disabled='disabled'";}?>>
					<option value="">(NINGUNO)</option>
				  <?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) {?>
						<option value="<?php echo $row_Proyecto['IdProyecto']; ?>" <?php if ((isset($row['PrjCode'])) && (strcmp($row_Proyecto['IdProyecto'], $row['PrjCode']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Proyecto['DeProyecto']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td><input size="50" type="text" id="FreeTxt<?php echo $i; ?>" name="FreeTxt[]" class="form-control" value="<?php echo $row['FreeTxt']; ?>" onChange="ActualizarDatos('FreeTxt',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" maxlength="100" <?php if ($row['LineStatus'] == 'C' || $Estado == 2) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="Price<?php echo $i; ?>" name="Price[]" class="form-control" value="<?php echo number_format($row['Price'], 2); ?>" onChange="ActualizarDatos('Price',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" <?php if ($row['LineStatus'] == 'C' || $Estado == 2) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="PriceTax<?php echo $i; ?>" name="PriceTax[]" class="form-control" value="<?php echo number_format($row['PriceTax'], 2); ?>" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" readonly><input type="hidden" id="TarifaIVA<?php echo $i; ?>" name="TarifaIVA[]" value="<?php echo number_format($row['TarifaIVA'], 0); ?>"><input type="hidden" id="VatSum<?php echo $i; ?>" name="VatSum[]" value="<?php echo number_format($row['VatSum'], 2); ?>"></td>
			<td><input size="15" type="text" id="DiscPrcnt<?php echo $i; ?>" name="DiscPrcnt[]" class="form-control" value="<?php echo number_format($row['DiscPrcnt'], 2); ?>" onChange="ActualizarDatos('DiscPrcnt',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" <?php if ($row['LineStatus'] == 'C' || $Estado == 2) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="LineTotal<?php echo $i; ?>" name="LineTotal[]" class="form-control" readonly value="<?php echo number_format($row['LineTotal'], 2); ?>"></td>
			<td><?php if ($row['Metodo'] == 0) {?><i class="fa fa-check-circle text-info" title="Sincronizado con SAP"></i><?php } else {?><i class="fa fa-times-circle text-danger" title="Aún no enviado a SAP"></i><?php }?></td>
		</tr>
		<?php
$i++;}
    echo "<script>
			Totalizar(" . ($i - 1) . ");
			</script>";
}
?>
		<?php if ($Estado == 1) {?>
		<tr>
			<td>&nbsp;</td>
			<td><input size="20" type="text" id="ItemCodeNew" name="ItemCodeNew" class="form-control"></td>
			<td><input size="50" type="text" id="ItemNameNew" name="ItemNameNew" class="form-control"></td>
			<td><input size="15" type="text" id="UnitMsrNew" name="UnitMsrNew" class="form-control"></td>
			<td><input size="15" type="text" id="QuantityNew" name="QuantityNew" class="form-control"></td>
			<td><input size="15" type="text" id="CantInicialNew" name="CantInicialNew" class="form-control"></td>
			<td><input size="20" type="text" id="WhsCodeNew" name="WhsCodeNew" class="form-control"></td>
			<td><input size="20" type="text" id="ToWhsCodeNew" name="ToWhsCodeNew" class="form-control"></td>
			<td><input size="15" type="text" id="OnHandNew" name="OnHandNew" class="form-control"></td>

			<td><input size="20" type="text" id="OcrCodeNew" name="OcrCodeNew" class="form-control"></td>
			<td><input size="20" type="text" id="OcrCode2New" name="OcrCode2New" class="form-control"></td>
			<td><input size="20" type="text" id="OcrCode3New" name="OcrCode3New" class="form-control"></td>
			<td><input size="70" type="text" id="ProyectoNew" name="ProyectoNew" class="form-control"></td>

			<td><input size="50" type="text" id="FreeTxtNew" name="FreeTxtNew" class="form-control"></td>
			<td><input size="15" type="text" id="PriceNew" name="PriceNew" class="form-control"></td>
			<td><input size="15" type="text" id="PriceTaxNew" name="PriceTaxNew" class="form-control"></td>
			<td><input size="15" type="text" id="DiscPrcntNew" name="DiscPrcntNew" class="form-control"></td>
			<td><input size="15" type="text" id="LineTotalNew" name="LineTotalNew" class="form-control"></td>
			<td>&nbsp;</td>
		</tr>
		<?php }?>
		</tbody>
	</table>
	</div>
</form>
<script>
function CalcularTotal(line){
	var TotalLinea=document.getElementById('LineTotal'+line);
	var PrecioLinea=document.getElementById('Price'+line);
	var PrecioIVALinea=document.getElementById('PriceTax'+line);
	var TarifaIVALinea=document.getElementById('TarifaIVA'+line);
	var ValorIVALinea=document.getElementById('VatSum'+line);
	var PrcDescuentoLinea=document.getElementById('DiscPrcnt'+line);
	var CantLinea=document.getElementById('Quantity'+line);
	var Linea=document.getElementById('LineNum'+line);

	if(CantLinea.value>0){
		//if(parseFloat(PrecioLinea.value)>0){
			//alert('Info');
			var Precio=PrecioLinea.value.replace(/,/g, '');
			var TarifaIVA=TarifaIVALinea.value.replace(/,/g, '');
			var ValorIVA=ValorIVALinea.value.replace(/,/g, '');
			var Cant=CantLinea.value.replace(/,/g, '');
			var TotIVA=((parseFloat(Precio)*parseFloat(TarifaIVA)/100)+parseFloat(Precio));
			ValorIVALinea.value=number_format((parseFloat(Precio)*parseFloat(TarifaIVA)/100),2);
			PrecioIVALinea.value=number_format(parseFloat(TotIVA),2);
			var PrecioIVA=PrecioIVALinea.value.replace(/,/g, '');
			var SubTotalLinea=PrecioIVA*Cant;
			var PrcDesc=parseFloat(PrcDescuentoLinea.value.replace(/,/g, ''));
			var TotalDesc=(PrcDesc*SubTotalLinea)/100;

			TotalLinea.value=number_format(SubTotalLinea-TotalDesc,2);
		//}else{
			//alert('Ult');
			//var Ult=UltPrecioLinea.value.replace(/,/g, '');
			//var Cant=CantLinea.value.replace(/,/g, '');
			//TotalLinea.value=parseFloat(number_format(Ult*Cant,2));
		//}
		Totalizar(<?php if (isset($i)) {echo $i - 1;} else {echo 0;}?>);
		//window.parent.document.getElementById('TotalSolicitud').value='500';
	}else{
		alert("No puede solicitar cantidad en 0. Si ya no va a solicitar este articulo, borre la linea.");
		CantLinea.value="1.00";
		//ActualizarDatos(1,line,Linea.value);
	}

}
</script>
<script>
	 $(document).ready(function(){
		 $(".alkin").on('click', function(){
				 $('.ibox-content').toggleClass('sk-loading');
			});
		  $(".select2").select2();
		 var options = {
			url: function(phrase) {
				return "ajx_buscar_datos_json.php?type=12&data="+phrase+"&whscode=<?php echo $Almacen; ?>&tipodoc=3";
			},
			getValue: "IdArticulo",
			requestDelay: 400,
			template: {
				type: "description",
				fields: {
					description: "DescripcionArticulo"
				}
			},
			list: {
				maxNumberOfElements: 8,
				match: {
					enabled: true
				},
				onClickEvent: function() {
					var IdArticulo = $("#ItemCodeNew").getSelectedItemData().IdArticulo;
					var DescripcionArticulo = $("#ItemCodeNew").getSelectedItemData().DescripcionArticulo;
					var UndMedida = $("#ItemCodeNew").getSelectedItemData().UndMedida;
					var PrecioSinIVA = $("#ItemCodeNew").getSelectedItemData().PrecioSinIVA;
					var PrecioConIVA = $("#ItemCodeNew").getSelectedItemData().PrecioConIVA;
					var CodAlmacen = $("#ItemCodeNew").getSelectedItemData().CodAlmacen;
					var Almacen = $("#ItemCodeNew").getSelectedItemData().Almacen;
					var StockAlmacen = $("#ItemCodeNew").getSelectedItemData().StockAlmacen;
					var StockGeneral = $("#ItemCodeNew").getSelectedItemData().StockGeneral;
					$("#ItemNameNew").val(DescripcionArticulo);
					$("#UnitMsrNew").val(UndMedida);
					$("#QuantityNew").val('1.00');
					$("#CantInicialNew").val('1.00');
					$("#PriceNew").val(PrecioSinIVA);
					$("#PriceTaxNew").val(PrecioConIVA);
					$("#DiscPrcntNew").val('0.00');
					$("#LineTotalNew").val('0.00');
					$("#OnHandNew").val(StockAlmacen);
					$("#WhsCodeNew").val(Almacen);
					$.ajax({
						type: "GET",
						<?php if ($type == 1) {?>
						url: "registro.php?P=35&doctype=11&item="+IdArticulo+"&whscode="+CodAlmacen+"&towhscode=<?php echo $AlmacenDestino; ?>&cardcode=<?php echo $CardCode; ?>",
						<?php } else {?>
						url: "registro.php?P=35&doctype=12&item="+IdArticulo+"&whscode="+CodAlmacen+"&towhscode=<?php echo $AlmacenDestino; ?>&cardcode=0&id=<?php echo base64_decode($_GET['id']); ?>&evento=<?php echo base64_decode($_GET['evento']); ?>",
						<?php }?>
						success: function(response){
							window.location.href="detalle_traslado_inventario.php?<?php echo $_SERVER['QUERY_STRING']; ?>";
						}
					});
				}
			}
		};
		<?php if ($sw == 1 && $Estado == 1 && PermitirFuncion(1203)) {?>
		$("#ItemCodeNew").easyAutocomplete(options);
	 	<?php }?>
	});
</script>
</body>
</html>
<?php
sqlsrv_close($conexion);
?>