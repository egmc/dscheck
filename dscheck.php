<?php
/**
 * DsCheck
 *
 * easy chekcer for URL status
 */
require  __DIR__  . "/vendor/autoload.php";

use DsCheck\DsCheck;
use Symfony\Component\Yaml\Parser;

$parser = new Parser();

$dschecker = new DsCheck($parser->parse(file_get_contents(__DIR__ . "/dscheck.yaml")));
$dschecker->run();

