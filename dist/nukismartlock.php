<?php
/*******************************************************************************
* Nikya eedomus Script Nuki Smartlock
********************************************************************************
* Plugin version : 1.0
* Author : Nikya
* Origine : https://github.com/Nikya/eedomusScript_nuki_smartlock
* Nuki Bridge HTTP-API : 1.6
*******************************************************************************/

/** Utile en cours de dev uniquement */
//$eedomusScriptsEmulatorDatasetPath = "eedomusScriptsEmulator_dataset.json";
//require_once ("eedomusScriptsEmulator.php");

/** Initialisation de la réponse */
$response = null;

/** Lecture de l'functions */
$function = getArg('function');

/*******************************************************************************
* Routeur d'function
*/
switch($function) {
	case 'setup':
		sdk_setup(getArg('nukihost'), getArg('nukiport'), getArg('token'));
		break;
	case 'register':
		sdk_register(getArg('eedomushost'), getArg('nukiid'), getArg('periph_id_state'), getArg('periph_id_batterycritical'));
		break;
	case 'list':
		sdk_callAPI('list');
		break;
	case 'callback_list':
		sdk_callAPI('callback/list');
		break;
	case 'callback_remove':
		sdk_callAPI('callback/remove', array('id'=> getArg('id')));
		break;
	case 'incomingcall':
		sdk_incomingCall();
		break;
	default:
		$response = '{ "success" : "false", "message" : "Unknown function '.$function.' " }';
}

/*******************************************************************************
* Enregister les informations pour communiquer avec le Bridge Nuki
*/
function sdk_setup($nukihost, $nukiport, $token) {
	saveVariable('host', $nukihost);
	saveVariable('port', $nukiport);
	saveVariable('token', $token);

	sdk_callAPI('list');
}

/*******************************************************************************
* Enregister les informations
* - Côté eedomus : Les id des 2 périphériques d'informations
* - Côté Nuki : Enregistre ce script en tant que callBack
*/
function sdk_register($eedomushost, $nukiid, $periph_id_state, $periph_id_batterycritical) {
	global $response;

	$eScript = explode( '/' , __FILE__);
	$scriptName = $eScript[count($eScript)-1];

	$callbackUrl = "http://$eedomushost/script";
	$callbackUrlQuery = array(
		'exec' => $scriptName,
		'function' => 'incomingcall'
	);
	$fullUrl = "$callbackUrl?".http_build_query($callbackUrlQuery);

	saveVariable('nukiid', $nukiid);
	saveVariable("periph_id_state$nukiid", $periph_id_state);
	saveVariable("periph_id_batterycritical$nukiid", $periph_id_batterycritical);

	sdk_callAPI('callback/add', array('url' => $fullUrl));
}

/*******************************************************************************
* Fonction appelée par un callback de la part de Nuki.
* Est rappeler à chaque changement d'état.
*/
function sdk_incomingCall() {
	global $response;

	// Le callback est accompagné d'un Json contenant les nouvelles valeurs
	// FIXME : Impossible à lire sur eedomus !
	// $dataRaw = file_get_contents('php://input');
	// $nukiid = $dataRaw['nukiid'];
	// $periph_value_state = $dataRaw['state'];
	// $periph_value_batterycritical = $dataRaw['batteryCritical'];

	// FIXME : workaround
	$nukiid = loadVariable('nukiid');
	$periph_value_state = -1;
	$periph_value_batterycritical = -1;
	$listingStr = sdk_callAPI('list');
	$listing = sdk_json_decode($listingStr);

	foreach ($listing as $listed) {
		if ($listed['nukiId']==$nukiid) {
			$periph_value_state = $listed['lastKnownState']['state'];
			$periph_value_batterycritical = $listed['lastKnownState']['batteryCritical']===false ? 0 : 100;
		}
	}
	// end workaround

	$periph_id_state = loadVariable("periph_id_state$nukiid");
	$periph_id_batterycritical = loadVariable("periph_id_batterycritical$nukiid");

	setValue($periph_id_state, $periph_value_state);
	setValue($periph_id_batterycritical, $periph_value_batterycritical);

	$response = ' { ';
	$response.= ' "nukiid" : "'. $nukiid .'", ';
	$response.= ' "periph_id_state" : "'. $periph_id_state .'", ';
	$response.= ' "periph_id_batterycritical" : "'. $periph_id_batterycritical .'", ';
	$response.= ' "periph_value_state" : "'. $periph_value_state .'", ';
	$response.= ' "periph_value_batterycritical" : "'. $periph_value_batterycritical .'" ';
	$response.= ' } ';
}

/*******************************************************************************
* Appeler l'API de Nuki
*
* @param $endpoint Endpoint ciblé
* @param $params Tableau de paramétre à envoyer sur la cible
*
* @return le résulat de l'appel au format Json
*/
function sdk_callAPI($endpoint, $params=array()) {
	global $response;

	$host = loadVariable('host');
	$port = loadVariable('port');
	$token = loadVariable('token');

	if(empty($host) or empty($port) or empty($token)) {
		$response = '{ "success" : "false", "message" : "Need an execution of function:setup before !" }';
		return;
	}

	$params['token'] =$token;
	$url = "http://$host:$port/$endpoint?".http_build_query($params);

	$response = httpQuery($url);

	return $response;
}

/*******************************************************************************
* Fin du script, affichage du résultat au format XML
*/
sdk_header('text/xml');
echo jsonToXML($response);
