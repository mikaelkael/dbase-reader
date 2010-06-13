<?php

namespace Mkk\Tests\Db;

use Mkk\Db;

require_once 'Mkk/Db/DbaseReader.php';

class DbaseReaderTest extends \PHPUnit_Framework_TestCase
{

    public function testConstructor()
    {
        $dbfFile = new Db\DbaseReader(__DIR__ . '/../_files/zftest.dbf');
        $this->assertEquals(200, $dbfFile->getRecordCount());
    }

    /**
     * @expectedException Exception
     */
    public function testConstructorWithUnknowFile()
    {
        $dbfFile = new Db\DbaseReader(__DIR__ . '/../_files/zftest-nofile.dbf');
    }

    public function testReadFieldsHeaders()
    {
        $dbfFile = new Db\DbaseReader(__DIR__ . '/../_files/zftest.dbf');
        $knownFields = array (0 => array('fieldName' => 'ID',
                                         'fieldType' => 'N',
                                         'offset' => 0,
                                         'fieldLen' => 11,
                                         'fieldDec' => 0),
                              1 => array('fieldName' => 'NAME',
                                         'fieldType' => 'C',
                                         'offset' => 0,
                                         'fieldLen' => 11,
                                         'fieldDec' => 0),
                              2 => array ('fieldName' => 'DATE',
                                          'fieldType' => 'D',
                                          'offset' => 0,
                                          'fieldLen' => 8,
                                          'fieldDec' => 0));
        $this->assertEquals($knownFields, $dbfFile->getInfos());
    }

    /**
     * @expectedException Exception
     */
    public function testReadFieldsHeadersWithIncorrectDbaseFile()
    {
        $dbfFile = new Db\DbaseReader(__DIR__ . '/../_files/not-a-dbf-file.txt');
        $dbfFile->getInfos();
    }

    public function testReadCompleteFile()
    {
        $dbfFile = new Db\DbaseReader(__DIR__ . '/../_files/zftest.dbf');
        $rows = $dbfFile->fetchAll();
        foreach ($rows as $k => $v) {
            $this->assertEquals(array('ID' => $k + 1,
                                      'NAME' => 'foo' . ($k+1),
                                      'DATE' => date('Ymd', mktime(0, 0, 0, 1, 1 + $k, 2010))),
                                $v);
        }
    }
}