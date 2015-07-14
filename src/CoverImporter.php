<?php

use Util\Database;

class CoverImporter implements Importer{

    private $checked = 0;
    private $withoutCover = 0;

    public function run(){

        $db = Database::getInstance();

        $cursor = $db->findTitlesWithNoCover();

        while($cursor -> count()) {

            $cursor = $cursor->limit(1000);

            foreach ($cursor as $d) {

                $this->checked++;

                $cs = new \Cover\Amazon();
                $covers = $cs ->getCovers($d);

                if(!$covers){
                    $this->withoutCover++;
                }


                $db->updateCover($d, $covers);

                usleep(1000000); // One second

            }

            $cursor = $db->findTitlesWithNoCover();
        }
    }

    public function getChecked(){
        return $this->checked;
    }

    public function getWithoutCover(){
        return $this->checked;
    }

    public function getWithCover(){
        return ($this->checked - $this->withoutCover);
    }
}