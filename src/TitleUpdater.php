<?php

/**
 * TODO: Auf Format einigen, neu schreiben
 */
class TitleUpdater implements Importer{

    public function run(){

        $log = Log::getInstance()->getLog();
        $db = Database::getInstance();

        $handle = opendir(Config::getInstance()->getValue('dirs', 'title_update'));
        if ($handle === false) {
            $log->addError('Opening directory ' . Config::getInstance()->getValue('dirs', 'title_update') . 'failed!');
            return;
        }

        while (($file = readdir($handle)) !== false) {

            $f = Config::getInstance()->getValue('dirs', 'title_update', true) . $file;
            if ($file !== '.' && $file !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'json') {

                $d = json_decode(file_get_contents($f), true);

                $user = $d['best_id'];
                $titel = $d['titel'];

                foreach($titel as $tit){

                    $id = $tit['titel']['_id'];
                    //$status = $tit['comment'];
                    $status = 'Test - Akzeptiert';

                    try{
                        $this -> handleTitle($id, $user, $status);
                    }catch(\Exception $e){
                        $this->out($id.': '.$e -> getMessage());
                        continue;
                    }
                }

            }
        }
    }

    private function handleTitle($id, $user, $status){

        $pending = $this -> users -> findOne(array('_id' => $user), array('pending' => 1));
        $done = $this -> users -> findOne(array('_id' => $user), array('done' => 1));

        if(is_null($pending) || is_null($done)){
            throw new \Exception('Der Nutzer konnte nicht gefunden werden!');
        }

        $pending = $pending['pending'];
        $done = $done['done'];

        $pos = 0;
        $e = NULL;
        foreach($pending as $p){
            if($p['id'] == $id){
                $e = $p;
                break;
            }
            $pos++;
        }

        if(is_null($e)){
            throw new \Exception('Kein Titel unter dieser ID gefunden!');
        }

        $e['comment'] = $status;

        $f = array_slice($pending, 0, $pos);
        $s = array_slice($pending, $pos+1);

        $pending = array_merge($f, $s);
        $done = array_merge($done, array($e));

        try{
            $this -> users ->update(array('_id' => $user), array('$set' => array('pending' => $pending, 'done' => $done)) , $this -> opt);
        }catch(\Exception $e){
            throw new \Exception('Aktualisieren nicht erfolgreich!');
        }

    }
}
