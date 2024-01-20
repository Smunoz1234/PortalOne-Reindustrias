<?php 
if((isset($_POST['id'])&&$_POST['id']!="")||(isset($_GET['id'])&&$_GET['id']!="")){
	require_once("includes/conexion.php");
	require_once("includes/conect_ws_rep.php");
	
	if(isset($_POST['id'])&&$_POST['id']!=""){
		$ID=base64_decode($_POST['id']);
		$Type=base64_decode($_POST['type']);
	}else{
		$ID=base64_decode($_GET['id']);
		$Type=base64_decode($_GET['type']);
	}
	
	$NombreArchivo="";
	$size=0;
	$SrvRuta="";
	$ZipMode=0;
	
	if(isset($_REQUEST['zip'])&&($_REQUEST['zip'])==base64_encode('1')){
		$ZipMode=1;
	}
	
	#Ruta local de archivos de SAP	
	$carp_archivos=ObtenerVariable("RutaArchivos");
	$RutaLocal=$_SESSION['BD']."/".$carp_archivos."/InformesSAP/";
	
	// OBTENER RUTA DE DESCARGAR DEL ARCHIVO DEL SERVIDOR
	
	/******* LINUX *******/
	// $Dominio=DOMINIO_WIN;
	// $User=USER_WIN;
	// $Pass=PASS_WIN;
	// $Path=PATH_WIN;
	// $SrvRuta = "smb://".$Dominio.$User.$Pass.$Path;

	/******* WINDOWS *******/				
	$SrvRuta = $RutaLocal;
	
	if($Type==1){//Informes SAP B1
		//Campos
		$SQL_Campos=Seleccionar('uvw_tbl_ParamInfSAP_Campos','*',"ID_Categoria='".$ID."'");
		$Num_Campos=sqlsrv_num_rows($SQL_Campos);

		$SQL_WS=Seleccionar('uvw_tbl_ParamInfSAP_WebServices','*',"ID_Categoria=".$ID);
		$row_WS=sqlsrv_fetch_array($SQL_WS);
	}	
		
	try{
		if($Type==2){//Layouts de SAP B1
			if($ID==15){//Formatos SAP B1
				if($ZipMode==1){//Comprimir los archivos descargados
					
//					$Facts=explode("[*]",$_GET['DocKey']);//Lista de los números de las facturas.
//					$IdSeries=explode("[*]",$_GET['IdFrm']);//Lista de las series de las facturas
//					$Files=array();//Donde se almacenan los archivos generados, los cuales se van a comprimir.
					
					//Leer los archivos, los cuales se pasan por JSON
					$json=($_REQUEST['file']);
					$archivos=json_decode($json, true);
					$Files=array();//Donde se almacenan los archivos generados, los cuales se van a comprimir.
					
					$Count=count($archivos);					
					$i=0;
					while($i<$Count){
						$Parametros=array(
							'pIdObjeto' => $archivos[$i]['Obj'],//Código del objeto
							'pIdFormato' => $archivos[$i]['Serie'],//Id del formato o la serie, en la misma poscicion que la de la factura
							'pDockey' => $archivos[$i]['Num'],
							'pID' => (isset($_REQUEST['idreg'])) ? base64_decode($_REQUEST['idreg']) : '',
							'pUsuario' => $_SESSION['User']
						);
						
//						print_r($Parametros);
//						exit();

						$result = $Client->CrearFormatoSAP($Parametros);
						if(is_soap_fault($result)){
							trigger_error("Fallo IntSAPB1: (Codigo: {$result->faultcode}, Mensaje: {$result->faultstring})", E_USER_ERROR);
						}

						$Respuesta=$Client->__getLastResponse();

						$Contenido=new SimpleXMLElement($Respuesta,0,false,"s",true);

						$espaciosDeNombres = $Contenido->getNamespaces(true);
						$Nodos = $Contenido->children($espaciosDeNombres['s']);
						$Nodo=	$Nodos->children($espaciosDeNombres['']);
						$Nodo2=	$Nodo->children($espaciosDeNombres['']);
						//echo $Nodo2[0];
						try{
							$Archivo=json_decode($Nodo2[0],true);
							//$Archivo=explode("#",$Nodo2[0]);
							if($Archivo['ID_Respuesta']=="0"){
								//InsertarLog(1, 0, 'Error al generar el informe');
								throw new Exception('Error al generar el informe. Error de WebServices');		
							}
						}catch (Exception $e){
							echo 'Excepción capturada: ',  $e->getMessage(), "\n";
							InsertarLog(1, 501, 'Excepción capturada: '.$e->getMessage());//501, cod de SAP Download
						}
						$Files[$i]=$Archivo['DE_Respuesta'];
						
						if(SO=="Linux"){//Copiar los archivos a la ruta local para comprimirlos localmente
							if(!copy($SrvRuta.$Archivo['DE_Respuesta'],$RutaLocal.$Archivo['DE_Respuesta'])){
								exit('No se pudo copiar el archivo '.$Archivo['DE_Respuesta']);			
							}
						}
						$i++;
					}
					
					//Crear archivo ZIP e insertar los archivos
					$zip = new ZipArchive();
					$zipName=date('YmdHi')."_".$_SESSION['CodUser'].".zip";
					$filezip = $RutaLocal.$zipName;
					//echo $filezip;
					//exit();

					if($zip->open($filezip, ZIPARCHIVE::CREATE)===TRUE){
						$Count=count($Files);
						$i=0;
						//$zip->close();
						while($i<$Count){
							$zip->addFile($RutaLocal.$Files[$i],$Files[$i]);
							//echo "Se agregó: ".$SrvRuta.$Files[$i]."\n";
							$i++;
						}
						//exit();
						$zip->close();
						$Archivo['DE_Respuesta']=$zipName;
					}else{
						exit("No se puede abrir el archivo $filezip\n");
					}
					
				}else{					
					$Parametros=array(
						'pIdObjeto' => base64_decode($_REQUEST['ObType']),//Codigo del objeto
						'pIdFormato' => base64_decode($_REQUEST['IdFrm']),//Id del formato (Serie)
						'pDockey' => base64_decode($_REQUEST['DocKey']),//DocEntry del documento
						'pID' => (isset($_REQUEST['IdReg'])) ? base64_decode($_REQUEST['IdReg']) : '',//Id de la tabla de formatos (para cuando hay varios formatos de la misma serie)
						'pUsuario' => $_SESSION['User']
					);

					// print_r($Parametros);
					// exit();

					$result = $Client->CrearFormatoSAP($Parametros);
					if(is_soap_fault($result)){
						trigger_error("Fallo IntSAPB1: (Codigo: {$result->faultcode}, Mensaje: {$result->faultstring})", E_USER_ERROR);
					}

					$Respuesta=$Client->__getLastResponse();

					$Contenido=new SimpleXMLElement($Respuesta,0,false,"s",true);

					$espaciosDeNombres = $Contenido->getNamespaces(true);
					$Nodos = $Contenido->children($espaciosDeNombres['s']);
					$Nodo=	$Nodos->children($espaciosDeNombres['']);
					$Nodo2=	$Nodo->children($espaciosDeNombres['']);
					//echo $Nodo2[0];
					try{
						$Archivo=json_decode($Nodo2[0],true);
						//$Archivo=explode("#",$Nodo2[0]);
						if($Archivo['ID_Respuesta']=="0"){
							//InsertarLog(1, 0, 'Error al generar el informe');
							throw new Exception('Error al generar el informe. Error de WebServices');		
						}
					}catch (Exception $e){
						echo 'Excepción capturada: ',  $e->getMessage(), "\n";
						InsertarLog(1, 501, 'Excepción capturada: '.$e->getMessage());//501, cod de SAP Download
					}	
				}
				
			}
		}
		
	}catch(SoapFault $ex){
		echo "Fault code: {$ex->faultcode}"."<br>";
		echo "Fault string: {$ex->faultstring}"."<br>";
		if ($Client != null){
			$Client = null;
		}
		exit();
	}
	try{
		
		//BUSCAR ARCHIVO PARA DESCARGAR
		if($ZipMode==1){
			$filename = $filezip;
		}else{
			$filename = $SrvRuta.$Archivo['DE_Respuesta'];
			//echo $filename;
			//exit();
		}
		
		
		$NombreArchivo=$Archivo['DE_Respuesta'];
		$size = filesize($filename);
		//echo $filename;
		//exit();

		header("Content-Transfer-Encoding: binary");
		//header('Content-type: application/octet-stream', true);
		header('Content-type: application/pdf', true);
		header("Content-Type: application/force-download"); 
		header('Content-Disposition: attachment; filename="'.$NombreArchivo.'"');
		header("Content-Length: $size");
		readfile($filename);

		//echo $filename;
	}catch (Exception $e) {
		echo 'Excepción capturada: ',  $e->getMessage(), "\n";
		InsertarLog(1, 501, 'Excepción capturada: '.error_get_last());
	}
	
}
