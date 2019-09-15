<?php

namespace PHPGuus\FlysystemRaid;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;
use PHPGuus\FlysystemRaid\Exceptions\IncorrectNumberOfFileSystems;

class RaidOneAdapter extends AbstractAdapter
{
	#region Public Construction

	/**
	 * RaidAdapter constructor.
	 *
	 * @param array $fileSystems
	 * @throws IncorrectNumberOfFileSystems
	 */
	public function __construct(array $fileSystems)
	{
		if(count($fileSystems) < 2) {
			throw new IncorrectNumberOfFileSystems(count($fileSystems), 2);
		}

		$this->fileSystems = $fileSystems;
	}

	#endregion

	#region Public Access

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

		foreach($this->fileSystems as $fileSystem) {
			$result = $fileSystem->write($path, $contents);
			if($result)
				$trueResults++;
			else
				break;
		}

		if($trueResults < 2) {
			foreach($this->fileSystems as $fileSystem) {
				if($fileSystem->has($path))
					$fileSystem->delete($path);
			}

			return FALSE;
		} else
			return $this->getMetadata($path);
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
		$position = ftell($resource);

		foreach($this->fileSystems as $fileSystem) {
			$result = $fileSystem->writeStream($path, $resource);
			if($result)
				$trueResults++;
			else
				break;
		}

		if($trueResults < 2) {
			foreach($this->fileSystems as $fileSystem) {
				if($fileSystem->has($path))
					$fileSystem->delete($path);
			}

			return FALSE;
		} else
			return $this->getMetadata($path);
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

		foreach($this->fileSystems as $fileSystem) {
			$result = $fileSystem->update($path, $contents);
			if($result)
				$trueResults++;
			else
				break;
		}

		if($trueResults < count($this->fileSystems)) {
			foreach($this->fileSystems as $fileSystem) {
				$fileSystem->update($path, $originalContents);
			}

			return FALSE;
		} else
			return $this->getMetadata($path);
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

		foreach($this->fileSystems as $fileSystem) {
			$result = $fileSystem->updateStream($path, $resource);
			if($result)
				$trueResults++;
			else
				break;
			fseek($resource, $position, SEEK_SET);
		}

		if($trueResults < count($this->fileSystems)) {
			foreach($this->fileSystems as $fileSystem) {
				$fileSystem->update($path, $originalContents);
			}

			return FALSE;
		} else
			return $this->getMetadata($path);
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

		foreach($this->fileSystems as $fileSystem) {
			$result = $fileSystem->rename($path, $newpath);
			if($result)
				$trueResults++;
		}

		if($trueResults < count($this->fileSystems)) {
			foreach($this->fileSystems as $fileSystem) {
				if($fileSystem->has($newpath)) {
					$fileSystem->rename($newpath, $path);
				}
			}

			return FALSE;
		} else
			return TRUE;
	}

	/**
	 * @inheritDoc
	 */
	public function copy($path, $newpath)
	{
		// TODO: Implement copy() method.
	}

	/**
	 * @inheritDoc
	 */
	public function delete($path)
	{
		/*
		 * TODO Actually use $this->read() instead
		 */
		$originalContents = $this->fileSystems[0]->read($path);
		$trueResults = 0;

		foreach($this->fileSystems as $fileSystem) {
			$result = $fileSystem->delete($path);
			if($result)
				$trueResults++;
			else
				break;
		}

		if($trueResults < count($this->fileSystems)) {
			foreach($this->fileSystems as $fileSystem) {
				if(!$fileSystem->has($path)) {
					$fileSystem->write($path, $originalContents);
				}
			}

			return FALSE;
		} else
			return TRUE;
	}

	/**
	 * @inheritDoc
	 */
	public function deleteDir($dirname)
	{
		// TODO: Implement deleteDir() method.
	}

	/**
	 * @inheritDoc
	 */
	public function createDir($dirname, Config $config)
	{
		// TODO: Implement createDir() method.
	}

	/**
	 * @inheritDoc
	 */
	public function setVisibility($path, $visibility)
	{
		// TODO: Implement setVisibility() method.
	}

	/**
	 * @inheritDoc
	 */
	public function has($path)
	{
		// TODO: Implement has() method.
	}

	/**
	 * @inheritDoc
	 */
	public function read($path)
	{
		// TODO: Implement read() method.
	}

	/**
	 * @inheritDoc
	 */
	public function readStream($path)
	{
		// TODO: Implement readStream() method.
	}

	/**
	 * @inheritDoc
	 */
	public function listContents($directory = '', $recursive = false)
	{
		// TODO: Implement listContents() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getMetadata($path)
	{
		// TODO: Implement getMetadata() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getSize($path)
	{
		// TODO: Implement getSize() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getMimetype($path)
	{
		// TODO: Implement getMimetype() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getTimestamp($path)
	{
		// TODO: Implement getTimestamp() method.
	}

	/**
	 * @inheritDoc
	 */
	public function getVisibility($path)
	{
		// TODO: Implement getVisibility() method.
	}

	#endregion

	#region Private Attributes

	/**
	 * @var array
	 */
	private $fileSystems;

	#endregion
}