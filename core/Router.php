<?php

class Router {

    protected $routes;

    public function __construct($definitions) {
        $this->routes = $this->compileRoutes($definitions);
    }

    // 受け取ったルーティング定義中の動的パラメータ指定を正規表現で扱える形式に変換するメソッド
    public function compileRoutes($definitions) {
        $routes = [];

        foreach ($definitions as $url => $params) {
            // ltrim関数で最初の'/'(スラッシュ)を取り除き、exlode関数で'/'(スラッシュ)を元に配列を生成
            // （例1）'/account/:action' → [account, :action]
            // （例2）'/user/:user_name' → [user, :user_name]
            $tokens = explode('/', ltrim($url, '/'));
            foreach ($tokens as $i => $token) {
                // 動的パラメータである場合（例1）:action
                if (0 === strpos($token, ':')) {
                    // ':'を除いた文字列を変数$nameに代入
                    $name = substr($token, 1);
                    // 正規表現で(名前付き)キャプチャを利用できる形式に変換
                    $token = '(?P<' . $name . '>[^/]+)';
                }
                // （例1）$tokens = [account, (?P<action>[^/]+)]
                // （例2）$tokens = [user, (?P<user_name>[^/]+)]
                $tokens[$i] = $token;
            }
            // 正規表現のパターンを生成
            // （例1）$pattern = /account/(?P<action>[^/]+)
            // （例2）$pattern = /user/(?P<user_name>[^/]+)
            $pattern = '/' . implode('/', $tokens);
            // URLを正規表現の形式に変換して最初に定義した$routes変数に代入
            $routes[$pattern] = $params;
            // $routes = [
            //     '/account/(?P<action>[^/]+)'
            //         => ['controller' => 'account'],   ← 例1
            //     '/user/(?P<user_name>[^/]+)'
            //         => ['controller' => 'status', 'action' => 'user'],   ← 例2
            // ];
        }

        // コンストラクタによって$routesパラメータにセットされる
        return $routes;
    }

    // 変換済みのルーティング定義配列とPATH_INFOのマッチングを行いルーティングパラメータを特定するメソッド
    // （例1）Router::resolve('/signup');
    // （例2）Router::resolve('/taka');
    public function resolve($path_info) {
        // 先頭に`/`(スラッシュ)がなかった場合、'/'を付ける
        if ('/' !== substr($path_info, 0, 1)) {
            $path_info = '/' . $path_info;
        }

        foreach ($this->routes as $pattern => $params) {
            // $routesプロパティに格納されたルーティング定義配列を利用して正規表現のパターンを完成させ、preg_match関数でマッチングを行う
            // （パターン例1）#^/account/(?P<action>[^/]+)$#
            // （パターン例2）#^/user/(?P<user_name>[^/]+)$#
            if (preg_match('#^' . $pattern . '$#', $path_info, $matches)) {
                // （例1）['controller' => 'account'] → ['controller' => 'account', '/account/signup' ,'action' => 'signup', 'signup']
                // （例2）['controller' => 'status', 'action' => 'user'] → ['controller' => 'status', 'action' => 'user', '/user/taka', 'user_name' => 'taka', 'taka']
                $params = array_merge($params, $matches);

                return $params;
            }
        }

        return false;
    }

}