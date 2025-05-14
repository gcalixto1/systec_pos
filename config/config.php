<?php
require_once("includes/class.php");

$queryEmpresa = $conexion->query("SELECT * FROM configuracion WHERE id = 1");
$Empresa = $queryEmpresa->fetch_assoc();

define("MH_API_URL", "https://apitest.dtes.mh.gob.sv/seguridad/auth");
define("MH_API_FIRMADOR", "http://localhost:8113/firmardocumento/");
define("MH_USER", str_replace('-', '', $Empresa['dni']));
define("MH_PWD", "Oscarfuentes01*");
define("MH_GRANT_TYPE", "password");
define("TOKEN_CACHE_FILE", __DIR__ . '/../storage/token_cache.json');
define("TOKEN_CACHE_MINUTES", 50);
define("MH_ENVIO_DTE_URL", "https://apitest.dtes.mh.gob.sv/fesv/recepciondte");
define("MH_PWD_DTE", "Oscar01*");