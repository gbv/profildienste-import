<?php

namespace Cover;

use Util\Log;
use Util\Config;
use Util\ISBNtest;

class Amazon implements CoverService{

    public function getCovers($title){

        $isbn=isset($title['004A']['A'])? $title['004A']['A'] : NULL;
        if(is_null($isbn)){
            $isbn = isset($title['004A']['0'])? $title['004A']['0'] : NULL;
        }

        $cover = NULL;

        $logMessage = 'ISBN: '.$isbn.' ';

        if (!is_null($isbn)){
            $isc = new ISBNtest();
            $isc -> set_isbn($isbn);
            $logMessage.= $isc -> get_isbn10().' ';
            $cover = $this -> getImg($isc -> get_isbn10());
        }

        Log::getInstance()->getLog()->addInfo($logMessage);

        return is_null($cover) ? false : $cover;
    }

    private function getImg($asin){

        $AWSAccessKeyId = Config::getInstance()->getValue('cover', 'access');
        $SecretAccessKey = Config::getInstance()->getValue('cover', 'secret');

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

        if(isset($XML-> Items -> Item -> MediumImage -> URL[0]) && isset($XML-> Items -> Item ->LargeImage -> URL[0])){
            return array('md' => strval($XML-> Items -> Item -> MediumImage -> URL[0]), 'lg' => strval($XML-> Items -> Item -> LargeImage -> URL[0]));
        }else{
            return NULL;
        }
    }

}