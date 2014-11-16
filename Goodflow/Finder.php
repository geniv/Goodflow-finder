<?php
/*
 * Finder.php
 *
 * Copyright 2014 geniv
 *
 */

namespace Goodflow;

/**
 * rozsireni API finderu o moznost razeni
 * source: http://forum.nette.org/cs/5331-2010-09-15-trida-nette-finder-pro-prochazeni-adresarovou-strukturou#p46244
 *
 * @package goodglow
 * @author geniv
 * @version 1.04
 */
class Finder extends \Nette\Utils\Finder
{
    private $userDefinedSort;

    /**
     * Sets the user defined sprt comparison function
     * @param callback $callback
     * @return Finder
     */
    public function userDefinedSort($callback)
    {
        $this->userDefinedSort = $callback;
        return $this;
    }


    /**
     * order by name
     * @return Finder
     */
    public function orderByName()
    {
        $this->userDefinedSort = function($f1, $f2) {
            return \strcasecmp($f1->getFilename(), $f2->getFilename());
        };
        return $this;
    }


    /**
     * order by modify time
     * @return Finder
     */
    public function orderByMTime()
    {
        $this->userDefinedSort = function($f1, $f2) {
            return $f2->getMTime() - $f2->getMTime();
        };
        return $this;
    }


    /**
     * order by natural name
     * @param  boolean $caseSensitive true = case sensitive, false = case insensitive
     * @return Finder
     */
    public function orderByNatularName($caseSensitive = true)
    {
        $this->userDefinedSort = ($caseSensitive ? 'natsort' : 'natcasesort');
        return $this;
    }


    /**
     * Returns iterator.
     * @return \Iterator
     */
    public function getIterator()
    {
        $iterator = parent::getIterator();
        if ($this->userDefinedSort === NULL) {
            return $iterator;
        }

        $iterator = new \ArrayIterator(\iterator_to_array($iterator));
        if (is_string($this->userDefinedSort)) {
            $sort = $this->userDefinedSort;
            $iterator->$sort();
        } else {
            $iterator->uasort($this->userDefinedSort);
        }
        return $iterator;
    }
}