<?php

namespace PHPGuus\FlysystemRaid;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use PHPGuus\FlysystemRaid\Exceptions\IncorrectNumberOfFileSystems;

class RaidOneAdapter extends AbstractAdapter
{
    //region Public Construction

    /**
     * RaidAdapter constructor.
     *
     * @param array $fileSystems
     *
     * @throws IncorrectNumberOfFileSystems
     */
    public function __construct(array $fileSystems)
    {
        if (count($fileSystems) < 2) {
            throw new IncorrectNumberOfFileSystems(count($fileSystems), 2);
        }

        $this->fileSystems = $fileSystems;
    }

    //endregion

    //region Public Access

    /**
     * Write a new file in a RAID-1 fashion. If the file is not written to at
     * least two filesystems, the write is deemed a failure.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function write($path, $contents, Config $config)
    {
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->write($path, $contents);
            if ($result) {
                ++$trueResults;
            } else {
                break;
            }
        }

        if ($trueResults < 2) {
            foreach ($this->fileSystems as $fileSystem) {
                if ($fileSystem->has($path)) {
                    $fileSystem->delete($path);
                }
            }

            return false;
        } else {
            return $this->getMetadata($path);
        }
    }

    /**
     * Write a new file using a stream in a RAID-1 fashion. If the file is not
     * written to at least two filesystems, the write is deemed a failure.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function writeStream($path, $resource, Config $config)
    {
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->writeStream($path, $resource);
            if ($result) {
                ++$trueResults;
            } else {
                break;
            }
        }

        if ($trueResults < 2) {
            foreach ($this->fileSystems as $fileSystem) {
                if ($fileSystem->has($path)) {
                    $fileSystem->delete($path);
                }
            }

            return false;
        } else {
            return $this->getMetadata($path);
        }
    }

    /**
     * Update a file in a RAID-1 fashion. If the file is not updated at
     * all filesystems, the update is deemed a failure and will be reverted in
     * all filesystems.
     *
     * @param string $path
     * @param string $contents
     * @param Config $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function update($path, $contents, Config $config)
    {
        /*
         * TODO Actually use $this->read() instead
         */
        $originalContents = $this->fileSystems[0]->read($path);
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->update($path, $contents);
            if ($result) {
                ++$trueResults;
            } else {
                break;
            }
        }

        if ($trueResults < count($this->fileSystems)) {
            foreach ($this->fileSystems as $fileSystem) {
                $fileSystem->update($path, $originalContents);
            }

            return false;
        } else {
            return $this->getMetadata($path);
        }
    }

    /**
     * Update a file using a stream in a RAID-1 fashion. If the file is not
     * updated at all filesystems, the update is deemed a failure and will be
     * reverted in all filesystems.
     *
     * @param string   $path
     * @param resource $resource
     * @param Config   $config   Config object
     *
     * @return array|false false on failure file meta data on success
     */
    public function updateStream($path, $resource, Config $config)
    {
        /*
         * TODO Actually use $this->read() instead
         */
        $originalContents = $this->fileSystems[0]->read($path);
        $position = ftell($resource);
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->updateStream($path, $resource);
            if ($result) {
                ++$trueResults;
            } else {
                break;
            }
            fseek($resource, $position, SEEK_SET);
        }

        if ($trueResults < count($this->fileSystems)) {
            foreach ($this->fileSystems as $fileSystem) {
                $fileSystem->update($path, $originalContents);
            }

            return false;
        } else {
            return $this->getMetadata($path);
        }
    }

    /**
     * Rename a file.
     *
     * @param string $path
     * @param string $newpath
     *
     * @return bool
     */
    public function rename($path, $newpath)
    {
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->rename($path, $newpath);
            if ($result) {
                ++$trueResults;
            }
        }

        if ($trueResults < count($this->fileSystems)) {
            foreach ($this->fileSystems as $fileSystem) {
                if ($fileSystem->has($newpath)) {
                    $fileSystem->rename($newpath, $path);
                }
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function copy($path, $newpath)
    {
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->copy($path, $newpath);
            if ($result) {
                ++$trueResults;
            } else {
                break;
            }
        }

        if ($trueResults < count($this->fileSystems)) {
            foreach ($this->fileSystems as $fileSystem) {
                if ($fileSystem->has($newpath)) {
                    $fileSystem->delete($newpath);
                }
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete($path)
    {
        /*
         * TODO Actually use $this->read() instead
         */
        $originalContents = $this->fileSystems[0]->read($path);
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->delete($path);
            if ($result) {
                ++$trueResults;
            } else {
                break;
            }
        }

        if ($trueResults < count($this->fileSystems)) {
            foreach ($this->fileSystems as $fileSystem) {
                if (!$fileSystem->has($path)) {
                    $fileSystem->write($path, $originalContents);
                }
            }

            return false;
        } else {
            return true;
        }
    }

    /**
     * Delete a directory in a RAID-1 fashion. If a deleteDir operation fails
     * for one of the underlying filesystems, this cannot be repaired magically
     * as most Flysystem Adapters chose to recursively delete files along the
     * way.
     *
     * @param string $dirname
     *
     * @return bool
     */
    public function deleteDir($dirname)
    {
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->deleteDir($dirname);
            if ($result) {
                ++$trueResults;
            }
        }

        if ($trueResults < count($this->fileSystems)) {
            return false;
        } else {
            return true;
        }
    }

    /**
     * Create a directory in a RAID-1 fashion: Create the directory for each
     * underlying filesystem, and if that fails on one of them, revert all
     * filesystems by deleting the created directory.
     *
     * @param string $dirname directory name
     * @param Config $config
     *
     * @return array|false
     */
    public function createDir($dirname, Config $config)
    {
        $trueResults = 0;

        foreach ($this->fileSystems as $fileSystem) {
            $result = $fileSystem->createDir($dirname);
            if (false === $result) {
                break;
            } else {
                ++$trueResults;
            }
        }

        if ($trueResults < count($this->fileSystems)) {
            foreach ($this->fileSystems as $fileSystem) {
                if ($fileSystem->has($dirname)) {
                    $fileSystem->deleteDir($dirname);
                }
            }

            return false;
        }

        return $this->getMetadata($dirname);
    }

    /**
     * Set the visibility for a file in a RAID-1 fashion by setting the
     * visibility on the $path for each underlying filesystem. Revert if one
     * has a failure setting the visibility.
     *
     * @param string $path
     * @param string $visibility
     *
     * @return array|false file meta data
     */
    public function setVisibility($path, $visibility)
    {
        $trueResults = 0;

        $originalVisibility = $this->getVisibility($path);

        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $result = $fileSystem->setVisibility($path, $visibility);
                if ($result) {
                    ++$trueResults;
                } else {
                    break;
                }
            }
        }

        if ($trueResults < count($this->fileSystems)) {
            foreach ($this->fileSystems as $fileSystem) {
                if ($fileSystem->has($path)) {
                    $fileSystem->setVisibility($path,
                        $originalVisibility['visibility']);
                }
            }

            return false;
        }

        return $this->getVisibility($path);
    }

    /**
     * Check whether a file exists in a RAID-1 fashion: The first file system
     * that has the requested file, determines the full answer.
     *
     * @param string $path
     *
     * @return array|bool|null
     */
    public function has($path)
    {
        foreach($this->fileSystems as $fileSystem) {
            if($fileSystem->has($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function read($path)
    {
        // TODO: Implement read() method.
    }

    /**
     * {@inheritdoc}
     */
    public function readStream($path)
    {
        // TODO: Implement readStream() method.
    }

    /**
     * {@inheritdoc}
     */
    public function listContents($directory = '', $recursive = false)
    {
        // TODO: Implement listContents() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getMetadata($path)
    {
        // TODO: Implement getMetadata() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getSize($path)
    {
        // TODO: Implement getSize() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getMimetype($path)
    {
        // TODO: Implement getMimetype() method.
    }

    /**
     * {@inheritdoc}
     */
    public function getTimestamp($path)
    {
        // TODO: Implement getTimestamp() method.
    }

    /**
     * Get the visibility of a file in a RAID-1 fashion: The first filesystem
     * that returns a non-false result provides the result of this method.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getVisibility($path)
    {
        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $result = $fileSystem->getVisibility($path);
                if (false !== $result) {
                    return $result;
                }
            }
        }

        return false;
    }

    //endregion

    //region Private Attributes

    /**
     * @var array
     */
    private $fileSystems;

    //endregion
}
