<?php
/**
 * Created by PhpStorm.
 * User: Valentin
 * Date: 01.02.2016
 * Time: 13:19
 */

namespace Vvval\Binary\Tests;

use Vvval\Binary\BinaryGenerator;

class BinaryTest extends \PHPUnit_Framework_TestCase
{
    private $writeFile = 'binary.write.txt';
    private $appendFile = 'binary.append.txt';

    /**
     * If data file exists.
     */
    public function testBinaryWrite()
    {
        $binary = new BinaryGenerator();

        $binary->start($this->writeFile, BinaryGenerator::WRITE);
        $binary->writeData('some data.1');
        $binary->writeData('some data.2');
        $binary->finish();

        $this->assertFileExists($this->writeFile);
    }
}