<?php

namespace PHPGuus\FlysystemRaid\Tests;

use League\Flysystem\Adapter\Local;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use PHPGuus\FlysystemRaid\Exceptions\IncorrectNumberOfFileSystems;
use PHPGuus\FlysystemRaid\RaidOneAdapter;
use PHPUnit\Framework\TestCase;

class RaidOneAdapterTest extends TestCase
{
	#region Public Construction Test

	/**
	 * @test
	 */
	public function itCannotCreateARaidAdapter()
	{
		$this->expectException(IncorrectNumberOfFileSystems::class);
		$localAdapter = new RaidOneAdapter([
			new Filesystem(new Local('.tests/disk1'))
		]);
	}

	#endregion

	#region Public Write Tests

	/**
	 * @test
	 */
	public function itCanWriteAFile()
	{
		$result = $this->adapter->write('itCanWriteAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

//		$this->assertTrue($result);
		$this->assertTrue(file_exists('./tests/disk1/itCanWriteAFile.txt'));
		$this->assertTrue(file_exists('./tests/disk2/itCanWriteAFile.txt'));
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanWriteAFile.txt'),
			file_get_contents('./tests/disk2/itCanWriteAFile.txt'));
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanWriteAFile.txt'),
			'The quick brown fox jumps over the lazy dog.');
	}

	/**
	 * @test
	 */
	public function itCannotWriteAFile()
	{
		chmod('./tests/disk2', 0544);
		$previousErrorReporting = error_reporting(E_ERROR);

		$result = $this->adapter->write('itCannotWriteAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		$this->assertFalse($result);
		$this->assertFalse(file_exists('./tests/disk1/itCanWriteAFile.txt'));
		$this->assertFalse(file_exists('./tests/disk2/itCanWriteAFile.txt'));

		error_reporting($previousErrorReporting);
		chmod('./tests/disk2', 0755);
	}

	/**
	 * @test
	 */
	public function itCanWriteAFileUsingAStream()
	{
		$handle = tmpfile();
		fwrite($handle, 'The quick brown fox jumps over the lazy dog.');

		$result = $this->adapter->writeStream('itCanWriteAFileUsingAStream.txt',
			$handle, new Config());

//		$this->assertTrue($result);
		$this->assertTrue(file_exists(
			'./tests/disk1/itCanWriteAFileUsingAStream.txt'));
		$this->assertTrue(file_exists(
			'./tests/disk2/itCanWriteAFileUsingAStream.txt'));
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanWriteAFileUsingAStream.txt'),
			file_get_contents('./tests/disk2/itCanWriteAFileUsingAStream.txt'));
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanWriteAFileUsingAStream.txt'),
			'The quick brown fox jumps over the lazy dog.');
	}

	/**
	 * @test
	 */
	public function itCannotWriteAFileUsingAStream()
	{
		$handle = tmpfile();
		fwrite($handle, 'The quick brown fox jumps over the lazy dog.');

		chmod('./tests/disk2', 0544);
		$previousErrorReporting = error_reporting(E_ERROR);

		$result = $this->adapter->writeStream(
			'itCannotWriteAFileUsingAStream.txt', $handle, new Config());

		$this->assertFalse($result);
		$this->assertFalse(file_exists(
			'./tests/disk1/itCannotWriteAFileUsingAStream.txt'));
		$this->assertFalse(file_exists(
			'./tests/disk2/itCannotWriteAFileUsingAStream.txt'));

		error_reporting($previousErrorReporting);
		chmod('./tests/disk2', 0755);
	}

	#endregion

	#region Public Update Tests

	/**
	 * @test
	 */
	public function itCanUpdateAFile()
	{
		$this->adapter->write('itCanUpdateAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		$result = $this->adapter->update('itCanUpdateAFile.txt',
			'The quick brown dog jumps over the lazy fox.', new Config());

//		$this->assertTrue($result);
		$this->assertTrue(file_exists('./tests/disk1/itCanUpdateAFile.txt'));
		$this->assertTrue(file_exists('./tests/disk2/itCanUpdateAFile.txt'));
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanUpdateAFile.txt'),
			file_get_contents('./tests/disk2/itCanUpdateAFile.txt')
		);
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanUpdateAFile.txt'),
			'The quick brown dog jumps over the lazy fox.'
		);
	}

	/**
	 * @test
	 */
	public function itCannotUpdateAFile()
	{
		$this->adapter->write('itCannotUpdateAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		chmod('./tests/disk2/itCannotUpdateAFile.txt', 0544);
		$previousErrorReporting = error_reporting(E_ERROR);

		$result = $this->adapter->update('itCannotUpdateAFile.txt',
			'The quick brown dog jumps over the lazy fox.', new Config());

//		$this->assertTrue($result);
		$this->assertTrue(file_exists('./tests/disk1/itCannotUpdateAFile.txt'));
		$this->assertTrue(file_exists('./tests/disk2/itCannotUpdateAFile.txt'));
		$this->assertSame(
			file_get_contents('./tests/disk1/itCannotUpdateAFile.txt'),
			file_get_contents('./tests/disk2/itCannotUpdateAFile.txt')
		);
		$this->assertSame(
			file_get_contents('./tests/disk1/itCannotUpdateAFile.txt'),
			'The quick brown fox jumps over the lazy dog.'
		);

		error_reporting($previousErrorReporting);
		chmod('./tests/disk2/itCannotUpdateAFile.txt', 0755);
	}

	/**
	 * @test
	 */
	public function itCanUpdateAFileUsingAStream()
	{
		$this->adapter->write('itCanUpdateAFileUsingAStream.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		$handle = tmpfile();
		fwrite($handle, 'The quick brown dog jumps over the lazy fox.');

		$result = $this->adapter->updateStream(
			'itCanUpdateAFileUsingAStream.txt', $handle, new Config());

//		$this->assertTrue($result);
		$this->assertTrue(file_exists(
			'./tests/disk1/itCanUpdateAFileUsingAStream.txt'));
		$this->assertTrue(file_exists(
			'./tests/disk2/itCanUpdateAFileUsingAStream.txt'));
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanUpdateAFileUsingAStream.txt'),
			file_get_contents('./tests/disk2/itCanUpdateAFileUsingAStream.txt')
		);
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanUpdateAFileUsingAStream.txt'),
			'The quick brown dog jumps over the lazy fox.'
		);
	}

	/**
	 * @test
	 */
	public function itCannotUpdateAFileUsingAStream()
	{
		$this->adapter->write('itCannotUpdateAFileUsingAStream.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		chmod('./tests/disk2/itCannotUpdateAFileUsingAStream.txt', 0544);
		$previousErrorReporting = error_reporting(E_ERROR);

		$handle = tmpfile();
		fwrite($handle, 'The quick brown dog jumps over the lazy fox.');

		$result = $this->adapter->updateStream(
			'itCannotUpdateAFileUsingAStream.txt', $handle, new Config());

		$this->assertFalse($result);
		$this->assertTrue(file_exists(
			'./tests/disk1/itCannotUpdateAFileUsingAStream.txt'));
		$this->assertTrue(file_exists(
			'./tests/disk2/itCannotUpdateAFileUsingAStream.txt'));
		$this->assertSame(file_get_contents(
			'./tests/disk1/itCannotUpdateAFileUsingAStream.txt'),
			file_get_contents(
				'./tests/disk2/itCannotUpdateAFileUsingAStream.txt')
		);
		$this->assertSame(file_get_contents(
			'./tests/disk1/itCannotUpdateAFileUsingAStream.txt'),
			'The quick brown fox jumps over the lazy dog.'
		);

		error_reporting($previousErrorReporting);
		chmod('./tests/disk2/itCannotUpdateAFileUsingAStream.txt', 0755);
	}

	#endregion

	#region Public Rename Tests

	/**
	 * @test
	 */
	public function itCanRenameAFile()
	{
		$this->adapter->write('itCanRenameAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		$result = $this->adapter->rename(
			'itCanRenameAFile.txt', 'newNameItCanRenameAFile.txt');

		$this->assertTrue($result);
		$this->assertTrue(
			file_exists('./tests/disk1/newNameItCanRenameAFile.txt'));
		$this->assertFalse(file_exists('./tests/disk1/itCanRenameAFile.txt'));
		$this->assertTrue(
			file_exists('./tests/disk2/newNameItCanRenameAFile.txt'));
		$this->assertFalse(file_exists('./tests/disk2/itCanRenameAFile.txt'));
	}

	/**
	 * @test
	 */
	public function itCannotRenameAFile()
	{
		$this->adapter->write('itCannotRenameAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		$previousErrorReporting = error_reporting(E_NOTICE);
		chmod('./tests/disk2', 0544);

		$result = $this->adapter->rename(
			'itCannotRenameAFile.txt', 'newNameItCannotRenameAFile.txt');

		$this->assertFalse($result);
		$this->assertTrue(file_exists('./tests/disk1/itCannotRenameAFile.txt'));
		$this->assertFalse(
			file_exists('./tests/disk1/newNameItCannotRenameAFile.txt'));
		$this->assertTrue(file_exists('./tests/disk2/itCannotRenameAFile.txt'));
		$this->assertFalse(
			file_exists('./tests/disk2/newNameItCannotRenameAFile.txt'));

		error_reporting($previousErrorReporting);
		chmod('./tests/disk2', 0755);
	}

	#endregion

	#region Public Copy Tests

	/**
	 * @test
	 */
	public function itCanCopyAFile()
	{
		$this->adapter->write('itCanCopyAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		$result = $this->adapter->copy('itCanCopyAFile.txt',
			'itCanCopyAFile.copy.txt');

		$this->assertTrue($result);
		$this->assertFileExists('./tests/disk1/itCanCopyAFile.txt');
		$this->assertFileExists('./tests/disk1/itCanCopyAFile.copy.txt');
		$this->assertFileExists('./tests/disk2/itCanCopyAFile.txt');
		$this->assertFileExists('./tests/disk2/itCanCopyAFile.copy.txt');
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanCopyAFile.txt'),
			file_get_contents('./tests/disk1/itCanCopyAFile.copy.txt')
		);
		$this->assertSame(
			file_get_contents('./tests/disk2/itCanCopyAFile.txt'),
			file_get_contents('./tests/disk2/itCanCopyAFile.copy.txt')
		);
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanCopyAFile.txt'),
			file_get_contents('./tests/disk2/itCanCopyAFile.txt')
		);
		$this->assertSame(
			file_get_contents('./tests/disk1/itCanCopyAFile.txt'),
			'The quick brown fox jumps over the lazy dog.'
		);
	}

	/**
	 * @test
	 */
	public function itCannotCopyAFile()
	{
		$this->adapter->write('itCannotCopyAFile.txt',
			'The quick brown fox jumps over the lazy dog.', new Config());

		$previousErrorReporting = error_reporting(E_NOTICE);
		chmod('./tests/disk2', 0544);

		$result = $this->adapter->copy('itCannotCopyAFile.txt',
			'itCannotCopyAFile.copy.txt');

		$this->assertFalse($result);
		$this->assertFileExists('./tests/disk1/itCannotCopyAFile.txt');
		$this->assertFileNotExists('./tests/disk1/itCannotCopyAFile.copy.txt');
		$this->assertFileExists('./tests/disk2/itCannotCopyAFile.txt');
		$this->assertFileNotExists('./tests/disk2/itCannotCopyAFile.copy.txt');

		error_reporting($previousErrorReporting);
		chmod('./tests/disk2', 0755);
	}

	#endregion

	#region Protected Attributes

	/**
	 * @var RaidOneAdapter
	 */
	protected $adapter;

	#endregion

	#region Protected Implementation

	/**
	 * This method is called before each test.
	 */
	protected function setUp(): void
	{
		$this->adapter = new RaidOneAdapter([
			new Filesystem(new Local('./tests/disk1')),
			new Filesystem(new Local('./tests/disk2'))
		]);

		parent::setUp(); // TODO: Change the autogenerated stub
	}

	protected function tearDown(): void
	{
		$this->rrmdir('./tests/disk1');
		$this->rrmdir('./tests/disk2');

		parent::tearDown(); // TODO: Change the autogenerated stub
	}

	#endregion

	#region Private Implementation

	private function rrmdir($path)
	{
		$iterator = new \DirectoryIterator($path);
		foreach($iterator as $fileInfo) {
			if($fileInfo->isDot())
				continue;
			elseif($fileInfo->isDir()) {
				$this->rrmdir($fileInfo->getRealPath());
			} elseif($fileInfo->isFile()) {
				unlink($fileInfo->getRealPath());
			}
		}

		rmdir($path);
	}

	#endregion
}