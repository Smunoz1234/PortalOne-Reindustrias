<?php
require "includes/conexion.php";

// Enter the name of directory
$pathdir = CrearObtenerDirRuta("tmp_download") . "/";

LimpiarDirRuta("tmp_download");

// Enter the name to creating zipped directory
$zipcreated = $pathdir . "download.zip";

// Create new zip class
$zip = new ZipArchive;

$cant = 0;
if ($zip->open($zipcreated, ZipArchive::CREATE) === true) {

    // Store the path into the variable
    $dir = opendir($pathdir);

    while ($file = readdir($dir)) {
        $pathfile = $pathdir . $file;

        if (is_file($pathfile)) {
            $zip->addFile($pathfile, $file);
            $cant++;
        }
    }
    $zip->close();
}

if ($cant == 0) {
    echo "No hay archivos para descargar";
} else {
    header('Content-Type: application/zip');
    header('Content-disposition: attachment; filename=' . $zipcreated);
    header('Content-Length: ' . filesize($zipcreated));
    readfile($zipcreated);
}
