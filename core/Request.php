<?php

class Request {

	public function isPost() {
		if ($_SERVER['REQUEST_METHOD'] === 'POST') {
			return true;
		}
		return false;
	}

	public function getGet($name, $default = null) {
		if (isset($_GET[$name])) {
			return $_GET[$name];
		}
		return $default;
	}

	public function getPost($name, $default = null) {
		if(isset($_POST[$name])) {
			return $_POST[$name];
		}
		return $default;
	}

	public function getHost() {
		if (!empty($_SERVER['HTTP_HOST'])) {
			return $_SERVER['HTTP_HOST'];
		}
		return $_SERVER['SERVER_NAME'];
	}

	public function isSsl() {
		if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
			return true;
		}
		return false;
	}

	public function getRequestUri() {
		return $_SERVER['REQUEST_URI'];
	}

	public function getBaseUrl() {
		$script_name = $_SERVER['SCRIPT_NAME'];

		$request_uri = $this->getRequestUri();

		// index.php(フロントコントローラ)がURLに含まれている場合の処理
		if (0 === strpos($request_uri, $script_name)) {
			return $script_name;
		} elseif (0 === strpos($request_uri, dirname($script_name))) { 
			return rtrim(dirname($script_name), '/'); // 最後のスラッシュを削除
		}

		return '';
	}

	public function getPathInfo() {
		$base_url = $this->getBaseUrl();
		$request_uri = $this->getRequestUri();

		// GETパラメータが含まれている場合の処理（$posに0以外が代入されたとき）
		if (false !== ($pos = strpos($request_uri, '?'))) {
			$request_uri = substr($request_uri, 0, $pos); // GETパラメータ(?~の部分)を取り除く
		}

		$path_info = (string)substr($request_uri, strlen($base_url));

		return $path_info;
	}

}
