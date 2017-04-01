<?php

namespace Cover;

use Config\Config;
use Util\ISBNtest;
use Services\LogService;

/**
 * Class Amazon
 *
 * Amazon ECS API wrapper for getting covers.
 *
 * @package Cover
 */
class Amazon implements CoverProvider  {

    private $config;
    private $logService;

    private $log;

    public function __construct(Config $config, LogService $logService) {
        $this->config = $config;
        $this->logService = $logService;

        $this->log = $this->logService->getLog();
    }

    public function getCovers($title) {

        // Prevent throttling by waiting a second
        usleep(1000000);

        // We use the ISBN for the check since Amazons ID (ASIN)
        // is equal to the 10-digit ISBN for media etc.
        $isbn = isset($title['004A']['A']) ? $title['004A']['A'] : NULL;
        if (is_null($isbn)) {
            $isbn = isset($title['004A']['0']) ? $title['004A']['0'] : NULL;
        }

        $cover = NULL;

        $logMessage = 'ISBN: ' . $isbn . ' ';

        if (!is_null($isbn)) {

            // convert the ISBN to a 10-digit ISBN
            $isc = new ISBNtest();
            $isc->set_isbn($isbn);
            $logMessage .= $isc->get_isbn10() . ' ';

            // get the URLs
            $cover = $this->getImg($isc->get_isbn10());
        }

        $this->log->addInfo($logMessage);

        return is_null($cover) ? false : $cover;
    }

    /**
     * Retrieve the URLs of the cover images for a title with the given ASIN.
     *
     * @param $asin string ASIN of the title
     * @return array|null Array containing the URLs for the large and medium
     *         version of the cover image or null if Amazon doesn't have a cover for
     *         the title.
     */
    private function getImg($asin) {

        $AWSAccessKeyId = $this->config->getValue('cover', 'access');
        $SecretAccessKey = $this->config->getValue('cover', 'secret');

        $ItemId = $asin;
        $Timestamp = gmdate('Y-m-d\TH:i:s\Z');
        $Timestamp = str_replace(':', '%3A', $Timestamp);
        $ResponseGroup = 'Images';
        $ResponseGroup = str_replace(',', '%2C', $ResponseGroup);

        $String = "AWSAccessKeyId=$AWSAccessKeyId&
AssociateTag=PutYourAssociateTagHere&
IdType=ASIN&
ItemId=$ItemId&
Operation=ItemLookup&
ResponseGroup=$ResponseGroup&
Service=AWSECommerceService&
Timestamp=$Timestamp&
Version=2011-08-01";

        $String = str_replace("\n", '', $String);

        $Prepend = "GET\necs.amazonaws.de\n/onca/xml\n";
        $PrependString = $Prepend . $String;

        $Signature = base64_encode(hash_hmac('sha256', $PrependString, $SecretAccessKey, True));
        $Signature = str_replace('+', '%2B', $Signature);
        $Signature = str_replace('=', '%3D', $Signature);

        $BaseUrl = 'http://ecs.amazonaws.de/onca/xml?';
        $SignedRequest = $BaseUrl . $String . "&Signature=" . $Signature;

        $XML = @simplexml_load_file($SignedRequest);

        if (isset($XML->Items->Item->MediumImage->URL[0]) && isset($XML->Items->Item->LargeImage->URL[0])) {
            return [
                'md' => strval($XML->Items->Item->MediumImage->URL[0]),
                'lg' => strval($XML->Items->Item->LargeImage->URL[0])
            ];
        } else {
            return null;
        }
    }

}