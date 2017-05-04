<?php
namespace Importer;

use Util\Util;

/**
 * Class RemoteFetcher
 *
 * Gets the JSON files from the (remote) CBS and saves them in the
 * respective directories
 *
 */
class RemoteFetcher extends Importer {

    public function run() {

        $dirs = $this->config->getValue('remote', 'dirs');
        foreach ($dirs as $dir) {

            $dir = Util::addTrailingSlash($dir);

            $host = $this->config->getValue('remote', 'user') . '@' . $this->config->getValue('remote', 'host') . ':' . $dir;

            exec('rsync -azPi --stats  --remove-source-files ' . $host . ' ' . $this->config->getValue('dirs', 'title_import') . ' 2>&1', $output, $ret);

            foreach ($output as $o) {
                $this->log->addInfo($o);
            }

            if ($ret != 0) {
                $this->log->addError('Fetching from remote host failed! Please see output above.');
            }
        }

    }

    /**
     * Further describes the purpose of the importer.
     *
     * @return string Description
     */
    public function getDescription() {
        return 'Remote fetcher produces no stats';
    }
}