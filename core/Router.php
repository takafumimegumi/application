<?php

class Router {

    protected $routes;

    public function __construct($definitions) {
        $this->routes = $this->compileRoutes($definitions);
    }

    public function compileRoutes($definitions) {
        $routes = [];

        foreach ($definitions as $url => $params) {
            // （例）'/item/:action' → [item, :action]
            $tokens = explode('/', ltrim($url, '/'));
            foreach ($tokens as $i => $token) {
                // 動的パラメータである場合（例）:action
                if (0 === strpos($token, ':')) {
                    // ':'を除いた文字列を変数$nameに代入
                    $name = substr($token, 1);
                    // 正規表現の形式に変換（名前付きキャプチャ）
                    $token = '(?P<' . $name . '>[^/]+)';
                }
                // （例）'/item/:action' → [item, (?P<action>[^/]+)]
                $tokens[$i] = $token;
            }
            // 正規表現のパターンを生成
            $pattern = '/' . implode('/', $tokens);
            // URLを正規表現の形式に変換して最初に定義した$routes変数に代入
            $routes[$pattern] = $param;
        }

        // コンストラクタによって$routesパラメータにセットされる
        return $routes;
    }

    public function resolve($path_info) {
        // 先頭に`/`(スラッシュ)がなかった場合、'/'を付ける
        if ('/' !== substr($path_info, 0, 1)) {
            $path_info = '.' . $path_info;
        }

        foreach ($this->routes as $pattern => $params) {
            // $routesプロパティに格納されたルーティング定義配列を利用して正規表現のパターンを完成させ、preg_match関数でマッチングを行う
            // （パターン例）#^/item/(?P<action>[^/]+)$#
            if (preg_match('#^' . $pattern . '$#', $path_info, $matches)) {
                // （例）['controller' => 'item'] → ['controller' => 'item', <マッチした文字列> ,'action' => <括弧で囲まれた値>, <括弧で囲まれた値>]
                $params = array_merge($params, $matches);

                return $params;
            }
        }

        return false;
    }

}