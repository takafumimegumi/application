<?php

abstract class Application {

    protected $debug = false;
    protected $request;
    protected $response;
    protected $session;
    protected $db_manager;
    protected $login_action = [];

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
        try {
            //（例）$params = ['controller' => 'account', 'action' => 'signup']
            $params = $this->router->resolve($this->request->getPathInfo());
            if ($params === false) {
                throw new HttpNotFoundException('No route found for ' . $this->request->getPathInfo());
            }

            //（例）account
            $controller = $params['controller'];
            //（例）signup
            $action = $params['action'];

            //（例）Application::runAction('account', 'signup', ['controller' => 'account', 'action' => 'signup']);
            $this->runAction($controller, $action, $params);
        } catch (HttpNotFoundException $e) {
            $this->render404Page($e);
        } catch (UnauthorizedActionException $e) {
            // ログイン画面のコントローラとアクションは個別のアプリケーションで異なるため、必要に応じて$login_actionプロパティで再定義する
            list($controller, $action) = $this->login_action;
            $this->runAction($controller, $action);
        }

        $this->response->send();
    }

    public function runAction($controller_name, $action, $params = []) {
        // 第一引数の文字列の最初の文字を大文字にし、連結する（例）AccountController
        $controller_class = ucfirst($controller_name) . 'Controller';

        // コントローラが特定できたらインスタンス化して返す
        $controller = $this->findController($controller_class);
        // コントローラが見つからなかった場合
        if ($controller === false) {
            throw new HttpNotFoundException($controller_class . ' controller is not found.');
        }

        //（例）AccountController->run('signup', ['controller' => 'account', 'action' => 'signup']);
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

        //（例）new AccountController(Applicationクラス自身) ← これによりControllerクラス内でApplicationクラスの各メソッドを扱える
        return new $controller_class($this);
    }

    protected function render404Page($e) {
        $this->response->setStatusCode(404, 'Not Found');
        $message = $this->isDebugMode() ? $e->getMessage() : 'Page not found.';
        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $this->response->setContent(<<<EOF
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
"http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
<head>
        <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
        <title>404</title>
</head>
<body>
        {$message}
</body>
</html>
EOF
        );
    }

}