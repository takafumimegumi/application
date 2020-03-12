<?php

class DbManager {

    protected $connections = [];
    protected $repository_connection_map = [];
    protected $repositories = [];

    // 接続を行うメソッド（$nameは接続を特定するための名前：masterなど）
    public function connect($name, $params) {

        // 各キーが存在するかのチェックを不要にするためにarray_merge関数を利用
        $params = array_merge([
            'dsn' => null,
            'user' => '',
            'password' => '',
            'options' => [],
        ], $params);

        $con = new PDO(
            $params['dns'],
            $params['user'],
            $params['password'],
            $params['options']
        );

        $con->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->connections[$name] = $con;
    }

    public function getConnection($name = null) {
        if (is_null($name)) {
            // 指定がなければ配列の先頭の要素を返す
            return current($this->connections);
        }
        
        return $this->connections[$name];
    }

    // 接続先を管理するためのメソッド（1つのデータベースのみで十分なケースはあまり意味がない）
    public function setRepositoryConnectionMap($repository_name, $name) {
        $this->repository_connection_map[$repository_name] = $name;
    }

    // 接続先を管理するためのメソッド（1つのデータベースのみで十分なケースはあまり意味がない）
    public function getConnectionForRepository($repository_name) {
        if (isset($this->repository_connection_map[$repository_name])) {
            $name = $this->repository_connection_map[$repository_name];
            $con = $this->getConnection($name);
        } else {
            $con->getConnection();
        }

        return $con;
    }

    // Repositoryクラスの管理を行うメソッド
    public function get($repository_name) {
        if (!isset($this->repositories[$repository_name])) {
            //（例）get('User') → $repository_class = 'UserRepository'
            $repository_class = $repository_name. 'Repository';
            //（例）get('User') → $con = getConnectionForRepository('User');
            // repository_connection_map['User']の値（$name）が$conに代入される
            // 値（$name）が設定されていない場合は最初に作成した接続先（mysqlなどの接続情報が入力済みのPDOインスタンス）を取得する
            $con = $this->getConnectionForRepository($repository_name);

            // new UserRepository(PDOクラスのインスタンス)
            $repository = new $repository_class($con);

            // repositories = [
            //     'User' => UserRepository(PDOクラスのインスタンス)のインスタンス
            // ];
            $this->repositories[$repository_name] = $repository;
        }

        return $this->repositories[$repository_name];
    }

    public function __destruct() {
        foreach ($this->repositories as $repository) {
            unset($repository);
        }

        foreach ($this->connections as $con) {
            unset($con);
        }
    }

}