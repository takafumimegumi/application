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

    // ビューファイルの読み込み処理をラッピングしたメソッド
    protected function render($variables = [], $template = null, $layout = 'layout') {
        $defaults = [
            'request' => $this->request,
            'base_url' => $this->request->getBaseUrl(),
            'session' => $this->session,
        ];

        $view = new View($this->application->getViewDir(), $defaults);

        if (is_null($template)) {
            //（例）signup
            $template = $this->action_name;
        }

        //（例）account/signup
        $path = $this->controller_name . '/' . $template;

        //（例）$view->render('acount/signup', [], 'layout');
        return $view->render($path, $variables, $layout);
    }

    protected function forward404() {
        throw new HttpNotFoundException('Forwarded 404 page from ' . $this->controller_name . '/' . $this->action_name);
    }

    protected function redirect($url) {
        // 同じアプリケーション内で別アクションのリダイレクトを行う場合の処理（$urlにはPATH_INFO部分のみ指定）
        if (!preg_match('#https?://#', $url)) {
            $protocol = $this->request->isSsl() ? 'https://' : 'http://';
            $host = $this->request->getHost();
            $base_url = $this->request->getBaseUrl();

            // 絶対URLの組み立て
            $url = $protocol . $host . $base_url . $url;
        }

        $this->response->setStatuscode(302, 'Found');
        $this->response->setHttpHeader('Location', $url);
    }


    protected function generateCsrfToken($form_name) {
        $key = 'csrf_tokens/' . $form_name;
        // セッションを連想配列で取得（キーが$key）
        $tokens = $this->session->get($key, []);
        // トークンが10個以上ある場合は、古いものから削除
        if (count($tokens) >= 10) {
            array_shift($tokens);
        }

        $token = sha1($form_name . session_id() . microtime());
        $tokens[] = $token;

        $this->session->set($key, $tokens);

        return $token;
    }

    protected function checkCsrfToken($form_name, $token) {
        $key = 'csrf_tokens/' . $form_name;
        $tokens = $this->session->get($key, []);

        // 指定した値を配列で検索し見つかって、キーを返ってきた場合の処理
        if (false !== ($pos = array_search($token, $tokens, true))) {
            unset($token[$pos]);
            $this->session->set($key, $tokens);

            return true;
        }
    }

}