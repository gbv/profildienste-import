<?php

namespace Util;

use MongoDB\Client;

class Database {

    private static $instance;
    
    private $titles;
    private $users;
    private $data;

    private function __construct(){
        $config = Config::getInstance();

        $client = new Client('mongodb://'.$config->getValue('database', 'host').':'.$config->getValue('database', 'port'));
        $db = $client->selectDatabase('pd');

        var_dump($client->listDatabases());

        $this->titles = $db->selectCollection('titles');
        $this->users = $db->selectCollection('users');
        $this->data = $db->selectCollection('data');
    }

    public static function getInstance(){
        if(is_null(Database::$instance)){
            Database::$instance = new Database();
        }

        return Database::$instance;
    }

    public function insertTitle($title){
        $this->titles->insertOne($title, Config::getInstance()->getValue('database', 'options'));
    }

    public function updateTitle($title){

    }

    public function updateCover($title, $cover){
        $this->titles->updateOne(
            ['_id' => $title['_id']],
            array('$set' => array('XX02' => $cover)),
            Config::getInstance()->getValue('database', 'options')
        );
    }

    public function insertUser($user){
        $this->users->insertOne($user, Config::getInstance()->getValue('database', 'options'));
    }

    public function updateUser($id, $upd){
        $this->users->updateOne(array('_id' => $id), $upd , Config::getInstance()->getValue('database', 'options'));
    }

    public function getGlobalPrice(){
        return $this->data->findOne(array('_id' => 'gprice'));
    }

    public function getGlobalCount(){
        return $this->data->findOne(array('_id' => 'gcount'));
    }

    public function insertData($d){
        $this->data->insertOne($d, Config::getInstance()->getValue('database', 'options'));
    }

    public function updateData($value, $data){
        $this->data->updateOne(
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