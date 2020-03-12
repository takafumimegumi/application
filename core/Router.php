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
            // （例）'/account/:action' → [account, :action]
            $tokens = explode('/', ltrim($url, '/'));
            foreach ($tokens as $i => $token) {
                // 動的パラメータである場合（例）:action
                if (0 === strpos($token, ':')) {
                    // ':'を除いた文字列を変数$nameに代入
                    $name = substr($token, 1);
                    // 正規表現で(名前付き)キャプチャを利用できる形式に変換
                    $token = '(?P<' . $name . '>[^/]+)';
                }
                // （例）$tokens = [account, (?P<action>[^/]+)]
                $tokens[$i] = $token;
            }
            // 正規表現のパターンを生成
            // （例）$pattern = /account/(?P<action>[^/]+)
            $pattern = '/' . implode('/', $tokens);
            // URLを正規表現の形式に変換して最初に定義した$routes変数に代入
            $routes[$pattern] = $params;
            // $routes = [
            //     '/account/(?P<action>[^/]+)' => ['controller' => 'account']
            // ];
        }

        // コンストラクタによって$routesパラメータにセットされる
        return $routes;
    }

    // 変換済みのルーティング定義配列とPATH_INFOのマッチングを行いルーティングパラメータを特定するメソッド
    // （例）Router::resolve('/signup');
    public function resolve($path_info) {
        // 先頭に`/`(スラッシュ)がなかった場合、'/'を付ける
        if ('/' !== substr($path_info, 0, 1)) {
            $path_info = '/' . $path_info;
        }

        foreach ($this->routes as $pattern => $params) {
            // $routesプロパティに格納されたルーティング定義配列を利用して正規表現のパターンを完成させ、preg_match関数でマッチングを行う
            // （パターン例）#^/account/(?P<action>[^/]+)$#
            if (preg_match('#^' . $pattern . '$#', $path_info, $matches)) {
                // （例）['controller' => 'account'] → ['controller' => 'account', <マッチした文字列全体> ,'action' => <括弧で囲まれた値>, <括弧で囲まれた値>]
                $params = array_merge($params, $matches);

                return $params;
            }
        }

        return false;
    }

}