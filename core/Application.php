<?php

abstract class Application {

    protected $debug = false;
    protected $request;
    protected $response;
    protected $session;
    protected $db_manager;

    public function __construct($debug = false) {
        $this->setDebugMode($debug);
        $this->initialize();
        $this->configure();
    }

    protected function setDebugMode($debug) {
        if ($debug) {
            $this->debug = true;
            ini_set('display_errors', 1);
            error_reporting(-1);
        } else {
            $this->debug = false;
            ini_set('display_errors', 0);
        }
    }

    protected function initialize() {
        $this->request = new Request();
        $this->response = new Response();
        $this->session = new Session();
        $this->db_manager = new DbManager();
        $this->router = new Router($this->registerRoutes());
    }

    protected function configure() {}

    abstract public function getRootDir();

    abstract protected function registerRoutes();

    public function isDebugMode() {
        return $this->debug;
    }

    public function getRequest() {
        return $this->request;
    }

    public function getResponse() {
        return $this->response;
    }

    public function getSession() {
        return $this->session;
    }

    public function getDbManager() {
        return $this->db_manager;
    }

    public function getControllerDir() {
        return $this->getRootDir() . '/controllers';
    }

    public function getViewDir() {
        return $this->getRootDir() . '/views';
    }

    public function getModelDir() {
        return $this->getRootDir() . '/models';
    }

    public function getWebDir() {
        return $this->getRootDir() . '/web';
    }

    public function run() {
        //（例）$params = ['controller' => 'user', 'action' => 'edit']
        $params = $this->router->resolve($this->request->getPathInfo());
        if ($params === false) {
            // todo-A
        }

        //（例）user
        $controller = $params['controller'];
        //（例）edit
        $action = $params['action'];

        //（例）Application::runAction('user', 'edit', ['controller' => 'user', 'action' => 'edit']);
        $this->runAction($controller, $action, $params);

        $this->response->send();
    }

    public function runAction($controller_name, $action, $params = []) {
        // 第一引数の文字列の最初の文字を大文字にし、連結する（例）UserController
        $controller_class = ucfirst($controller_name) . 'Controller';

        // コントローラが特定できたらインスタンス化して返す
        $controller = $this->findController($controller_class);
        // コントローラが見つからなかった場合
        if ($controller === false) {
            // todo-B
        }

        //（例）UserController->run('edit', ['controller' => 'user', 'action' => 'edit']);
        // runメソッドを実行して帰ってきたコンテンツを取得
        $content = $controller->run($action, $params);
        // Response::setContentで、コントローラのrunメソッドによる返り値(コンテンツ)をメッセージボディーにセット
        $this->response->setContent($content);
    }

    protected function findController($controller_class) {
        // 引数に指定されたクラスが存在しない場合
        if (!class_exist($controller_class)) {
            // $controller_fileに引数に指定されたクラス名をファイル名に持つphpファイルを代入
            $controller_file = $this->getControllerDir() . '/' . $controller_class . '.php';
            // $controller_fileが読み込めない場合は処理を終了。読み込める場合は読み込む。
            if (!is_readable($controller_file)) {
                return false;
            } else {
                require_once $controller_file;

                if (!class_exists($controller_class)) {
                    return false;
                }
            }
        }

        //（例）new UserController(Applicationクラス自身)
        return new $controller_class($this);
    }

}