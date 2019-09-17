<?php

namespace PHPGuus\FlysystemRaid;

use League\Flysystem\Config;
use PHPGuus\FlysystemRaid\Exceptions\IncorrectNumberOfFileSystems;

class RaidOneAdapter extends AbstractRaidAdapter
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
     * Rebuild the array so that all configured Filesystems have the same data.
     *
     * @return bool
     */
    public function rebuildArray(): bool
    {
        $contents = $this->listContents('', true);
        $fileSystemCount = count($this->fileSystems);

        foreach ($contents as $metadata) {
            if ($metadata['mirrors'] < $fileSystemCount) {
                $cmResult = $this->createMirror($metadata['path']);
                if (!$cmResult) {
                    return false;
                }
            }
        }

        return true;
    }

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
        $originalContents = $this->read($path);
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
                $fileSystem->update($path, $originalContents['contents']);
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
        $originalContents = $this->read($path);
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
                $fileSystem->update($path, $originalContents['contents']);
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
        $originalContents = $this->read($path);
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
                    $fileSystem->write($path, $originalContents['contents']);
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
        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Read a file in a RAID-1 fashion: The first filesystem that can return the
     * contents of the file, determines the return value from this method.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function read($path)
    {
        $contents = false;

        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $contents = $fileSystem->read($path);
                if (false !== $contents) {
                    break;
                }
            }
        }

        if (false !== $contents) {
            return [
                'type' => 'file',
                'path' => $path,
                'contents' => $contents,
            ];
        } else {
            return false;
        }
    }

    /**
     * Read a file as a stream in a RAID-1 fashion: The first filesystem that
     * can return a handle to the stream, determines the return value from
     * this method.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function readStream($path)
    {
        $stream = false;

        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $stream = $fileSystem->readStream($path);
                if (false !== $stream) {
                    break;
                }
            }
        }

        if (false !== $stream) {
            return [
                'type' => 'file',
                'path' => $path,
                'stream' => $stream,
            ];
        } else {
            return false;
        }
    }

    /**
     * List contents of a directory in a RAID-1 fashion: The first filesystem
     * that returns a non-empty array provides the result.
     *
     * @param string $directory
     * @param bool   $recursive
     *
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        $result = [];

        foreach ($this->fileSystems as $fileSystem) {
            $fsResult = $fileSystem->listContents($directory, $recursive);
            if (count($fsResult)) {
                $result = $this->mergeContentLists($result, $fsResult);
            }
        }

        return $result;
    }

    /**
     * Get all the meta data of a file or directory in a RAID-1 fashion: The
     * first filesystem that has the path and can return metadata on that path,
     * determines the result of this method.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMetadata($path)
    {
        $result = false;

        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $result = $fileSystem->getMetaData($path);
                $result['mirrors'] = 0;
                if (false !== $result) {
                    break;
                }
            }
        }

        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                ++$result['mirrors'];
            }
        }

        return $result;
    }

    /**
     * Get the size of a file by calling getMetadata().
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getSize($path)
    {
        $this->getMetadata($path);
    }

    /**
     * Get the mime type of a file in a RAID-1 fashion: The first filesystem that
     * has the path and can return the mime type of that path, determines the
     * result of this method.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getMimetype($path)
    {
        $result = false;
        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $result = $fileSystem->getMimetype($path);
                if (false !== $result) {
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * Get the last modified time of a file as a timestamp in a RAID-1 fashion:
     * The first filesystem that returns a non-false result provides the result
     * of this method.
     *
     * @param string $path
     *
     * @return array|false
     */
    public function getTimestamp($path)
    {
        $result = false;
        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $result = $fileSystem->getTimestamp($path);
                if (false !== $result) {
                    break;
                }
            }
        }

        return $result;
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

    //region Private Implementation

    private function createMirror($path): bool
    {
        $stream = false;

        foreach ($this->fileSystems as $fileSystem) {
            if ($fileSystem->has($path)) {
                $object = $this->read($path);

                break;
            }
        }

        /* If there is no stream, then all mirrors were lost somehow... */
        if (!$object) {
            return false;
        }

        $contents = $object['contents'];
        foreach ($this->fileSystems as $fileSystem) {
            if (!$fileSystem->has($path)) {
                $result = $fileSystem->write($path, $contents, []);
                if (false === $result) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Merge the contents of two listings and make sure to add a count of the
     * mirrors.
     *
     * @param array $a
     * @param array $b
     *
     * @return array
     */
    private function mergeContentLists(array $a, array $b)
    {
        $result = [];
        $countA = count($a);
        for ($i = 0; $i < $countA; ++$i) {
            $itemA = $a[$i];
            $itemA['mirrors'] = 1;
            $result[] = $itemA;
        }

        foreach ($b as $itemB) {
            $found = false;
            $countResult = count($result);
            for ($i = 0; $i < $countResult; ++$i) {
                if (
                    $result[$i]['type'] == $itemB['type'] &&
                    $result[$i]['path'] == $itemB['path'] &&
                    $result[$i]['size'] == $itemB['size']
                ) {
                    $found = true;
                    ++$result[$i]['mirrors'];

                    break;
                }
            }
            if (!$found) {
                $itemB['mirrors'] = 1;
                $result[] = $itemB;
            }
        }

        return $result;
    }

    //endregion
}
