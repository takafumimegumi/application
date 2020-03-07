<?php

abstract class Controller {

    protected $controller_name;
    protected $action_name;
    protected $application;
    protected $request;
    protected $response;
    protected $session;
    protected $db_manager;

    public function __construct($application) {
        //（例）'AccountContoroller' → 'account'
        $this->controller_name = strtolower(substr(get_class($this), 0, -10));

        $this->application = $application;
        $this->request = $application->getRequest();
        $this->response = $application->getResponse();
        $this->session = $application->getSession();
        $this->db_manager = $application->getDbManager();
    }

    public function run($action, $params = []) {
        //（例）signup
        $this->action_name = $action;

        //（例）signupAction
        $action_method = $action . 'Action';
        if (!method_exists($this, $action_method)) {
            $this->forward404();
        }

        // 可変関数の仕組みを使ってアクションを特定し、存在すれば実行
        //（例）AccountController::signupAction
        $content = $this->$action_method($params);

        return $content;
    }

}