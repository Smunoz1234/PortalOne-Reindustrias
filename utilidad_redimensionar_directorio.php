<?php require_once "includes/conexion.php";
PermitirAcceso(1801);

$sw = 0;
$Title = "Redimensionar imágenes desde directorio";

$msg_error = ""; // Mensaje de error
$sw_error = 0; // Bandera de error

$rutaPrincipal = ObtenerVariable("RutaDirectorioImagenesResize");

$entrada = $_POST["dirOrigen"] ?? "";
$entrada = ($rutaPrincipal . $entrada);
if (!is_dir($entrada) && ($entrada != "")) {
	$msg_error = "El directorio de origen no existe.";

	$sw_error = 1;
} else {
	$salida = $_POST["dirDestino"] ?? "";
	$salida = CrearObtenerDirRuta($rutaPrincipal . $salida);

	$ancho = $_POST["ancho"] ?? "";
	$alto = $_POST["alto"] ?? "";

	if (($entrada != "") && ($salida != "") && ($ancho != "") && ($alto != "")) {
		$archivos = array_diff(scandir($entrada), array('.', '..'));

		if (empty($archivos)) {
			$msg_error = "El directorio de origen esta vacío.";

			$sw_error = 1;
		}

		// SMM, 20/04/2023
		$CambiarNombre = $_POST["CambiarNombre"] ?? "";

		if ($CambiarNombre == "Y") {
			$SQL_Nombres = Seleccionar("tbl_CambioNombreImagenes", "*");
			while ($row_Nombre = sqlsrv_fetch_array($SQL_Nombres)) {
				$archivo = $row_Nombre["NombreArchivo"];
				$entrada_archivo = "$entrada/$archivo";

				if (file_exists($entrada_archivo)) {
					$nuevo_archivo = $row_Nombre["NombreArchivoNuevo"];
					$resultado = RedimensionarImagen($archivo, $entrada_archivo, $ancho, $alto, "$salida/$nuevo_archivo");

					if ($resultado != "OK") {
						$msg_error = $resultado;

						$sw_error = 1;
					}
				} else {
					$msg_error = "Algunos archivos especificados no existen.";
					$msg_error .= str_replace("\\", "/", $entrada_archivo);

					$sw_error = 1;
				}
			}
		} else {
			foreach ($archivos as &$archivo) {
				$resultado = RedimensionarImagen($archivo, "$entrada/$archivo", $ancho, $alto, "$salida/$archivo");

				if ($resultado != "OK") {
					$msg_error = $resultado;

					$sw_error = 1;
				}
			}
		}

		$sw = 1;
	}
}
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
	<?php include "includes/cabecera.php"; ?>
	<!-- InstanceBeginEditable name="doctitle" -->
	<title>
		<?php echo $Title; ?> |
		<?php echo NOMBRE_PORTAL; ?>
	</title>
	<!-- InstanceEndEditable -->
	<!-- InstanceBeginEditable name="head" -->

	<script type="text/javascript">
		$(document).ready(function () {
			const form = document.getElementById('utilidadForm');

			form.addEventListener('submit', function (event) {
				event.preventDefault(); // Detener el envío automático del formulario

				Swal.fire({
					title: "¿Desea continuar con el proceso?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						$('.ibox-content').toggleClass('sk-loading', true); // Carga iniciada.

						form.submit(); // Enviar el formulario manualmente

						// $('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
					} else {
						console.log("Proceso NO confirmado.")
					}
				}); // SMM, 19/04/2023
			});

			<?php if (($sw == 1) && ($sw_error == 0)) { ?>
				Swal.fire({
					"title": "¡Listo!",
					"text": "Las imágenes se redimensionaron correctamente.",
					"icon": "success"
				});
			<?php } ?>

			<?php if ($sw_error == 1) { ?>
				Swal.fire({
					"title": "¡Advertencia!",
					"text": "<?php echo $msg_error; ?>",
					"icon": "warning"
				});
			<?php } ?>
		});
	</script>
	<!-- InstanceEndEditable -->
</head>

<body>

	<div id="wrapper">

		<?php include "includes/menu.php"; ?>

		<div id="page-wrapper" class="gray-bg">
			<?php include "includes/menu_superior.php"; ?>
			<!-- InstanceBeginEditable name="Contenido" -->
			<div class="row wrapper border-bottom white-bg page-heading">
				<div class="col-sm-8">
					<h2>
						<?php echo $Title; ?>
					</h2>
					<ol class="breadcrumb">
						<li>
							<a href="#">Utilidades PortalOne</a>
						</li>
						<li class="active">
							<strong>
								<?php echo $Title; ?>
							</strong>
						</li>
					</ol>
				</div>
			</div>

			<div class="wrapper wrapper-content">
				<div class="ibox-content">
					<?php include "includes/spinner.php"; ?>
					<div class="row">
						<div class="col-lg-12">
							<form action="utilidad_redimensionar_directorio.php" method="post" class="form-horizontal"
								enctype="multipart/form-data" id="utilidadForm">
								<!-- IBOX, Inicio -->
								<div class="ibox">
									<div class="ibox-title bg-success">
										<h5 class="collapse-link"><i class="fa fa-folder"></i> Opciones de Directorios
										</h5>
										<a class="collapse-link pull-right" style="color: white;">
											<i class="fa fa-chevron-up"></i>
										</a>
									</div>

									<div class="ibox-content">
										<div class="form-group">
											<label class="col-lg-3 control-label">
												Ruta Directorio para imagenes resize<br>
												<span class="text-muted">RutaDirectorioImagenesResize</span>
											</label>
											<div class="col-lg-7">
												<input type="text" class="form-control"
													value="<?php echo $rutaPrincipal; ?>" readonly>
											</div>
											<div class="col-lg-2">
												<button type="button" class="btn btn-sm btn-info btn-circle"
													data-toggle="tooltip" data-html="true"
													title="Este es el valor del parámetro general para esta funcionalidad, <b>Si esta vacío se tomaran las carpetas de la raíz.</b>"><i
														class="fa fa-info"></i></button>
											</div>
										</div>

										<br>
										<div class="form-group">
											<label class="col-lg-1 control-label" for="dirOrigen">Directorio
												origen</label>
											<div class="col-lg-3">
												<input type="text" name="dirOrigen" id="dirOrigen" class="form-control"
													value="dir_entrada" required readonly>
											</div>
											<div class="col-lg-2">
												<button type="button" class="btn btn-sm btn-info btn-circle"
													data-toggle="tooltip" data-html="true"
													title="Este directorio debe existir dentro de la ruta especificada y debe contener las imágenes que se quieren redimensionar."><i
														class="fa fa-info"></i></button>
											</div>

											<label class="col-lg-1 control-label" for="dirDestino">Directorio
												destino</label>
											<div class="col-lg-3">
												<input type="text" name="dirDestino" id="dirDestino"
													class="form-control" value="dir_salida" required readonly>
											</div>
											<div class="col-lg-2">
												<button type="button" class="btn btn-sm btn-info btn-circle"
													data-toggle="tooltip" data-html="true"
													title="Este es el directorio de salida de las imágenes que se redimensionan. <b>Si no existe se crea automaticamente.</b>"><i
														class="fa fa-info"></i></button>
											</div>
										</div>
									</div>

									<div class="ibox-title bg-success">
										<h5 class="collapse-link"><i class="fa fa-arrows-alt"></i> Opciones de
											Redimensión</h5>
										<a class="collapse-link pull-right" style="color: white;">
											<i class="fa fa-chevron-up"></i>
										</a>
									</div>

									<div class="ibox-content">
										<div class="form-group">
											<label class="col-lg-2 control-label">Cambiar nombre</label>
											<div class="col-lg-2">
												<label class="checkbox-inline i-checks"
													style="margin-right: 20px;"><input name="CambiarNombre"
														id="CambiarNombre" type="checkbox" value="Y" checked></label>
												<button type="button" class="btn btn-sm btn-info btn-circle"
													data-toggle="tooltip" data-html="true"
													title="Cambiar el nombre de las imágenes según la información de la tabla [CambioNombreImagenes], que se encuentra en la base de datos."><i
														class="fa fa-info"></i></button>
											</div>
										</div>

										<div class="form-group">
											<label class="col-lg-2 control-label" for="ancho">Ancho en píxeles</label>
											<div class="col-lg-4">
												<input type="number" name="ancho" id="ancho" class="form-control"
													required>
											</div>

											<label class="col-lg-2 control-label" for="alto">Alto en píxeles</label>
											<div class="col-lg-4">
												<input type="number" name="alto" id="alto" class="form-control"
													required>
											</div>
										</div>
									</div>
								</div>
								<!-- IBOX, Fin -->
							</form>

							<!-- Botones de acción al final del formulario, SMM -->
							<div class="form-group">
								<div class="col-lg-9">
									<button class="btn btn-primary" form="utilidadForm" type="submit" id="Ejecutar"><i
											class="fa fa-check"></i> Redimensionar imágenes</button>
									<a href="<?php echo $return; ?>" class="alkin btn btn-outline btn-default"><i
											class="fa fa-arrow-circle-o-left"></i> Regresar</a>
								</div>
							</div>
							<!-- Pendiente a agregar al formulario, SMM -->
						</div>
					</div>
				</div>
			</div>
			<!-- InstanceEndEditable -->
			<?php include "includes/footer.php"; ?>

		</div>
	</div>
	<?php include "includes/pie.php"; ?>
	<!-- InstanceBeginEditable name="EditRegion4" -->

	<script>
		$(document).ready(function () {
			// SMM, 19/04/2022
			$('[data-toggle="tooltip"]').tooltip();
			$('.i-checks').iCheck({
				checkboxClass: 'icheckbox_square-green',
				radioClass: 'iradio_square-green',
			});

			$('#utilidadForm').on('submit', function (event) {
				// event.preventDefault();
				console.log(event.target.ancho);
			});

			$(".alkin").on('click', function () {
				$('.ibox-content').toggleClass('sk-loading');
			});

			$(".select2").select2();
		});
	</script>

	<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd -->

</html>
<?php sqlsrv_close($conexion); ?>