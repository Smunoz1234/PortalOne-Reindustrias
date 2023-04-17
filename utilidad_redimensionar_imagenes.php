<?php require_once "includes/conexion.php";
PermitirAcceso(1801);

$Title = "Redimensionar imágenes";
$persistir = true;

$msg_error = ""; // Mensaje de error
$sw_error = 0; // Bandera de error

$ancho = $_POST["ancho"] ?? "";
$alto = $_POST["alto"] ?? "";

echo $ancho;
echo "<br>" . $alto;

$dir = CrearObtenerDirTemp();
if (($ancho != "") && ($alto != "")) {
    $test = "capo-vehiculo.jpg";
    RedimensionarImagen($test, ($dir . $test), $ancho, $alto);
}

print_r($_POST);
?>

<!DOCTYPE html>
<html><!-- InstanceBegin template="/Templates/PlantillaPrincipal.dwt.php" codeOutsideHTMLIsLocked="false" -->

<head>
<?php include "includes/cabecera.php";?>
<!-- InstanceBeginEditable name="doctitle" -->
<title><?php echo $Title; ?> | <?php echo NOMBRE_PORTAL; ?></title>
<!-- InstanceEndEditable -->
<!-- InstanceBeginEditable name="head" -->

<script type="text/javascript">
	$(document).ready(function() {
		// Espacio para Scripts necesarios.
	});
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
                            <a href="#">Utilidades PortalOne</a>
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
              <form action="utilidad_redimensionar_imagenes.php" method="post" class="form-horizontal" enctype="multipart/form-data" id="utilidadForm">
				<!-- IBOX, Inicio -->
				<div class="ibox">
					<div class="ibox-title bg-success">
						<h5 class="collapse-link"><i class="fa fa-arrows-alt"></i> Opciones de Redimensión</h5>
						 <a class="collapse-link pull-right" style="color: white;">
							<i class="fa fa-chevron-up"></i>
						</a>
					</div>
					<div class="ibox-content">
						<div class="form-group">
							<div class="col-lg-6">
								<label for="ancho">Ancho en píxeles:</label>
								<input type="number" name="ancho" id="ancho" class="form-control" required>
							</div>
							<div class="col-lg-6">
								<label for="alto">Alto en píxeles:</label>
								<input type="number" name="alto" id="alto" class="form-control" required>
							</div>
						</div>
					</div>
				</div>
				<!-- IBOX, Fin -->
			</form>


			<div class="ibox">
				<div class="ibox-title bg-success">
					<h5 class="collapse-link"><i class="fa fa-image"></i> Zona de Carga de Imágenes</h5>
					<a class="collapse-link pull-right" style="color: white;">
						<i class="fa fa-chevron-up"></i>
					</a>
				</div>
				<div class="ibox-content">

					<?php if (false) {?>
						<!-- Código obsoleto -->
					<?php } else {echo "<!--p>Sin anexos.</p-->";}?>

					<div class="row">
						<form action="upload.php?<?php if ($persistir && false) {echo "persistent=redimensionar_imagenes";}?>" class="dropzone" id="dropzoneForm" name="dropzoneForm">
							<?php //if (($sw_error == 0) && !$persistir) {LimpiarDirTemp();}?>

							<div class="fallback">
								<input name="File" id="File" type="file" form="dropzoneForm" />
							</div>
						</form>
					</div>

				</div><!-- ibox-content -->
			</div> <!-- ibox -->

			<!-- Botones de acción al final del formulario, SMM -->
			   <div class="form-group">
					<div class="col-lg-9">
						<button class="btn btn-primary" form="utilidadForm" type="submit" id="Ejecutar"><i class="fa fa-check"></i> Redimensionar imágenes</button>
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
$(document).ready(function(){
	var anexos = []; // SMM, 11/04/2023

	// SMM, 11/04/2023
	$('#utilidadForm').on('submit', function (event) {
		event.preventDefault();

		console.log(event.target);
	});

	$("#recepcionForm").validate({
		submitHandler: function(form){
			if(bandera_fechas) {
				Swal.fire({
					"title": "¡Ha ocurrido un error!",
					"text": "La fecha de ingreso no puede superar a la fecha de entrega.",
					"icon": "warning"
				});
			} else {
				Swal.fire({
					title: "¿Desea continuar con el registro?",
					icon: "question",
					showCancelButton: true,
					confirmButtonText: "Si, confirmo",
					cancelButtonText: "No"
				}).then((result) => {
					if (result.isConfirmed) {
						$('.ibox-content').toggleClass('sk-loading', true); // Carga iniciada.

						let formData = new FormData(form);
						Object.entries(photos).forEach(([key, value]) => formData.append(key, value));
						Object.entries(anexos).forEach(([key, value]) => formData.append(`Anexo${key}`, value));

						// Agregar valores de las listas
						formData.append("id_llamada_servicio", $("#id_llamada_servicio").val());
						formData.append("id_marca", $("#id_marca").val());
						formData.append("id_linea", $("#id_linea").val());
						formData.append("id_annio", $("#id_annio").val());
						formData.append("id_color", $("#id_color").val());

						let json = Object.fromEntries(formData);
						localStorage.recepcionForm = JSON.stringify(json);

						console.log("Line 1790", json);

						// Inicio, AJAX
						$.ajax({
							url: 'frm_recepcion_vehiculo_ws.php',
							type: 'POST',
							data: formData,
							processData: false,  // tell jQuery not to process the data
							contentType: false,   // tell jQuery not to set contentType
							success: function(response) {
								console.log("Line 1273", response);

								try {
									let json_response = JSON.parse(response);
									Swal.fire(json_response).then(() => {
										if (json_response.hasOwnProperty('return')) {
											window.location = json_response.return;
										}
									});
								} catch (error) {
									console.log("Line 1283", error);
								}

								$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
							},
							error: function(response) {
								console.error("server error")
								console.error(response);

								$('.ibox-content').toggleClass('sk-loading', false); // Carga terminada.
							}
						});
						// Fin, AJAX
					} else {
						console.log("Registro NO confirmado.")
					}
				}); // SMM, 14/06/2022
			}
		}
	});

	$(".alkin").on('click', function(){
		$('.ibox-content').toggleClass('sk-loading');
	});

	$(".select2").select2();
});
</script>

<script>
Dropzone.options.dropzoneForm = {
	paramName: "File", // The name that will be used to transfer the file
	maxFilesize: "<?php echo ObtenerVariable("MaxSizeFile"); ?>", // MB
	maxFiles: "<?php echo ObtenerVariable("CantidadArchivos"); ?>",
	uploadMultiple: true,
	addRemoveLinks: true,
	dictRemoveFile: "Quitar",
	acceptedFiles: "image/*", // solo se permiten archivos de tipo imagen
	dictDefaultMessage: "<strong>Haga clic aquí para cargar las imágenes</strong><br>Tambien puede arrastrarlas hasta aquí<br><h4><small>(Máximo <?php echo ObtenerVariable("CantidadArchivos"); ?> imágenes a la vez)<small></h4>",
	dictFallbackMessage: "Tu navegador no soporta cargue de archivos mediante arrastrar y soltar",
	removedfile: function(file) {
			let indice = anexos.indexOf(file.name);
			if (indice !== -1) {
				anexos.splice(indice, 1);
			}

			// Eliminar el archivo cargado por dropzone.
			<?php if (!$persistir) {?>
				$.get( "includes/procedimientos.php", {
					type: "3",
					nombre: file.name
				}).done(function( data ) {
					var _ref;
					return (_ref = file.previewElement) !== null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
				});
			<?php }?>
		},
	init: function(file) {
		this.on("addedfile", file => {
			anexos.push(file.name); // SMM, 16/02/2022
			console.log("Line 1057, Dropzone(addedfile)", file.name);

			// Desactivar formulario mientras se cargan las imágenes.
			$("#Ejecutar").prop("disabled", true);
    	});
	},
	queuecomplete: function() {
		console.log("Line 1087, Dropzone(queuecomplete)");

		// Activar formulario cuando se cargan las imágenes.
		$("#Ejecutar").prop("disabled", false);
	}
};
</script>

<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>