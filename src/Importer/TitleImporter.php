<?php
namespace Importer;

use MongoDB\BSON\UTCDateTime;
use Pimple\Container;
use Util\Mailer;

/**
 * Class TitleImporter
 *
 * Imports titles from JSON files in the titles_import directory
 */
class TitleImporter extends Importer {

    /**
     * @var int number of imported titles
     */
    private $total = 0;

    /**
     * @var int failed imports
     */
    private $fails = 0;

    /**
     * @var Mailer Mailer instance
     */
    private $mailer;

    public function __construct(Container $container) {
        parent::__construct($container);
        $this->mailer = $container['mailer'];
    }

    public function run() {

        $prices = 0;
        $count = 0;

        $handle = opendir($this->config->getValue('dirs', 'title_import'));

        if ($handle === false) {
            $this->log->addError('Opening directory ' . $this->config->getValue('dirs', 'title_import') . 'failed!');
            return;
        }

        // read all JSON files from the import dir
        while (($file = readdir($handle)) !== false) {

            $f = $this->config->getValue('dirs', 'title_import', true) . $file;
            if ($file !== '.' && $file !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'json') {

                $this->total++;

                $d = json_decode(file_get_contents($f), true);
                if (is_null($d)) {
                    $this->log->addWarning($f . ' is not a valid JSON file');
                    rename($f, $this->config->getTitlesFailDir() . $file);
                    $this->fails++;
                    continue;
                }

                $ppn = isset($d['003@']['0']) ? $d['003@']['0'] : NULL;
                if (is_null($ppn)) {
                    $this->log->addWarning($f . ' has no ID (Field 003@/0)!');
                    rename($f, $this->config->getTitlesFailDir() . $file);
                    $this->fails++;
                    continue;
                }

                if (isset($d['004A']['f'])) {
                    preg_match_all('/EUR (\d+.{0,1}\d{0,2})/', $d['004A']['f'], $m);

                    if (count($m) == 2 && count($m[1]) == 1) {
                        $prices += floatval($m[1][0]);
                        $count++;
                    }
                }

                $d['XX02'] = NULL;

                // create a title database entry for each user
                $err = false;
                foreach ($d['XX01'] as $user) {

                    $d['_id'] = $ppn . '_' . $user;

                    //enrich title data
                    $d['user'] = $user;
                    $d['status'] = 'normal';
                    $d['comment'] = NULL;
                    $d['ssgnr'] = NULL;
                    $d['selcode'] = NULL;
                    $d['budget'] = NULL;
                    $d['supplier'] = NULL;
                    $d['watchlist'] = NULL;
                    $d['lastStatusChange'] = new UTCDateTime((time() * 1000));

                    try {
                        $this->databaseService->insertTitle($d);
                        $this->log->addInfo($f . ' ok.');
                        $this->mailer->addTitle($user);
                    } catch (Exception $e) {
                        $this->log->addError($e->getMessage());
                        $err = true;
                        $this->fails++;
                    }
                }

                if ($err) {
                    rename($f, $this->config->getTitlesFailDir() . $file);
                } else {
                    rename($f, $this->config->getTitlesDir() . $file);
                }
            }
        }
        closedir($handle);

        // update the overall mean used for estimating unknown prices
        $count = ($this->total - $this->fails);
        if ($count > 0) {

            $cursor_price = $this->databaseService->getGlobalPrice();
            $cursor_count = $this->databaseService->getGlobalCount();

            if (!is_null($cursor_price) && !is_null($cursor_count)) {

                $opr = $cursor_price['value'];
                $ocnt = $cursor_count['value'];

                $mean = round((($opr + $prices) / ($ocnt + $count)), 2);

                try {
                    $this->databaseService->updateData('gprice', ($opr + $prices));
                    $this->databaseService->updateData('gcount', ($ocnt + $count));
                    $this->databaseService->updateData('mean', $mean);
                } catch (\Exception $e) {
                    $this->log->addError('Error: ' . $e->getMessage());
                }

            } else {

                $mean = round(($prices / $count), 2);

                try {
                    $this->databaseService->insertData(['_id' => 'mean', 'value' => $mean]);
                    $this->databaseService->insertData(['_id' => 'gprice', 'value' => $prices]);
                    $this->databaseService->insertData(['_id' => 'gcount', 'value' => $count]);
                } catch (Exception $e) {
                    $this->log->addError('Error: ' . $e->getMessage());
                }

            }

        }
    }

    /**
     * @return int total number of imported titles
     */
    public function getTotal() {
        return $this->total;
    }

    /**
     * @return int number of failed imports
     */
    public function getFails() {
        return $this->fails;
    }
}