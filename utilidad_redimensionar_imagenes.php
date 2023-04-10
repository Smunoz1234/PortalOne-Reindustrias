<?php require_once "includes/conexion.php";
PermitirAcceso(1801);
$Title = "Redimensionar imágenes";

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

if (isset($_POST['swError']) && ($_POST['swError'] != "")) { //Para saber si ha ocurrido un error.
    $sw_error = $_POST['swError'];
} else {
    $sw_error = 0;
}

$dir = CrearObtenerDirTemp();
$dir_firma = CrearObtenerDirTempFirma();
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
              <form action="frm_recepcion_vehiculo.php" method="post" class="form-horizontal" enctype="multipart/form-data" id="recepcionForm">
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
						<form action="upload.php?persistent=recepcion_vehiculos" class="dropzone" id="dropzoneForm" name="dropzoneForm">
							<?php //if ($sw_error == 0) {LimpiarDirTemp();}?>
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
						<?php if ($type_frm == 0) {?>
							<button class="btn btn-primary" form="recepcionForm" type="submit" id="Crear"><i class="fa fa-check"></i> Redimensionar imágenes</button>
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
var anexos = []; // SMM, 16/02/2022

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
			var indice = anexos.indexOf(file.name);
			if (indice !== -1) {
				anexos.splice(indice, 1);
			}

			$.get( "includes/procedimientos.php", {
				type: "3",
				nombre: file.name
			}).done(function( data ) {
				var _ref;
				return (_ref = file.previewElement) !== null ? _ref.parentNode.removeChild(file.previewElement) : void 0;
			});
		},
	init: function(file) {
		this.on("addedfile", file => {
			anexos.push(file.name); // SMM, 16/02/2022
			console.log("Line 1057, Dropzone(addedfile)", file.name);

			// SMM, 28/09/2022
			$("#Crear").prop("disabled", true);
    	});
	},
	queuecomplete: function() {
		console.log("Line 1087, Dropzone(queuecomplete)");

		// SMM, 28/09/2022
		$("#Crear").prop("disabled", false);
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

	console.log("Line 1073, uploadImage", file);
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
				url: 'upload_image.php?persistent=recepcion_vehiculos',
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
						console.log("Line 1100, testImage", photo_route);

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
	maxLength('observaciones'); // SMM, 02/03/2022

	var bandera_fechas = false; // SMM, 25/02/2022
	$('#recepcionForm').on('submit', function (event) {
		// Stiven Muñoz Murillo, 08/02/2022
		event.preventDefault();

		// Stiven Muñoz Murillo, 25/02/2022
		let d1 = new Date(`${$('#fecha_ingreso').val()} ${$('#hora_ingreso').val()}`);
		let d2 = new Date(`${$('#fecha_aprox_entrega').val()} ${$('#hora_aprox_entrega').val()}`);

		console.log(d1);
		console.log(d2);

		// Stiven Muñoz Murillo, 25/02/2022
		bandera_fechas = (d1 > d2) ? true:false;
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

<!-- InstanceEndEditable -->
</body>

<!-- InstanceEnd --></html>
<?php sqlsrv_close($conexion);?>