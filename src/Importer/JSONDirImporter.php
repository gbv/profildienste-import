<?php
namespace Importer;


use Pimple\Container;
use Util\Util;
use Util\ValidatorUtils;

/**
 * Class JSONDirImporter
 *
 * A helper class for importing all JSON files from a specific directory.
 *
 * @package Importer
 */
abstract class JSONDirImporter extends Importer {

    use ValidatorUtils;

    protected $dir;

    protected $total;

    protected $fails;

    protected $currentFile;

    public function __construct(Container $container) {
        parent::__construct($container);
    }

    /**
     * Starts the importing step
     *
     * @return void
     */
    public function run() {

        if (empty($this->dir)) {
            $this->log->addError('No import directory specified!');
            return;
        }

        $handle = opendir($this->dir);
        if ($handle === false) {
            $this->log->addError('Opening directory ' . $this->dir . 'failed!');
            return;
        }

        while (($file = readdir($handle)) !== false) {

            $f = Util::addTrailingSlash($this->dir) . $file;
            if ($file !== '.' && $file !== '..' && pathinfo($f, PATHINFO_EXTENSION) === 'json') {

                $this->currentFile = $file;

                $this->total++;
                $d = json_decode(file_get_contents($f), true);
                if (is_null($d)) {
                    $this->handleError($f, $f . ' is not a valid json file');
                    continue;
                }

                $this->handleData($d);
            }
        }

        closedir($handle);

        $this->afterHandling();
    }

    /**
     * Checks if the dataset is valid, i.e.
     * has a all required attributes with correct
     * values. Has to return true if the data is valid.
     *
     * @param $data The dataset
     * @return boolean Result of validation
     */
    public abstract function validate($data);

    /**
     * Handler for valid data
     *
     * @param $data
     * @return void
     */
    public abstract function handleData($data);

    /**
     * Error handler for invalid or unparseable files.
     *
     * @param $fileName
     * @param $reason
     * @param null $data
     * @return void
     */
    public abstract function handleError($fileName, $reason, $data = null);

    /**
     * This function will be called after handling all JSON files in the
     * directory. Overwrite this method if something has to be done after
     * handling the files.
     */
    protected function afterHandling () { }

    public function getFails() {
        return $this->fails;
    }

    public function getTotal() {
        return $this->total;
    }
}