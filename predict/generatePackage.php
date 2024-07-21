<?php

error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

require_once('PEAR/PackageFileManager2.php');

PEAR::setErrorHandling(PEAR_ERROR_DIE);

$packagexml = new PEAR_PackageFileManager2;

$packagexml->setOptions(array(
    'baseinstalldir'    => '/',
    'simpleoutput'      => true,
    'packagedirectory'  => './',
    'filelistgenerator' => 'file',
    'ignore'            => array('generatePackage.php', 'xhprof_lib/*'),
    'dir_roles' => array(
        'tests'     => 'test',
        'examples'  => 'doc'
    ),
    'exceptions' => array('README.md' => 'doc'),
));

$packagexml->setPackage('Predict');
$packagexml->setSummary('A partial port of the Gpredict program for satellite tracking');
$packagexml->setDescription(
    'Predict is a partial PHP port of the Gpredict (http://gpredict.oz9aec.net/) program that '
    . 'allows real-time tracking and orbit prediction of satellites from two line element sets.  '
    . 'It supports the SGP4 and SDP4 models for prediction.'
);

$packagexml->setChannel('shupp.github.com/pirum');
$packagexml->setAPIVersion('0.2.2');
$packagexml->setReleaseVersion('0.2.2');

$packagexml->setReleaseStability('alpha');

$packagexml->setAPIStability('alpha');

$packagexml->setNotes('
* Addec Predict_TLE::createChecksum()
* Updates to examples
');
$packagexml->setPackageType('php');
$packagexml->addRelease();

$packagexml->detectDependencies();

$packagexml->addMaintainer('lead',
                           'shupp',
                           'Bill Shupp',
                           'shupp@php.net');
$packagexml->setLicense('GPL v2.1',
                        'http://www.opensource.org/licenses/gpl-license.php');

$packagexml->setPhpDep('5.2.0');
$packagexml->setPearinstallerDep('1.4.0b1');
$packagexml->addExtensionDep('required', 'date');

$packagexml->generateContents();
$packagexml->writePackageFile();

?>
