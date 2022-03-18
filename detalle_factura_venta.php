<?php
require_once "includes/conexion.php";
PermitirAcceso(406);
$sw = 0;
//$Proyecto="";
$Almacen = "";
$CardCode = "";
$Id = "";
$Evento = "";
$type = 1;
$Estado = 1; //Abierto

//Dimensiones de reparto
$SQL_DimReparto = Seleccionar('uvw_Sap_tbl_NombresDimensionesReparto', '*', '', "CodDim");

if (isset($_GET['id']) && ($_GET['id'] != "")) {
    if ($_GET['type'] == 1) {
        $type = 1;
    } else {
        $type = $_GET['type'];
    }
    if ($type == 1) { //Creando Factura de Venta
        $SQL = Seleccionar("uvw_tbl_FacturaVentaDetalleCarrito", "*", "Usuario='" . $_GET['usr'] . "' and CardCode='" . $_GET['cardcode'] . "'");
        if ($SQL) {
            $sw = 1;
            $CardCode = $_GET['cardcode'];
            //$Proyecto=$_GET['prjcode'];
            //$Almacen=$_GET['whscode'];
        } else {
            $CardCode = "";
            //$Proyecto="";
            $Almacen = "";
        }

    } else { //Editando Factura de venta
        if (isset($_GET['status']) && (base64_decode($_GET['status']) == "C")) {
            $Estado = 2;
        } else {
            $Estado = 1;
        }
        $SQL = Seleccionar("uvw_tbl_FacturaVentaDetalle", "*", "ID_FacturaVenta='" . base64_decode($_GET['id']) . "' and IdEvento='" . base64_decode($_GET['evento']) . "' and Metodo <> 3");
        if ($SQL) {
            $sw = 1;
        }
    }
}

//$Almacen=$row['WhsCode'];
$ParamSerie = array(
    "'" . $_SESSION['CodUser'] . "'",
    "'13'",
);
$SQL_Almacen = EjecutarSP('sp_ConsultarAlmacenesUsuario', $ParamSerie);

$SQL_Dim1 = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=1');

$SQL_Dim2 = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=2');

$SQL_Dim3 = Seleccionar('uvw_Sap_tbl_DimensionesReparto', '*', 'DimCode=3');

//Proyectos
$SQL_Proyecto = Seleccionar('uvw_Sap_tbl_Proyectos', '*', '', 'DeProyecto');

//Empleado de ventas, SMM 22/02/2022
$SQL_EmpleadosVentas = Seleccionar('uvw_Sap_tbl_EmpleadosVentas', '*', '', 'DE_EmpVentas');
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
function Totalizar(num){
	console.log(`Totalizar(${num})`);

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
		// var ValorIVALinea=document.getElementById('VatSum'+i);
		var PrcDescuentoLinea=document.getElementById('DiscPrcnt'+i);
		var CantLinea=document.getElementById('Quantity'+i);

		var Precio=parseFloat(PrecioLinea.value.replace(/,/g, ''));
		var PrecioIVA=parseFloat(PrecioIVALinea.value.replace(/,/g, ''));
		var TarifaIVA=TarifaIVALinea.value.replace(/,/g, '');
		// var ValorIVA=ValorIVALinea.value.replace(/,/g, '');
		var Cant=parseFloat(CantLinea.value.replace(/,/g, ''));

		var SubTotalLinea=Precio*Cant;
		var PrcDesc=parseFloat(PrcDescuentoLinea.value.replace(/,/g, ''));
		var TotalDesc=(PrcDesc*SubTotalLinea)/100;

		let ValorIVA = parseFloat(Precio) * (parseFloat(TarifaIVA) / 100); // SMM, 18/03/2022
		let TotIVA = ValorIVA + parseFloat(Precio); // SMM, 16/03/2022
		let SubTotalIVA = TotIVA * Cant; // SMM, 16/03/2022

		TotalLinea.value = number_format(SubTotalIVA-TotalDesc, 2); // SMM, 16/03/2022

		SubTotal=parseFloat(SubTotal)+parseFloat(SubTotalLinea);
		Descuentos=parseFloat(Descuentos)+parseFloat(TotalDesc);

		Iva=parseFloat(Iva)+parseFloat(ValorIVA * Cant);
		//var Linea=document.getElementById('LineTotal'+i).value.replace(/,/g, '');
	}
	Total=parseFloat(Total)+parseFloat((SubTotal-Descuentos)+Iva);
	//return Total;
	//alert(Total);
	window.parent.document.getElementById('SubTotal').value=number_format(parseFloat(SubTotal),2);
	window.parent.document.getElementById('Descuentos').value=number_format(parseFloat(Descuentos),2);
	window.parent.document.getElementById('Impuestos').value=number_format(parseFloat(Iva),2);
	window.parent.document.getElementById('TotalFactura').value=number_format(parseFloat(Total),2);
	window.parent.document.getElementById('TotalItems').value=num;
}

function ActualizarDatos(name,id,line){//Actualizar datos asincronicamente
	$.ajax({
		type: "GET",
		<?php if ($type == 1) {?>
		url: "registro.php?P=36&doctype=9&type=1&name="+name+"&value="+Base64.encode(document.getElementById(name+id).value)+"&line="+line+"&cardcode=<?php echo $CardCode; ?>&whscode=<?php echo $Almacen; ?>&actodos=0",
		<?php } else {?>
		url: "registro.php?P=36&doctype=9&type=2&name="+name+"&value="+Base64.encode(document.getElementById(name+id).value)+"&line="+line+"&id=<?php echo base64_decode($_GET['id']); ?>&evento=<?php echo base64_decode($_GET['evento']); ?>&actodos=0",
		<?php }?>
		success: function(response){
			if(response!="Error"){
				window.parent.document.getElementById('TimeAct').innerHTML="<strong>Actualizado:</strong> "+response;
			}
		}
	});
}

// Stiven Muñoz Murillo, 21/02/2022
var json=[];
var cant=0;

function BorrarLinea(){
	if(confirm(String.fromCharCode(191)+'Est'+String.fromCharCode(225)+' seguro que desea eliminar este item? Este proceso no se puede revertir.')){
		$.ajax({
			type: "GET",
			<?php if ($type == 1) {?>
			url: "includes/procedimientos.php?type=20&edit=<?php echo $type; ?>&linenum="+json+"&cardcode=<?php echo $CardCode; ?>",
			<?php } else {?>
			url: "includes/procedimientos.php?type=20&edit=<?php echo $type; ?>&linenum="+json+"&id=<?php echo base64_decode($_GET['id']); ?>&evento=<?php echo base64_decode($_GET['evento']); ?>",
			<?php }?>
			success: function(response){
				window.location.href="detalle_factura_venta.php?<?php echo $_SERVER['QUERY_STRING']; ?>";
				console.log(response);
			},
			error: function(error){
				console.error(error);
			}
		});
	}
}

function Seleccionar(ID){
	var btnBorrarLineas=document.getElementById('btnBorrarLineas');
	var btnDuplicarLineas=document.getElementById('btnDuplicarLineas');
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
		$("#btnBorrarLineas").prop('disabled', false);
	}else{
		$("#btnBorrarLineas").prop('disabled', true);
	}

	//console.log(json);
}

function SeleccionarTodos(){
	var Check = document.getElementById('chkAll').checked;
	if(Check==false){
		json=[];
		cant=0;
		$("#btnBorrarLineas").prop('disabled', true);
	}
	$(".chkSel:not(:disabled)").prop("checked", Check);

	if(Check){
		$(".chkSel:not(:disabled)").trigger('change');
	}
}

// SMM, 10/03/2022
function ConsultarArticulo(articulo){
	if(articulo!=""){
		self.name='opener';
		remote=open('articulos.php?id='+articulo+'&ext=1&tl=1','remote','location=no,scrollbar=yes,menubars=no,toolbars=no,resizable=yes,fullscreen=yes,status=yes');
		remote.focus();
	}
}
</script>
</head>

<body>
<form id="from" name="form">
	<div class="">
	<table width="100%" class="table table-bordered">
		<thead>
			<tr>
				<th class="text-center form-inline w-150">
					<div class="checkbox checkbox-success"><input type="checkbox" id="chkAll" value="" onChange="SeleccionarTodos();" title="Seleccionar todos"><label></label></div>
					<button type="button" id="btnBorrarLineas" title="Borrar lineas" class="btn btn-danger btn-xs" disabled onClick="BorrarLinea();"><i class="fa fa-trash"></i></button>
				</th>
				<th>Código artículo</th>
				<th>Nombre artículo</th>
				<th>Unidad</th>
				<th>Cantidad</th>
				<th>Cant. Inicial</th>
				<th>Almacén</th>
				<th>Stock almacén</th>
				<th>Sucursal cliente</th>
				<th>Áreas controladas</th>
				<th>Números de OT</th>
				<?php $row_DimReparto = sqlsrv_fetch_array($SQL_DimReparto);?>
				<th><?php echo $row_DimReparto['NombreDim']; //Dimension 1  ?></th>
				<?php $row_DimReparto = sqlsrv_fetch_array($SQL_DimReparto);?>
				<th><?php echo $row_DimReparto['NombreDim']; //Dimension 2  ?></th>
				<?php $row_DimReparto = sqlsrv_fetch_array($SQL_DimReparto);?>
				<th><?php echo $row_DimReparto['NombreDim']; //Dimension 3  ?></th>
				<th>Proyecto</th>
				<th>Empleado de ventas</th>
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
        sqlsrv_fetch($SQL_Almacen, SQLSRV_SCROLL_ABSOLUTE, -1);
        sqlsrv_fetch($SQL_Dim1, SQLSRV_SCROLL_ABSOLUTE, -1);
        sqlsrv_fetch($SQL_Dim2, SQLSRV_SCROLL_ABSOLUTE, -1);
        sqlsrv_fetch($SQL_Dim3, SQLSRV_SCROLL_ABSOLUTE, -1);
        sqlsrv_fetch($SQL_Proyecto, SQLSRV_SCROLL_ABSOLUTE, -1);
        sqlsrv_fetch($SQL_EmpleadosVentas, SQLSRV_SCROLL_ABSOLUTE, -1); // SMM, 22/02/2022
        ?>
		<tr>
			<td class="text-center form-inline w-150">
				<div class="checkbox checkbox-success"><input type="checkbox" class="chkSel" id="chkSel<?php echo $row['LineNum']; ?>" value="" onChange="Seleccionar('<?php echo $row['LineNum']; ?>');" aria-label="Single checkbox One" <?php if (($row['LineStatus'] == "C") && ($type == 2)) {echo "disabled='disabled'";}?>><label></label></div>
				<button type="button" class="btn btn-success btn-xs" onClick="ConsultarArticulo('<?php echo base64_encode($row['ItemCode']); ?>');" title="Consultar Articulo"><i class="fa fa-search"></i></button> <!-- SMM, 10/03/2022 -->
			</td>

			<td>
				<input size="20" type="text" id="ItemCode<?php echo $i; ?>" name="ItemCode[]" class="form-control" readonly value="<?php echo $row['ItemCode']; ?>">
				<input type="hidden" name="LineNum[]" id="LineNum<?php echo $i; ?>" value="<?php echo $row['LineNum']; ?>">
			</td>

			<td><input size="50" type="text" id="ItemName<?php echo $i; ?>" name="ItemName[]" class="form-control" value="<?php echo $row['ItemName']; ?>" maxlength="100" onChange="ActualizarDatos('ItemName',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if (($row['LineStatus'] == 'C') || ($type == 2)) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="UnitMsr<?php echo $i; ?>" name="UnitMsr[]" class="form-control" readonly value="<?php echo $row['UnitMsr']; ?>"></td>
			<td><input size="15" type="text" id="Quantity<?php echo $i; ?>" name="Quantity[]" class="form-control" value="<?php echo number_format($row['Quantity'], 2); ?>" onChange="ActualizarDatos('Quantity',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" <?php if (($row['LineStatus'] == 'C') || ($type == 2)) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="CantInicial<?php echo $i; ?>" name="CantInicial[]" class="form-control" value="<?php echo number_format($row['CantInicial'], 2); ?>" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" readonly></td>

			<td>
				<select id="WhsCode<?php echo $i; ?>" name="WhsCode[]" class="form-control select2" onChange="ActualizarDatos('WhsCode',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || (!PermitirFuncion(402))) {echo "disabled='disabled'";}?>>
				  <option value="">(NINGUNO)</option>
				  <?php while ($row_Almacen = sqlsrv_fetch_array($SQL_Almacen)) {?>
						<option value="<?php echo $row_Almacen['WhsCode']; ?>" <?php if ((isset($row['WhsCode'])) && (strcmp($row_Almacen['WhsCode'], $row['WhsCode']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Almacen['WhsName']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td><input size="15" type="text" id="OnHand<?php echo $i; ?>" name="OnHand[]" class="form-control" value="<?php echo number_format($row['OnHand'], 2); ?>" readonly></td>
			<td><input size="50" type="text" id="CDU_SucursalCliente<?php echo $i; ?>" name="CDU_SucursalCliente[]" class="form-control" value="<?php echo $row['CDU_SucursalCliente']; ?>" onChange="ActualizarDatos('CDU_SucursalCliente',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" maxlength="100" readonly></td>
			<td><?php if (($row['TreeType'] != "T") || (($row['TreeType'] == "T") && ($row['LineNum'] != 0))) {?>
				<input size="50" type="text" id="CDU_AreasControladas<?php echo $i; ?>" name="CDU_AreasControladas[]" class="form-control" value="<?php echo $row['CDU_AreasControladas']; ?>" onChange="ActualizarDatos('CDU_AreasControladas',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if (($row['LineStatus'] == 'C') || ($type == 2)) {echo "readonly";}?>>
				<?php }?>
			</td>
			<td><input size="30" type="text" id="CDU_OrdenServicio<?php echo $i; ?>" name="CDU_OrdenServicio[]" class="form-control" value="<?php echo $row['CDU_OrdenServicio']; ?>" onChange="ActualizarDatos('CDU_OrdenServicio',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" maxlength="100" readonly></td>

			<td>
				<select id="OcrCode<?php echo $i; ?>" name="OcrCode[]" class="form-control select2" onChange="ActualizarDatos('OcrCode',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || (!PermitirFuncion(402))) {echo "disabled='disabled'";}?>>
				  <option value="">(NINGUNO)</option>
				  <?php while ($row_Dim1 = sqlsrv_fetch_array($SQL_Dim1)) {?>
						<option value="<?php echo $row_Dim1['OcrCode']; ?>" <?php if ((isset($row['OcrCode'])) && (strcmp($row_Dim1['OcrCode'], $row['OcrCode']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Dim1['OcrName']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td>
				<select id="OcrCode2<?php echo $i; ?>" name="OcrCode2[]" class="form-control select2" onChange="ActualizarDatos('OcrCode2',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || (!PermitirFuncion(402))) {echo "disabled='disabled'";}?>>
				  <option value="">(NINGUNO)</option>
				  <?php while ($row_Dim2 = sqlsrv_fetch_array($SQL_Dim2)) {?>
						<option value="<?php echo $row_Dim2['OcrCode']; ?>" <?php if ((isset($row['OcrCode2'])) && (strcmp($row_Dim2['OcrCode'], $row['OcrCode2']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Dim2['OcrName']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td>
				<select id="OcrCode3<?php echo $i; ?>" name="OcrCode3[]" class="form-control select2" onChange="ActualizarDatos('OcrCode3',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || (!PermitirFuncion(402))) {echo "disabled='disabled'";}?>>
				  <option value="">(NINGUNO)</option>
				  <?php while ($row_Dim3 = sqlsrv_fetch_array($SQL_Dim3)) {?>
						<option value="<?php echo $row_Dim3['OcrCode']; ?>" <?php if ((isset($row['OcrCode3'])) && (strcmp($row_Dim3['OcrCode'], $row['OcrCode3']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Dim3['OcrName']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td>
				<select id="PrjCode<?php echo $i; ?>" name="PrjCode[]" class="form-control select2" onChange="ActualizarDatos('PrjCode',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || (!PermitirFuncion(402))) {echo "disabled='disabled'";}?>>
					<option value="">(NINGUNO)</option>
				  <?php while ($row_Proyecto = sqlsrv_fetch_array($SQL_Proyecto)) {?>
						<option value="<?php echo $row_Proyecto['IdProyecto']; ?>" <?php if ((isset($row['PrjCode'])) && (strcmp($row_Proyecto['IdProyecto'], $row['PrjCode']) == 0)) {echo "selected=\"selected\"";}?>><?php echo $row_Proyecto['DeProyecto']; ?></option>
				  <?php }?>
				</select>
			</td>

			<td> <!-- SMM, 22/02/2022 -->
				<select id="EmpVentas<?php echo $i; ?>" name="EmpVentas[]" class="form-control select2" onChange="ActualizarDatos('EmpVentas',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" <?php if ($row['LineStatus'] == 'C' || (!PermitirFuncion(402))) {echo "disabled='disabled'";}?>>
						<option value="">(NINGUNO)</option>
					<?php while ($row_EmpleadosVentas = sqlsrv_fetch_array($SQL_EmpleadosVentas)) {?>
						<option value="<?php echo $row_EmpleadosVentas['ID_EmpVentas']; ?>" <?php if ((isset($row['EmpVentas'])) && (strcmp($row_EmpleadosVentas['ID_EmpVentas'], $row['EmpVentas']) == 0)) {echo "selected=\"selected\"";}?>>
							<?php echo $row_EmpleadosVentas['DE_EmpVentas']; ?>
						</option>
					<?php }?>
				</select>
			</td>

			<td><input size="50" type="text" id="FreeTxt<?php echo $i; ?>" name="FreeTxt[]" class="form-control" value="<?php echo $row['FreeTxt']; ?>" onChange="ActualizarDatos('FreeTxt',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" maxlength="100" <?php if (($row['LineStatus'] == 'C') || ($type == 2)) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="Price<?php echo $i; ?>" name="Price[]" class="form-control" value="<?php echo number_format($row['Price'], 2); ?>" onChange="ActualizarDatos('Price',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" <?php if (($row['LineStatus'] == 'C') || ($type == 2)) {echo "readonly";}?>></td>
			<td><input size="15" type="text" id="PriceTax<?php echo $i; ?>" name="PriceTax[]" class="form-control" value="<?php echo number_format($row['PriceTax'], 2); ?>" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" readonly><input type="hidden" id="TarifaIVA<?php echo $i; ?>" name="TarifaIVA[]" value="<?php echo number_format($row['TarifaIVA'], 0); ?>"><input type="hidden" id="VatSum<?php echo $i; ?>" name="VatSum[]" value="<?php echo number_format($row['VatSum'], 2); ?>"></td>
			<td><input size="15" type="text" id="DiscPrcnt<?php echo $i; ?>" name="DiscPrcnt[]" class="form-control" value="<?php echo number_format($row['DiscPrcnt'], 2); ?>" onChange="ActualizarDatos('DiscPrcnt',<?php echo $i; ?>,<?php echo $row['LineNum']; ?>);" onBlur="CalcularTotal(<?php echo $i; ?>);" onKeyUp="revisaCadena(this);" onKeyPress="return justNumbers(event,this.value);" <?php if (($row['LineStatus'] == 'C') || ($type == 2)) {echo "readonly";}?>></td>
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
			<td><input size="15" type="text" id="OnHandNew" name="OnHandNew" class="form-control"></td>
			<td><input size="50" type="text" id="CDU_SucursalClienteNew" name="CDU_SucursalClienteNew" class="form-control"></td>
			<td><input size="50" type="text" id="CDU_AreasControladasNew" name="CDU_AreasControladasNew" class="form-control"></td>
			<td><input size="30" type="text" id="CDU_OrdenServicioNew" name="CDU_OrdenServicioNew" class="form-control"></td>
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
	console.log(`CalcularTotal(${line})`);

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

			TotalLinea.value=number_format(SubTotalLinea-TotalDesc, 2);
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
				return "ajx_buscar_datos_json.php?type=12&data="+phrase+"&whscode=<?php echo $Almacen; ?>&tipodoc=2";
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
						url: "registro.php?P=35&doctype=15&item="+IdArticulo+"&whscode="+CodAlmacen+"&cardcode=<?php echo $CardCode; ?>",
						<?php } else {?>
						url: "registro.php?P=35&doctype=16&item="+IdArticulo+"&whscode="+CodAlmacen+"&cardcode=0&id=<?php echo base64_decode($_GET['id']); ?>&evento=<?php echo base64_decode($_GET['evento']); ?>",
						<?php }?>
						success: function(response){
							window.location.href="detalle_factura_venta.php?<?php echo $_SERVER['QUERY_STRING']; ?>";
						}
					});
				}
			}
		};
		<?php if ($sw == 1 && $Estado == 1 && $type == 1 && PermitirFuncion(411)) {?>
		$("#ItemCodeNew").easyAutocomplete(options);
	 	<?php }?>
	});
</script>
</body>
</html>
<?php
sqlsrv_close($conexion);
?>