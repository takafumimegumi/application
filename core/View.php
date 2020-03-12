<?php

class View {

    protected $base_dir;
    protected $defaults;
    protected $layout_variables = [];

    public function __construct($base_dir, $defaults = []) {
        $this->base_dir = $base_dir;
        $this->defaults = $defaults;
    }

    // ページのタイトルはレイアウト側に出力されるが、実行するアクションによって変わってくる
    // レイアウトファイル側に値を設定するためのメソッド
    public function setLayoutVar($name, $value) {
        $this->layout_variables[$name] = $value;
    }

    // 実際にビューファイルの読み込みを行うメソッド
    // render(ビューファイルへのパス, ビューファイルに渡す変数を連想配列で指定, レイアウトファイル名)
    public function render($_path, $_variables = [], $_layout = false) {
        // （例）~/view/account/signup.php
        $_file = $this->base_dir . '/' . $_path . '.php';

        // 配列を変数に展開して、ビューファイルで利用しやすくする
        extract(array_merge($this->defaults, $_variables));

        ob_start();
        ob_implicit_flush(0);

        require $_file;

        $content = ob_get_clean();

        // $_layout変数にレイアウトファイル名が指定されている場合の処理
        // レイアウトファイル側のコンテンツは$_contentに格納され、適当な場所で$_contentを出力することで1つのHTMLとなり、再度$contentに格納される
        if ($_layout) {
            $content = $this->render(
                $_layout, 
                array_merge($this->layout_variables, ['_content' => $content,]
            ));
        }

        return $content;
    }

    public function escape($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

}