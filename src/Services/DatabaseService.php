<?php

namespace Services;

use Config\Config;
use Exception;
use MongoDB\Client;
use MongoDB\Driver\Exception\ConnectionTimeoutException;

class DatabaseService {

    private $config;

    private $client;

    private $titles;
    private $users;
    private $data;

    public function __construct(Config $config) {

        $this->config = $config;

        $this->client = new Client('mongodb://' . $config->getValue('database', 'host') . ':' . $config->getValue('database', 'port'));
        $db = $this->client->selectDatabase($config->getValue('database', 'name'));

        $this->titles = $db->selectCollection('titles');
        $this->users = $db->selectCollection('users');
        $this->data = $db->selectCollection('data');
    }

    public function checkConnectivity() {
        try {
            $this->client->listDatabases();
        } catch (ConnectionTimeoutException $e) {
            throw new Exception('Failed to connect to the database: ' . $e->getMessage());
        }
        return true;
    }

    public function insertTitle($title) {
        $this->titles->insertOne($title);
    }

    public function updateTitle($title) {

    }

    public function updateCover($title, $cover) {
        $this->titles->updateOne(['_id' => $title['_id']],
            ['$set' => ['XX02' => $cover]]
        );
    }

    public function insertUser($user) {
        $this->users->insertOne($user);
    }

    public function updateUser($id, $upd) {
        $this->users->updateOne(['_id' => $id], $upd);
    }

    public function getGlobalPrice() {
        return $this->data->findOne(['_id' => 'gprice']);
    }

    public function getGlobalCount() {
        return $this->data->findOne(['_id' => 'gcount']);
    }

    public function insertData($d) {
        $this->data->insertOne($d);
    }

    public function updateData($value, $data) {
        $this->data->updateOne(
            ['_id' => $value],
            ['$set' => ['value' => $data]]
        );
    }

    public function findTitlesWithNoCover() {
        return $this->titles->find(['XX02' => null]);
    }

    public function userExists($id) {
        $c = $this->users->findOne(['_id' => $id]);
        return !is_null($c);
    }

}