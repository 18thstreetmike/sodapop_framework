<?php
/**
 * This is the class that is extended to make application controllers.
 *
 * @author michaelarace
 */
class Sodapop_Controller {

	protected $request;

	public $view;

	public $controller;

	public $action;
        
        public $mimeType = 'text/html';

	protected $viewPath = 'index/index';

	protected $layoutPath = 'layout';

	public function __construct($request, $view) {
		$this->request = $request;
		$this->view = $view;
	}

	public function setViewPath($viewPath) {
		$this->viewPath = $viewPath;
	}

	public function setLayoutPath($layoutPath) {
		$this->layoutPath = $layoutPath;
	}

	public function preDispatch() {

	}

	public function postDispatch() {

	}

	public function render () {
		$this->view->controller = $this->controller;
		$this->view->action = $this->action;
		$this->view->request = $this->request;
		$this->view->viewFile = '/'.$this->viewPath;
		$this->view->layoutFile = '/../layouts/'.$this->layoutPath;
		return $this->view->render($this->mimeType);
	}

	public function cleanup() {

	}

	public function forward($controller, $action) {
		Sodapop_Application::getInstance()->loadControllerAction($controller, $action, $this->request, $this->view, $this->view->baseUrl);
	}

	public function redirect($url) {
		header('Location: '.$url);
		exit;
	}

	protected function buildTabsFromPermissions($tabGroup) {
		$tabs = array();
		if (is_array($tabGroup)) {
			foreach ($tabGroup as $navigationTab) {
				if (isset($navigationTab['model'])) {
					if ($this->application->user->hasModelViewPermission($navigationTab['model'])) {
						$tab = new stdClass();
						$tab->id = $navigationTab['id'];
						$tab->label = $navigationTab['label'];
						$tab->url = $navigationTab['url'];
						$tabs[] = $tab;
					}
				} else if (isset($navigationTab['permission'])) {
					$permissions = explode(',', $navigationTab['permission']);
					foreach ($permissions as $permission) {
						if ($this->application->user->hasPermission($permission)) {
							$tab = new stdClass();
							$tab->id = $navigationTab['id'];
							$tab->label = $navigationTab['label'];
							$tab->url = $navigationTab['url'];
							$tabs[] = $tab;
						}
					}
				} else if (isset($navigationTab['application_permission'])) {
					$permissions = explode(',', $navigationTab['application_permission']);
					foreach ($permissions as $permission) {
						if ($this->application->user->hasApplicationPermission($permission)) {
							$tab = new stdClass();
							$tab->id = $navigationTab['id'];
							$tab->label = $navigationTab['label'];
							$tab->url = $navigationTab['url'];
							$tabs[] = $tab;
						}
					}
				} else if (isset($navigationTab['server_permission'])) {
					$permissions = explode(',', $navigationTab['server_permission']);
					foreach ($permissions as $permission) {
						if ($this->application->user->hasServerPermission($permission)) {
							$tab = new stdClass();
							$tab->id = $navigationTab['id'];
							$tab->label = $navigationTab['label'];
							$tab->url = $navigationTab['url'];
							$tabs[] = $tab;
						}
					}
				} else {
					$tab = new stdClass();
					$tab->id = $navigationTab['id'];
					$tab->label = $navigationTab['label'];
					$tab->url = $navigationTab['url'];
					$tabs[] = $tab;
				}
			}
		}
		return $tabs;
	}
}
