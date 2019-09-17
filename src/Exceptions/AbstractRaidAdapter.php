<?php

namespace PHPGuus\FlysystemRaid\Exceptions;

use League\Flysystem\Adapter\AbstractAdapter;

abstract class AbstractRaidAdapter extends AbstractAdapter
{
    //region Public Access

    /**
     * Rebuild the array so that all configured Filesystems have the same data.
     *
     * @return bool
     */
    abstract public function rebuildArray(): bool;

    //endregion
}
