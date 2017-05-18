<?php

$PHAR_NAME = basename(__DIR__).'.phar';
$extraDirs = ['bootstrap', 'resources'];

//assert that we are in the project directory
if (__DIR__ !== getcwd()) {
    logError('The build script must be run from the project directory.');
    exit(-1);
}

// check if we can manipulate PHARs
if (ini_get('phar.readonly')) {
    logError(sprintf("You have to disable the PHAR readonly mode by executing the following command: \nphp -d phar.readonly=off %s", $argv[0]));
    exit(-1);
}

// get the path of the phar-composer executable
exec('which phar-composer', $output, $ret);
if ($ret !== 0) {
    logError('There was an error finding the phar-composer executable!');
    array_map(function ($line) { logError($line); }, $output);
    exit(-2);
}
$pharComposerPath = $output[0];

logInfo('Welcome to the build tool!');

if(is_dir('vendor')) {
    logInfo('Removing the vendor directory');
    passthru('rm -rf vendor/', $ret);
    if ($ret !== 0) {
        logError('Could not remove the vendor directory!');
        exit(-2);
    };
}

logInfo('Installing dependencies (without dev-dependencies)');
passthru('composer install --no-dev', $ret);
if ($ret !== 0) {
    logError('Could not install dependencies!');
    exit(-2);
};

logInfo('Building the PHAR');
passthru("php -d phar.readonly=off $pharComposerPath build .", $ret);

if ($ret !== 0) {
    logError('Could not build PHAR!');
    exit(-2);
};

logInfo('Adding the extra dirs to the PHAR');
$phar = new Phar($PHAR_NAME);

foreach ($extraDirs as $extra) {

    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($extra, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST,
        RecursiveIteratorIterator::CATCH_GET_CHILD
    );

    foreach ($it as $path => $f) {
        if (!$f->isDir()) {
            $phar->addFile($f, $f);
        }
    }
}

logInfo('Persisting version information');
$version = getVersionInfo();
if (!is_null($version)) {
    $phar->addFromString('resources/version.json', json_encode($version));
}

logInfo('Build finished!');

function logInfo($msg) {
    fprintf(STDOUT, "[INFO] %s \n", $msg);
}

function logError($msg) {
    fprintf(STDERR, "[ERR] %s \n", $msg);
}

function getVersionInfo() {

    $commitDate = new \DateTime(trim(exec('git log -n1 --pretty=%ci HEAD 2>/dev/null')));
    $commitDate->setTimezone(new \DateTimeZone('UTC'));
    $hash = trim(exec('git log --pretty="%h" -n1 HEAD 2>/dev/null'));

    if (empty($commitDate) || empty($hash)) {
        return null;
    }

    return [
        'date' => $commitDate->format('d-m-Y H:m:s'),
        'hash' => $hash
    ];
}