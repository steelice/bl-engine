<?php

require_once 'classes/iModulesFactory.class.php';

class rModulesFactory extends iModulesFactory{

	protected $order;


	public function __construct(rApplication $app, array $order){
		parent::__construct($app);
		$this->order = $order;
	}

	public function getModule(){

		/** проверяем, есть ли в системе роутер **/
		if($router = $this->checkRouter()){
			$routerResult = $router->checkRules();

			if(!$routerResult) throw new Exception('Access Denied');

			if(is_object($routerResult)) return $routerResult;
		}

		/** ежели у нас индекс - открываем индекс **/
		if(!$this->rURL->path(1)){
			return $this->getIndexModule();
		}

		// пробуем найти метод, создающий модуль
		foreach ($this->order as $method) {
			$method = 'get_'.$method;
			// все не сущестующие методы не вызываем
			
			if(!method_exists($this, $method)) continue; 

			if($module = $this->$method()) return $module;
		}


		return $this->get404Module();

	}

	/** 
		берем модуль из каталога 
	**/
	protected function get_module_at_dir($dir){
		$moduleName = $this->rURL->path(1);

		
		if($moduleName == 'index') return false; // пока лучше не придумал

		$fn = false;

		if(is_dir($dir.'/'.$moduleName)){
			if(($subModuleName = $this->rURL->path(2)) && file_exists($dir.'/'.$moduleName.'/'.$subModuleName.'.php')){



				$fn = $dir.'/'.$moduleName.'/'.$subModuleName.'.php';
				$moduleName = $moduleName.'_'.preg_replace('~[^a-z_-]~i', '', $subModuleName);

				// echo $moduleName; exit;

			}elseif(file_exists($dir.'/'.$moduleName.'/index.php')){
				$fn = $dir.'/'.$moduleName.'/index.php';
			}elseif (file_exists($dir.'/'.$moduleName.'/'.$moduleName.'.php')) {
				$fn = $dir.'/'.$moduleName.'/'.$moduleName.'.php';
			}
			
		}else
			$fn = $dir.'/'.$moduleName.'.php';

		if(!file_exists($fn)) return false;

		include_once $fn;

		$moduleClass = 'module_'.$moduleName;
		$moduleClass = str_replace('-', '_', $moduleClass);

		if(!class_exists($moduleClass)) throw new Exception("Wrong module file (searching for $moduleClass)");
		

		return new $moduleClass($this->app);
	}

	/**
		берем модуль из сайта
	**/
	public function get_site(){
		return $this->get_module_at_dir(MODULES_PATH);
	}

	/** 
		берем модуль из движка
	**/
	public function get_engine(){
		return $this->get_module_at_dir(ENGINE_MODULES_PATH);
	}


	/**
		пытаемся подхватить tpl-модуль из static-папки
	**/
	public function get_tpl(){
		$filename = TEMPLATES_PATH.'/'.STATIC_TPL_FOLDER.'/'.$this->rURL->safePath().'.tpl';
		if(!file_exists($filename)){
			return false;
		}

		$this->app->url->redirect2RightURL('.html');
		
		$module = new rMyTPLModule($this->app);
		$module->setTemplate(STATIC_TPL_FOLDER.'/'.$this->rURL->safePath().'.tpl');

		return $module;
	}

	/**
		пытаемся найти страницу в таблице статических страниц
	**/
	public function get_page(){
		if($text = @$this->db->selectRow('SELECT * FROM static_pages WHERE url = ?', $this->rURL->safePath())){


			$this->app->url->redirect2RightURL();
			

			$module = new rMyModule($this->app);
			$module->assign('customText', $text);
			$module->setTemplate('staticPage.tpl');
			if($text['title']) $module->setTitle($text['title']);


			return $module;
		}else
			return false;
	}

	/**
		пытаемся найти блог 
	**/

	public function get_blog(){

		$blog = new rMyBlog($this->app);

		if(defined('SIMPLE_BLOG_MODE') && SIMPLE_BLOG_MODE){
			// блог всего один и посты открываются по адресу site.com/post-url.html
			if($post = $blog->getByURL($this->app->url->path(1))){
				$module = new rMySiteBlog($app, $blog);
				$module->assignPost($post);
				
				return $module;
			}
		}else{
			// блогов несколько, вначале ищем блог в таблице
			if($blogSection = $blog->selectBlog($this->app->url->path(1))){
				$module = new rMySiteBlog($this->app, $blog);

				$module->routeURL();
				return $module;
			}
		}

		return false; // ничего не нашли
	}

	/**
	 * Поиск по базе urls
	 * @return module [description]
	 */
	public function get_url()
	{
		if(!$url = \ble\rURL::getURL($_SERVER['REQUEST_URI']))
			return false;

		if(!$url->handler || !$url->handled_id) return false;

		$class = 'rMyURLHandler_'.$url->handler;
		if(file_exists(ENGINE_PATH.'/lib/handlers/'.$class.'.class.php'))
			require_once ENGINE_PATH.'/lib/handlers/'.$class.'.class.php';
		elseif(file_exists(SITE_PATH.'/lib/handlers/'.$class.'.class.php'))
			require_once SITE_PATH.'/lib/handlers/'.$class.'.class.php';

		if(class_exists($class))
		{
			$module = new $class($url);
			return $module;
		}

		return false;
	}


	/**
		берем индексный модуль 
	**/
	public function getIndexModule(){
		if(file_exists(MODULES_PATH.'/index.php')){
			include_once MODULES_PATH.'/index.php';
			return new module_index($this->app);
		}else{
			include_once ENGINE_MODULES_PATH.'/index.php';
			return new module_engine_index($this->app);
		}
	}

	/**
		берем индексный модуль 
	**/
	public function get404Module(){
		if(file_exists(MODULES_PATH.'/_errors/404.php')){
			include_once MODULES_PATH.'/_errors/404.php';
			return new module_index($this->app);
		}else{
			include_once ENGINE_MODULES_PATH.'/_errors/404.php';
			return new module_engine_404($this->app);
		}
	}


	/**
	* Пытается найти на сайте settings/router.php и создать Роутер.
	* Иначе возвращает false
	**/
	public function checkRouter()
	{
		if(file_exists(SITE_PATH.'/settings/router.php')){
			require SITE_PATH.'/settings/router.php';
			return new rMyRouter($this->app);
		}
		return false;
	}

}