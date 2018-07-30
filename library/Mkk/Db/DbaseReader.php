<?php
namespace Mkk\Db;

class DbaseReader
{

    /**
     * Resource associated to the dbf file
     * @var resource
     */
    protected $_filePointer = null;

    /**
     * Path to the dbf file
     * @var string
     */
    protected $_fileName = null;

    /**
     * Headers of the dbf file
     * @var array
     */
    protected $_headers = null;

    /**
     * Fields headers
     * @var array
     */
    protected $_infos = null;

    /**
     * Unpack string build with fields headers
     * @var string
     */
    protected $_unpackString = '';

    /**
     * All data of the dbf file
     * @var array
     */
    protected $_data = null;
    
    /**
     * enable fetchng deleted rows too
     * @var array
     */
    protected $_fetchDeleted = false;

    /**
     * DbaseReader constructor: open the file and retrieve the headers
     * @param string $fileName Dbf filename
     */
    public function  __construct($fileName, $fetchDeleted = false)
    {
        $this->_fileName = $fileName;
        if (!file_exists($fileName) || !\is_readable($fileName)) {
            throw new \Exception('Dbf file does not exist or is not readable');
        }
        $this->_openFile();
        $buffer = fread($this->_filePointer, 32);
        $this->_headers = unpack("VrecordCount/vfirstRecord/vrecordLength",
                                 substr($buffer, 4, 8));
        $this->_closeFile();
        
        $this->_fetchDeleted = $fetchDeleted;
    }

    /**
     * Open associated dbf file
     */
    private function _openFile()
    {
        if (!$this->_filePointer) {
            $this->_filePointer = fopen($this->_fileName,'r');
        }
    }

    /**
     * Close associated dbf file
     */
    private function _closeFile()
    {
        if ($this->_filePointer) {
            fclose($this->_filePointer);
            $this->_filePointer = null;
        }
    }

    /**
     * Close associated dbf file when destructing object
     */
    public function  __destruct()
    {
        $this->_closeFile();
    }

    /**
     * Retrieve file metadata
     * @return array
     */
    public function getInfos()
    {
        if (!$this->_infos) {
            $this->_openFile();
            $continue = true;
            $this->_unpackString = '';
            $fields = array();
            fseek($this->_filePointer, 32);
            // Read fields headers
            while ($continue && !feof($this->_filePointer)) {
                $buffer = fread($this->_filePointer, 32);
                if (substr($buffer, 0, 1) == chr(13)) {
                    $continue = false;
                } else {
                    $field = unpack("a11fieldName/A1fieldType/Voffset/CfieldLen/CfieldDec",
                                    substr($buffer, 0, 18));
                    // Check fields headers
                    if (!in_array($field['fieldType'], array('M', 'D', 'N', 'C', 'L', 'F'))) {
                        throw new \Exception("Field type of field '{$field['fieldName']}' is not correct");
                    }
                    $this->_unpackString .= 'A' . $field['fieldLen'] . $field['fieldName'] . '/';
                    array_push($fields, $field);
                }
            }
            $this->_infos = $fields;
            $this->_closeFile();
        }
        return $this->_infos;
    }

    /**
     * Return all records as an array
     * @return array
     */
    public function fetchAll()
    {
        if (!$this->_data) {
            $this->getInfos();
            $this->_openFile();
            fseek($this->_filePointer, $this->_headers['firstRecord']);
            $records = array();
            for ($i = 1; $i <= $this->_headers['recordCount']; $i++) {
                //First byte shows if the record is deleted
                $deleted = fread($this->_filePointer, 1);
                $buffer = fread($this->_filePointer, ($this->_headers['recordLength'] - 1) );
                
                $record = unpack($this->_unpackString, $buffer);
                
                //Deleted records marked with *
                if( $this->_fetchDeleted || $deleted!='*' )
                {
                    array_push($records, $record);
                }
            }
            $this->_data = $records;
            $this->_closeFile();
        }
        return $this->_data;
    }

    /**
     * Number of record
     * @return integer
     */
    public function getRecordCount()
    {
        return $this->_headers['recordCount'];
    }
}