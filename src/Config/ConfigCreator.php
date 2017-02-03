<?php

namespace Config;

use Exception;
use Util\Util;

class ConfigCreator {

    public function createConfigFile($filename, $content) {

        // we need that command to determine the absolute paths
        if (getcwd() === false) {
            throw new Exception("Can't get the current working path.\n");
        }

        // try to write the configuration file
        if (file_put_contents($filename, json_encode($content, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            throw new Exception("Couldn't create the config file. Please make sure you have sufficient rights to write in this directory.");
        }

        fprintf(STDOUT, "A configuration file template has been copied to %s.\nPlease review the configuration to make sure it can be used.\n", getcwd() . '/' . $filename);
        exit(0);
    }
}