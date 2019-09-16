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
    //region Public Construction Test

    /**
     * @test
     */
    public function itCannotCreateARaidAdapter()
    {
        $this->expectException(IncorrectNumberOfFileSystems::class);
        $localAdapter = new RaidOneAdapter([
            new Filesystem(new Local('./tests/disk1')),
        ]);
    }

    //endregion

    //region Public Write Tests

    /**
     * @test
     */
    public function itCanWriteAFile()
    {
        $result = $this->adapter->write('itCanWriteAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        //		$this->assertTrue($result);
        $this->assertFileExists('./tests/disk1/itCanWriteAFile.txt');
        $this->assertFileExists('./tests/disk2/itCanWriteAFile.txt');
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
        $this->assertFileNotExists('./tests/disk1/itCanWriteAFile.txt');
        $this->assertFileNotExists('./tests/disk2/itCanWriteAFile.txt');

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
        $this->assertFileExists(
            './tests/disk1/itCanWriteAFileUsingAStream.txt');
        $this->assertFileExists(
            './tests/disk2/itCanWriteAFileUsingAStream.txt');
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
        $this->assertFileNotExists(
            './tests/disk1/itCannotWriteAFileUsingAStream.txt');
        $this->assertFileNotExists(
            './tests/disk2/itCannotWriteAFileUsingAStream.txt');

        error_reporting($previousErrorReporting);
        chmod('./tests/disk2', 0755);
    }

    //endregion

    //region Public Update Tests

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
        $this->assertFileExists('./tests/disk1/itCanUpdateAFile.txt');
        $this->assertFileExists('./tests/disk2/itCanUpdateAFile.txt');
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
        $this->assertFileExists('./tests/disk1/itCannotUpdateAFile.txt');
        $this->assertFileExists('./tests/disk2/itCannotUpdateAFile.txt');
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
        $this->assertFileExists(
            './tests/disk1/itCanUpdateAFileUsingAStream.txt');
        $this->assertFileExists(
            './tests/disk2/itCanUpdateAFileUsingAStream.txt');
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
        $this->assertFileExists(
            './tests/disk1/itCannotUpdateAFileUsingAStream.txt');
        $this->assertFileExists(
            './tests/disk2/itCannotUpdateAFileUsingAStream.txt');
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

    //endregion

    //region Public Rename Tests

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
        $this->assertFileExists('./tests/disk1/newNameItCanRenameAFile.txt');
        $this->assertFileNotExists('./tests/disk1/itCanRenameAFile.txt');
        $this->assertFileExists('./tests/disk2/newNameItCanRenameAFile.txt');
        $this->assertFileNotExists('./tests/disk2/itCanRenameAFile.txt');
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
        $this->assertFileExists('./tests/disk1/itCannotRenameAFile.txt');
        $this->assertFileNotExists(
            './tests/disk1/newNameItCannotRenameAFile.txt');
        $this->assertFileExists('./tests/disk2/itCannotRenameAFile.txt');
        $this->assertFileNotExists(
            './tests/disk2/newNameItCannotRenameAFile.txt');

        error_reporting($previousErrorReporting);
        chmod('./tests/disk2', 0755);
    }

    //endregion

    //region Public Copy Tests

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

    //endregion

    //region Public Delete Tests

    /**
     * @test
     */
    public function itCanDeleteAFile()
    {
        $this->adapter->write('itCanDeleteAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        $result = $this->adapter->delete('itCanDeleteAFile.txt');

        $this->assertTrue($result);
        $this->assertFileNotExists('./tests/disk1/itCanDeleteAFile.txt');
        $this->assertFileNotExists('./tests/disk2/itCanDeleteAFile.txt');
    }

    /**
     * @test
     */
    public function itCannotDeleteAFile()
    {
        $this->adapter->write('itCanDeleteAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        chmod('./tests/disk2', 0544);
        $previousErrorReporting = error_reporting(E_ERROR);

        $result = $this->adapter->delete('itCanDeleteAFile.txt');

        $this->assertFalse($result);
        $this->assertFileExists('./tests/disk1/itCanDeleteAFile.txt');
        $this->assertFileExists('./tests/disk2/itCanDeleteAFile.txt');
        $this->assertSame(
            file_get_contents('./tests/disk1/itCanDeleteAFile.txt'),
            'The quick brown fox jumps over the lazy dog.'
        );
        $this->assertSame(
            file_get_contents('./tests/disk2/itCanDeleteAFile.txt'),
            'The quick brown fox jumps over the lazy dog.'
        );

        error_reporting($previousErrorReporting);
        chmod('./tests/disk2', 0755);
    }

    //endregion

    //region Public Directory Tests

    /**
     * @test
     */
    public function itCanCreateADirectory()
    {
        $result = $this->adapter->createDir('itCanCreateADirectory',
            new Config());

//        $this->assertTrue($result);
        $this->assertDirectoryExists('./tests/disk1/itCanCreateADirectory');
        $this->assertDirectoryExists('./tests/disk2/itCanCreateADirectory');
    }

    /**
     * @test
     */
    public function itCannotCreateADirectory()
    {
        chmod('./tests/disk2', 0544);
        $previousErrorReporting = error_reporting(E_ERROR);

        $result = $this->adapter->createDir('itCannotCreateADirectory',
            new Config());

        $this->assertFalse($result);
        $this->assertDirectoryNotExists(
            './tests/disk1/itCannotCreateADirectory');
        $this->assertDirectoryNotExists(
            './tests/disk2/itCannotCreateADirectory');

        error_reporting($previousErrorReporting);
        chmod('./tests/disk2', 0755);
    }

    /**
     * @test
     */
    public function itCanDeleteADirectory()
    {
        $this->adapter->createDir('itCanDeleteADirectory', new Config());

        $result = $this->adapter->deleteDir('itCanDeleteADirectory');

        $this->assertTrue($result);
        $this->assertDirectoryNotExists('./tests/disk1/itCanDeleteADirectory');
        $this->assertDirectoryNotExists('./tests/disk2/itCanDeleteADirectory');
    }

    /**
     * @test
     */
    public function itCannotDeleteADirectory()
    {
        $this->adapter->createDir('itCannotDeleteADirectory', new Config());

        chmod('./tests/disk2', 0544);
        $previousErrorReporting = error_reporting(E_ERROR);

        $result = $this->adapter->deleteDir('itCannotDeleteADirectory');

        $this->assertFalse($result);
        $this->assertDirectoryNotExists('./tests/disk1/itCannotDeleteADirectory');
        $this->assertDirectoryExists('./tests/disk2/itCannotDeleteADirectory');

        error_reporting($previousErrorReporting);
        chmod('./tests/disk2', 0755);
    }

    //endregion

    //region Public Visibility Tests

    /**
     * @test
     */
    public function itCanGetVisibilityOfAFile()
    {
        $this->adapter->write('itCanGetVisibilityOfAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        $result = $this->adapter->getVisibility(
            'itCanGetVisibilityOfAFile.txt');

        $this->assertTrue('0664' == $result);
    }

    /**
     * @test
     */
    public function itCanGetVisibilityOfAFileWithOneMirrorReadOnly()
    {
        $this->adapter->write(
            'itCanGetVisibilityOfAFileWithOneMirrorReadOnly.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        chmod('./tests/disk1', 0300);
        $previousErrorReporting = error_reporting(E_ERROR);

        $result = $this->adapter->getVisibility(
            'itCanGetVisibilityOfAFileWithOneMirrorReadOnly.txt');

        $this->assertSame($result, '0664');

        error_reporting($previousErrorReporting);
        chmod('./tests/disk1', 0755);
    }

    /**
     * @test
     */
    public function itCannotGetVisibilityOfAFile()
    {
        $this->adapter->write(
            'itCannotGetVisibilityOfAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        chmod('./tests/disk1', 0600);
        chmod('./tests/disk2', 0600);
        $previousErrorReporting = error_reporting(E_ERROR);

        $result = $this->adapter->getVisibility(
            'itCannotGetVisibilityOfAFile.txt');

        $this->assertFalse($result);

        error_reporting($previousErrorReporting);
        chmod('./tests/disk1', 0755);
        chmod('./tests/disk2', 0755);
    }

    /**
     * @test
     */
    public function itCanSetVisibilityOfAFile()
    {
        $this->adapter->write('itCanSetVisibilityOfAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        $result = $this->adapter->setVisibility('itCanSetVisibilityOfAFile.txt',
            'private');

        $this->assertIsString($result);
        $this->assertSame($result, 'private');
    }

    /**
     * @test
     */
    public function itCannotSetVisibilityOfAFile()
    {
        $this->adapter->write('itCannotSetVisibilityOfAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        chmod('./tests/disk2', 0400);
        $previousErrorReporting = error_reporting(E_ERROR);

        $result = $this->adapter->setVisibility(
            'itCannotSetVisibilityOfAFile.txt', 'private');

        $this->assertFalse($result);

        error_reporting($previousErrorReporting);
        chmod('./tests/disk2', 0755);
    }

    //endregion

    //region Public Has Tests

    /**
     * @test
     */
    public function itHasAFile()
    {
        $this->adapter->write('itHasAFile.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        $result = $this->adapter->has('itHasAFile.txt');

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function itHasAFileWhenTheSecondMirrorWasLost()
    {
        $this->adapter->write('itHasAFileWhenTheSecondMirrorWasLost.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        unlink('./tests/disk2/itHasAFileWhenTheSecondMirrorWasLost.txt');

        $result = $this->adapter
            ->has('itHasAFileWhenTheSecondMirrorWasLost.txt');

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function itHasAFileWhenTheFirstMirrorWasLost()
    {
        $this->adapter->write('itHasAFileWhenTheFirstMirrorWasLost.txt',
            'The quick brown fox jumps over the lazy dog.', new Config());

        unlink('./tests/disk1/itHasAFileWhenTheFirstMirrorWasLost.txt');

        $result = $this->adapter
            ->has('itHasAFileWhenTheFirstMirrorWasLost.txt');

        $this->assertTrue($result);
    }

    /**
     * @test
     */
    public function itDoesNotHaveAFileThatDoesNotExist()
    {
        $result = $this->adapter->has('itDoesNotHaveAFileThatDoesNotExist.txt');

        $this->assertFalse($result);
    }

    //endregion

    //region Protected Attributes

    /**
     * @var RaidOneAdapter
     */
    protected $adapter;

    //endregion

    //region Protected Implementation

    /**
     * This method is called before each test.
     */
    protected function setUp(): void
    {
        $this->adapter = new RaidOneAdapter([
            new Filesystem(new Local('./tests/disk1')),
            new Filesystem(new Local('./tests/disk2')),
        ]);

        parent::setUp(); // TODO: Change the autogenerated stub
    }

    protected function tearDown(): void
    {
        $this->rrmdir('./tests/disk1');
        $this->rrmdir('./tests/disk2');

        parent::tearDown(); // TODO: Change the autogenerated stub
    }

    //endregion

    //region Private Implementation

    private function rrmdir($path)
    {
        $iterator = new \DirectoryIterator($path);
        foreach ($iterator as $fileInfo) {
            if ($fileInfo->isDot()) {
                continue;
            } elseif ($fileInfo->isDir()) {
                $this->rrmdir($fileInfo->getRealPath());
            } elseif ($fileInfo->isFile()) {
                unlink($fileInfo->getRealPath());
            }
        }

        rmdir($path);
    }

    //endregion
}
