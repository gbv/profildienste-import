<?php

namespace Cover;

/**
 * Interface CoverProvider
 *
 * All wrappers for the APIs of external cover providers
 * have to implement this interface
 *
 * @package Cover
 */
interface CoverProvider {

    /**
     * Get the covers for the given title.
     *
     * @param $title array Title data from the database
     * @return array|bool Returns false if the cover service doesn't have
     *         a cover for the title or an array with URLs for a large and
     *         medium version of the cover image.
     */
    public function getCovers($title);

}