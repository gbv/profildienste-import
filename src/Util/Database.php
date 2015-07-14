<?php

namespace Util;

class Database {

    private static $instance;

    private $db;

    private $titles;
    private $users;
    private $data;

    private function __construct(){
        $config = Config::getInstance();
        $m = new \MongoClient('mongodb://'.$config->getValue('database', 'host').':'.$config->getValue('database', 'port'));
        $this -> db = $m->selectDB('pd');

        $this->titles = new \MongoCollection($this -> db, 'titles');
        $this->users = new \MongoCollection($this -> db, 'users');
        $this->data = new \MongoCollection($this -> db, 'data');
    }

    public static function getInstance(){
        if(is_null(Database::$instance)){
            Database::$instance = new Database();
        }

        return Database::$instance;
    }

    public function insertTitle($title){
        $this->titles->insert($title, Config::getInstance()->getValue('database', 'options'));
    }

    public function updateTitle($title){

    }

    public function updateCover($title, $cover){
        $this->titles->update(
            array('_id' => $title['_id']),
            array('$set' => array('XX02' => $cover)),
            Config::getInstance()->getValue('database', 'options')
        );
    }

    public function insertUser($user){
        $this->users->insert($user, Config::getInstance()->getValue('database', 'options'));
    }

    public function updateUser($id, $upd){
        $this->users->update(array('_id' => $id), $upd , Config::getInstance()->getValue('database', 'options'));
    }

    public function getGlobalPrice(){
        return $this->data->findOne(array('_id' => 'gprice'));
    }

    public function getGlobalCount(){
        return $this->data->findOne(array('_id' => 'gcount'));
    }

    public function insertData($d){
        $this->data->insert($d, Config::getInstance()->getValue('database', 'options'));
    }

    public function updateData($value, $data){
        $this->data->update(
            array('_id' => $value),
            array('$set' => array('value' => $data)) ,
            Config::getInstance()->getValue('database', 'options')
        );
    }

    public function findTitlesWithNoCover(){
        return $this -> titles -> find(array('XX02' => NULL));
    }

    public function userExists($id){
        $c = $this->users->findOne(array('_id' => $id));
        return !is_null($c);
    }

}