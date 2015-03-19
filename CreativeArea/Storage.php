<?php namespace CreativeArea;

/**
 * Interface Storage.
 */
interface Storage
{
    /**
     * @param $key
     *
     * @return Storage\Record
     *
     * returns/creates the record corresponding to the given key
     */
    public function &getRecord($key);
};
