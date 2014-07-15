<?php

/** Инклудим конфиги **/
if(!include_once 'configs/web.php') die('Site not installed!');

/** Инклудим движок **/
include_once ENGINE_PATH.'/init.php';

function echoJSON($data, $status = 200, $error_msg = '')
{
	if(!headers_sent()) header('Content-Type: application/json');
	echo json_encode(array(
			'status' => $status,
			'error_msg' => $error_msg,
			'data' => $data
		));
	exit;
}

class JSONException extends Exception{};

$module = null;

try{


	$_APP = rMyApp::getInstance();
	$_APP->setForceAJAXHit();
	$_isJSONMode = $_APP->testPath('json', 1);


	$_MODULE_METHOD = 'RunAJAX';

	if(defined('ADMIN_FOLDER') && ADMIN_FOLDER && ($_APP->path(2) == ADMIN_FOLDER)){
		// администые аяксы. неавторизованным тут делать нехуй
		if(!$_APP->user->authed()) throw new rNotFound();

		

		require_once CONFIGS_PATH.'/admin.php';
		$_Autoexec = new rMyAdminAutoexec;
		$_Factory = new rMyAdminModulesFactory($_APP);

		$module = $_Factory->getAJAXModule();

		if($_APP->path(6)){
			if(method_exists($module, 'Run_'.$_APP->path(6))){
				$_MODULE_METHOD = 'Run_'.$_APP->path(6);
			}
		}elseif (method_exists($module, 'RunAJAX_'.$_APP->path(5))) {
			$_MODULE_METHOD = 'RunAJAX_'.$_APP->path(5);
		}




	}else{

		$_Autoexec = new rMyAutoexec;

		$_MODULE_NAME = $_APP->path(2);


		if($_APP->path(4) && file_exists(MODULES_PATH.'/'.$_APP->path(2).'/'.$_APP->path(3).'.php')){
			if(file_exists(MODULES_PATH.'/'.$_APP->path(2).'/'.$_APP->path(3).'.php')){
				require_once(MODULES_PATH.'/'.$_APP->path(2).'/'.$_APP->path(3).'.php');
				$_MODULE_NAME .= '_'.$_APP->path(3);

			}
		}elseif ($_APP->path(3) && file_exists(MODULES_PATH.'/'.$_APP->path(2).'/'.$_APP->path(3).'.php')) {
			require_once(MODULES_PATH.'/'.$_APP->path(2).'/'.$_APP->path(3).'.php');
			$_MODULE_NAME .= '_'.$_APP->path(3);
		}

		elseif(file_exists(MODULES_PATH.'/'.$_APP->path(2).'/ajax.php')){
			require_once(MODULES_PATH.'/'.$_APP->path(2).'/ajax.php');
			
		}elseif (file_exists(MODULES_PATH.'/'.$_APP->path(2).'.ajax.php')) {
			require_once(MODULES_PATH.'/'.$_APP->path(2).'.ajax.php');
		}elseif (file_exists(MODULES_PATH.'/'.$_APP->path(2).'.php')) {
			require_once(MODULES_PATH.'/'.$_APP->path(2).'.php');
		}elseif (file_exists(MODULES_PATH.'/'.$_APP->path(2).'/index.php')) {
			require_once(MODULES_PATH.'/'.$_APP->path(2).'/index.php');
		}


		elseif(file_exists(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'/ajax.php')){
			require_once(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'/ajax.php');
		}elseif (file_exists(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'.ajax.php')) {
			require_once(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'.ajax.php');
		}elseif (file_exists(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'.php')) {
			require_once(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'.php');
		}elseif (file_exists(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'/index.php')) {
			require_once(ENGINE_MODULES_PATH.'/'.$_APP->path(2).'/index.php');
		}

		else{
			throw new rNotFound();
			
		}

		$_MODULE_CLASS_NAME = "module_".$_MODULE_NAME;
		$module = new $_MODULE_CLASS_NAME($_APP);
		if($_APP->path(4) && method_exists($module, 'RunAJAX_'.$_APP->path(4))){
			if(method_exists($module, 'RunAJAX_'.$_APP->path(4))){
				$_MODULE_METHOD = 'RunAJAX_'.$_APP->path(4);
			}
		}elseif($_APP->path(3)){
			if(method_exists($module, 'RunAJAX_'.$_APP->path(3))){
				$_MODULE_METHOD = 'RunAJAX_'.$_APP->path(3);
			}elseif(method_exists($module, 'Run_'.$_APP->path(3))){
				$_MODULE_METHOD = 'Run_'.$_APP->path(3);
			}
		}
	}

	
	$_Autoexec->beforeAJAX($_APP, $module);
	
	
	$module->Init();
	
	if($_isJSONMode){
		// режим JSON
		echoJSON($module->$_MODULE_METHOD());
	}else{
		// режим ХУЙЗНАЙТСОН
		$result = $module->$_MODULE_METHOD();
		if($result === NULL){
			// рендер как обычный текст
			$_APP->assign('_MODULE', $module);
			
		}else{
			// даже в таком режиме пытаемся отдать джон
			echoJSON($result);
		}

	}

	$_Autoexec->afterAJAX($_APP, $module);

	
	

}catch(JSONException $e){
	echoJSON(false, 503, $e->getMessage());
}catch(dbException $e){
	$info = $e->getInfo();

	if($_isJSONMode){
		echoJSON(false, 503, $info['message']);
	}
	
	rSiteNotifier::outputError($info['message'], $info, TEMPLATES_PATH.'/errors/fatal.tpl', 'E_DB_ERROR', true);
	
}catch(rNotFound $e){
	//if(!$module) $module = new rMySite($_APP);

	if($_isJSONMode){
		echoJSON(false, 404, $e->getMessage());
	}

	$_APP->notFound();

	
}catch(SmartyException $e){
	rSiteNotifier::outputError($e->getMessage(), '', TEMPLATES_PATH.'/errors/notice.tpl', 'E_TPL_ERROR');
	//exit;
}catch(Exception $e){

	if($_isJSONMode)
		echoJSON(false, 503, $e->getMessage());

	if($module)
		$module->renderError($e->getMessage());
	else
		rSiteNotifier::outputError('', $e->getMessage(), TEMPLATES_PATH.'/errors/fatal.tpl', 'E_ENGINE_ERROR', true);
}