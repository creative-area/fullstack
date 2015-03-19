<?php namespace CreativeArea\Storage;

/**
 * Interface Record.
 */
interface Record
{
    /**
     * @return bool|string
     *
     * returns the string associated with this record, false is none exists
     */
    public function read();

    /**
     * locks the record for an eventual write.
     */
    public function lock();

    /**
     * @param string $content
     *
     * sets the string associated with this record
     */
    public function write(&$content);
}
