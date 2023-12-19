<?php
require_once("includes/conexion.php");
PermitirAcceso(1713);

$sw_error = 0;
$msg_error = "";

// Insertar datos
if (isset($_POST['P']) && ($_POST['P'] != "")) {
	try {
		$SQL_Campos = Seleccionar("tbl_FormularioCarAdvisor_ParametrosPorDefecto", "*");
		
		while ($row_Campos = sqlsrv_fetch_array($SQL_Campos)) {
			if (isset($_POST[$row_Campos['parametro_caradvisor']])) {
				$id_param = $row_Campos['id_parametro_caradvisor'] ?? "";
				$past_value = $row_Campos['valor_parametro'] ?? "";
				$next_value = $_POST[$row_Campos['parametro_caradvisor']] ?? "";
				$user = $_SESSION['CodUser'] ?? "";

				$Param = array(
					"'$id_param'",
					"'$past_value'",
					"'$next_value'",
					"'$user'",
				);
				$SQL_Param = EjecutarSP("sp_tbl_FormularioCarAdvisor_ParametrosPorDefecto", $Param);

				if (!$SQL_Param) {
					$sw_error = 1;
					$msg = "Error al actualizar la información";
				}
			}
		}

		if ($sw_error == 0) {
			header('Location:parametros_car_advisor.php?a=' . base64_encode("OK_PRUpd"));
		}
	} catch (Exception $e) {
		$sw_error = 1;
		$msg = $e->getMessage();
	}

}

// Parametros Car Advisor
$SQL = Seleccionar("tbl_FormularioCarAdvisor_ParametrosPorDefecto", "*");
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include_once("includes/cabecera.php"); ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>
		Parámetros Car Advisor | <?php echo NOMBRE_PORTAL; ?>
	</title>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->
	<style>
		.swal2-container {
			z-index: 9000;
		}
	</style>
	
	<?php
	if (isset($_GET['a']) && ($_GET['a'] == base64_encode("OK_PRUpd"))) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Listo!',
                text: 'Datos actualizados exitosamente.',
                icon: 'success'
            });
		});		
		</script>";
	}
	if (isset($sw_error) && ($sw_error == 1)) {
		echo "<script>
		$(document).ready(function() {
			Swal.fire({
                title: '¡Ha ocurrido un error!',
                text: '" . LSiqmlObs($msg_error) . "',
                icon: 'warning'
            });
		});		
		</script>";
	}
	?>
	<!-- InstanceEndEditable -->
</head>

<body>
	<div id="wrapper">
		<?php include_once("includes/menu.php"); ?>

		<div id="page-wrapper" class="gray-bg">
			<?php include_once("includes/menu_superior.php"); ?>
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>Parámetros Car Advisor</h2>
					<ol class="breadcrumb">
						<li>
							<a href="index1.php">Inicio</a>
						</li>
						<li>
							<a href="#">Administración</a>
						</li>
						<li>
							<a href="#">Parámetros de formularios</a>
						</li>
						<li class="active">
							<strong>Parámetros Car Advisor</strong>
						</li>
					</ol>
				</div>
			</div>

			<div class="wrapper wrapper-content">
				<div class="modal inmodal fade" id="myModal" tabindex="1" role="dialog" aria-hidden="true">
					<div class="modal-dialog modal-lg">
						<div class="modal-content" id="ContenidoModal">
							<!-- Gererated by JS -->
						</div>
					</div>
				</div>

				<form action="parametros_car_advisor.php" method="post" id="frmParam" class="form-horizontal">
					<div class="row">
						<div class="col-lg-12">
							<div class="ibox-content">
								<?php include("includes/spinner.php"); ?>
								<div class="form-group">
									<label class="col-xs-12">
										<h3 class="bg-success p-xs b-r-sm"><i class="fa fa-plus-square"></i> Acciones
										</h3>
									</label>
								</div>
								<div class="form-group">
									<div class="col-lg-6">
										<button class="btn btn-primary" type="submit" id="Guardar"><i
												class="fa fa-check"></i> Guardar datos</button>
									</div>
								</div>
								<input type="hidden" id="P" name="P" value="frmParam" />
							</div>
						</div>
					</div>
					<!-- /.row -->

					<br>
					<div class="row">
						<div class="col-lg-12">
							<div class="ibox-content">
								<?php include("includes/spinner.php"); ?>
								
								<div class="tabs-container">
									<ul class="nav nav-tabs">
										<li class="active">
											<a data-toggle="tab" href="#tab-1">
												<i class="fa fa-car"></i> Car Advisor
											</a>
										</li>
									</ul>
									
									<div class="tab-content">
										<div id="tab-1" class="tab-pane active">
											<br>
											<?php while ($row = sqlsrv_fetch_array($SQL)) { ?>
												<div class="form-group">
													<label class="col-lg-2 control-label">
														<?php echo $row['nombre_mostrar_parametro']; ?>
														
														<br>
														<span class="text-muted">
															<?php echo $row['parametro_caradvisor']; ?>
														</span>
													</label>

													<div class="col-lg-3">
														<input name="<?php echo $row['parametro_caradvisor']; ?>"
															type="text" class="form-control"
															id="<?php echo $row['id_parametro_caradvisor']; ?>"
															maxlength="100"
															value="<?php echo $row['valor_parametro']; ?>"
															autocomplete="off">
													</div>
												</div>
											<?php } ?>
										</div>
										<!-- /#tab-1 -->

									</div>
									<!-- /.tab-content -->
								</div>
								<!-- /.tabs-container -->
							</div>
						</div>
					</div>
					<!-- /.row -->
				</form>
			</div>
			<!-- /.wrapper-content -->

			<!-- InstanceEndEditable -->
			<?php include_once("includes/footer.php"); ?>

		</div>
		<!-- /#page-wrapper -->
	</div>
	<?php include_once("includes/pie.php"); ?>
	<!-- InstanceBeginEditable name="EditRegion4" -->
	<script>
		$(document).ready(function () {
			$("#frmParam").validate({
				submitHandler: function (form) {
					Swal.fire({
						title: "¿Está seguro que desea guardar los datos?",
						icon: "question",
						showCancelButton: true,
						confirmButtonText: "Si, confirmo",
						cancelButtonText: "No"
					}).then((result) => {
						if (result.isConfirmed) {
							$('.ibox-content').toggleClass('sk-loading', true);
							form.submit();
						}
					});
				}
			});

			$(".select2").select2();
			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});
		});
	</script>
	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>