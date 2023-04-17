<?php require_once "includes/conexion.php";
PermitirAcceso(1801);

$sw = 0;
$Title = "Redimensionar imágenes desde directorio";

$msg_error = ""; // Mensaje de error
$sw_error = 0; // Bandera de error

$entrada = $_POST["dirOrigen"] ?? "";
$salida = $_POST["dirDestino"] ?? "";

$ancho = $_POST["ancho"] ?? "";
$alto = $_POST["alto"] ?? "";

if (($entrada != "") && ($salida != "") && ($ancho != "") && ($alto != "")) {
    $archivos = array_diff(scandir($entrada), array('.', '..'));

    foreach ($archivos as &$archivo) {
        RedimensionarImagen($archivo, "$entrada/$archivo", $ancho, $alto, "$salida/$archivo");
    }

    $sw = 1;
}
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
		<?php if ($sw == 1) {?>
			Swal.fire({
				"title": "¡Listo!",
				"text": "Las imágenes se redimensionaron correctamente.",
				"icon": "success"
			});
		<?php }?>
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
              <form action="utilidad_redimensionar_directorio.php" method="post" class="form-horizontal" enctype="multipart/form-data" id="utilidadForm">
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
								<label for="dirOrigen">Directorio origen:</label>
								<input type="text" name="dirOrigen" id="dirOrigen" class="form-control" value="dir_entrada" required>
							</div>
							<div class="col-lg-6">
								<label for="dirDestino">Directorio destino:</label>
								<input type="text" name="dirDestino" id="dirDestino" class="form-control" value="dir_salida" required>
							</div>
						</div>
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
$(document).ready(function() {
	$('#utilidadForm').on('submit', function (event) {
		// event.preventDefault();
		console.log(event.target.ancho);
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