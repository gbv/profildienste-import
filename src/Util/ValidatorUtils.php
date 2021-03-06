<?php

namespace Util;


/**
 * Class ValidatorUtils
 *
 * This traits contains all kind of utility functions which can be used
 * by Importers for validating the input data.
 *
 * @package Util
 */
trait ValidatorUtils {

    protected function checkNameValueListSubfield($data, $field, $checkForDefault = true) {
        $defaultSeen = false;

        $keys = [];

        foreach ($data[$field] as $item) {

            if (!is_array($item)) {
                return false;
            }

            if (!$this->checkField($item, 'name') || !$this->checkField($item, 'value')) {
                return false;
            }

            if (!is_string($item['name']) || !is_string($item['value'])) {
                return false;
            }

            if (isset($item['default']) && $item['default'] === true) {
                if (!$defaultSeen) {
                    $defaultSeen = true;
                } else { // duplicate default
                    return false;
                }
            }

            // check if the item only contains the allowed fields
            $allKeys = array_keys($item);
            if ($allKeys != ['name', 'value'] && $allKeys  != ['name', 'value', 'default']) {
                return false;
            }

            $keys[] = $item['name'];
        }

        if ($checkForDefault && !$defaultSeen) {
            return false;
        }

        // check if there are any duplicate entries
        if (count(array_unique($keys)) !== count($keys)) {
            return false;
        }

        return true;
    }

    protected function checkIfAllFieldsExist($data, $fields, $exclusively = false) {
        return array_reduce(array_map(function ($field) use ($data) {
            return isset($data[$field]) && !empty($data[$field]) && is_string($data[$field]);
        }, $fields), function ($carry, $item) {
            return $carry && $item;
        }, true) && (!$exclusively || array_keys($data) == $fields);
    }

    protected function checkIfAnyFieldExists($data, $fields) {
        return array_reduce(array_map(function ($field) use ($data) {
            return isset($data[$field]) && !empty($data[$field]) && is_string($data[$field]);
        }, $fields), function ($carry, $item) {
            return $carry || $item;
        }, false);
    }

    protected function checkIfAnySubfieldExists($data, $fields) {
        return array_reduce(array_map(function ($field) use ($data) {
            return isset($data[$field]) && is_array($data[$field]) && count($data[$field]) > 0;
        }, $fields), function ($carry, $item) {
            return $carry || $item;
        }, false);
    }

    protected function checkIfAllSubfieldsExist($data, $fields, $exclusively = false) {
        return array_reduce(array_map(function ($field) use ($data) {
            return isset($data[$field]) && is_array($data[$field]) && count($data[$field]) > 0;
        }, $fields), function ($carry, $item) {
            return $carry && $item;
        }, true)  && (!$exclusively || array_keys($data) == $fields);
    }


    protected function checkField($data, $field, $subfield = null) {
        if (is_null($subfield)) {
            return isset($data[$field]) && !empty($data[$field]);
        } else {
            return isset($data[$field][$subfield]) && !empty($data[$field][$subfield]);
        }
    }

}