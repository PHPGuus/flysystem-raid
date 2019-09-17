# Flysystem-raid

[![Latest Version on Packagist](https://img.shields.io/packagist/v/phpguus/flysystem-raid?style=flat-square)](https://packagist.org/packages/phpguus/flysystem-raid)
[![Build Status](https://img.shields.io/travis/com/phpguus/flysystem-raid/master.svg?style=flat-square)](https://travis-ci.com/phpguus/flysystem-raid)
[![StyleCI](https://styleci.io/repos/208583235/shield?branch=master)](https://styleci.io/repos/208583235)
[![Quality Score](https://img.shields.io/scrutinizer/g/spatie/flysystem-dropbox.svg?style=flat-square)](https://scrutinizer-ci.com/g/spatie/flysystem-dropbox)
[![Total Downloads](https://img.shields.io/packagist/dt/spatie/flysystem-dropbox.svg?style=flat-square)](https://packagist.org/packages/spatie/flysystem-dropbox)

Flysystem-raid provides RAID functionality across multiple flysystem filesystems. 

## Installation

Require the package using composer

```bash
composer require phpguus/flysystem-raid
```

## Usage

### RAID-0

RAID-0 is commonly known to provide striping of data.

We do not yet provide support for this kind of RAID configuration.

### RAID-1

RAID-1 is commonly known to provide mirroring of data. Because we use the
[Flysystem](https://github.com/thephpleague/flysystem) abstraction, we can
mirror data across any Flysystem, and make it redundantly available.

This is, in some respects, better than what a CDN can provide. A CDN normally
covers only one vendor, for example DigitalOcean's Spaces CDN. This package,
however, allows you to create mirrored data across many vendors, including a
local disk.

If you want to keep files on your web server as well as in the cloud, this RAID
configuration is all you need to do exactly that.

```php
use Aws\S3\S3Client;
use League\Flysystem\Adapter\Local;
use League\Flysystem\AwsS3v3\AwsS3Adapter;
use League\Flysystem\Config;
use League\Flysystem\Filesystem;
use PHPGuus\FlysystemRaid\RaidOneAdapter;

include __DIR__ . '/vendor/autoload.php';

$s3Client = new S3Client([
    'credentials' => [
        'key'    => 'your-key',
        'secret' => 'your-secret'
    ],
    'region' => 'your-region',
    'version' => 'latest|version',
]);
$this->adapter = new RaidOneAdapter([
    new Filesystem(new Local('/local_files')),
    new Filesystem(new AWSS3Adapter($s3Client, 'your-bucket-name')),
]);

$this->adapter->write('myFirstFile.txt',
    'The quick brown fox jumps over the lazy dog.', new Config());
```

The file `myFirstFile.txt` is now written in both `/local_files` and in the AWS
cloud.

#### Extending the mirror to a new location

Extending the mirroring of your RAID-1 configuration to a new location is very
simple:

```php
$this->adapter = new RaidOneAdapter([
    new Filesystem(new Local('/my_other_local_drive')),
    new Filesystem(new Local('/local_files')),
    new Filesystem(new AWSS3Adapter($s3Client, 'your-bucket-name')),
]);
$this->adapter->rebuildArray();
```

#### Replacing one location of the mirror

If you want to replace a location, because for example you change vendors from
AWS to Digital Ocean, you need to perform a two step approach:

Step 1 is to make sure that your mirror is fully redundant:

```php
$this->adapter = new RaidOneAdapter([
    new Filesystem(new Local('/local_files')),
    new Filesystem(new AWSS3Adapter($s3Client, 'your-bucket-name')),
]);
/* Ensure that both locations have identical data */
$this->adapter->rebuildArray();
```

Step 2 is to replace your AWS adapter configuration with a new one and to
rebuild the array:

```php
$s3Client = new S3Client([
    'credentials' => [
        'key'    => 'your-digital-ocean-key',
        'secret' => 'your-digital-ocean-secret'
    ],
    'region' => 'your-digital-ocean-region',
    'version' => 'latest|version',
]);
$this->adapter = new RaidOneAdapter([
    new Filesystem(new Local('/my_other_local_drive')),
    new Filesystem(new AWSS3Adapter($s3Client, 'your-digital-ocean-bucket-name')),
]);
$this->adapter->rebuildArray();
``` 

#### Knowing when to rebuild the array

```$this->adapter->getMetadata($filePath);``` returns an array that has a key
`mirrors`. The value of this key indicates the number of mirrors that exist for
this file. If this is less than the number of locations configured in your
adapter, you need to rebuild the array.

It would make sense to run a scheduled script that calls `rebuildArray()` at
least once a day. 

### RAID-5

RAID-5 is commonly known to sustain the failure of **one** of its configured
components through the use of a single parity disk that can be used to
calculate missing data. At least 3 disks are necessary to provide RAID-5
protection, two are used for data, and one is used for parity calculations.

In modern implementations, RAID-5 parity data is stored across all three disks,
as is the principle data.

We do not yet provide support for this kind of RAID configuration.

### RAID-6

RAID-6, aka Double Parity RAID, is commonly known to sustain the failure of
**two** of its configured components through the use of a two parity disks that
can be used to calculate missing data. At least 4 disks are necessary to
provide RAID-6 protection, two are used for data, and two are used for parity
calculations.

In modern implementations, RAID-6 parity data is stored across all 4 disks,
as is the principle data.

We do not yet provide support for this kind of RAID configuration.

### RAID-10

This is combination of Striping and Mirroring, in which your data is striped
across all disks, and each stripe (generally 8KiB) is mirrored.

We do not yet provide support for this kind of RAID configuration.

## Contributing
Pull requests are welcome. For major changes, please open an issue first to
discuss what you would like to change.

Please make sure to update tests as appropriate.

## License
[MIT](./LICENSE.md)