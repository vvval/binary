<?php
/**
 * Lightweight tool to read/write tons of data directly in file, skipping memory usage
 *
 * @author  Valentin V
 * @license http://www.opensource.org/licenses/MIT
 * @link    https://github.com/vvval/binary
 * @version 0.1.0
 */
namespace Vvval\Binary;

use Vvval\Binary\Exceptions\HandlerException;
use Vvval\Binary\Exceptions\ReadDataException;
use Vvval\Binary\Exceptions\WriteDataException;

class BinaryGenerator
{
    /**
     * Open file flags.
     */
    const READ   = 'rb';
    const WRITE  = 'wb';
    const APPEND = 'ab';

    private $filename = null;
    private $compression = null;
    private $handler = null;
    private $mode = self::READ;

    /**
     * Start process.
     *
     * @param string $filename
     * @param string $mode
     * @param bool   $compression
     * @throws HandlerException
     */
    public function start($filename, $mode, $compression = false)
    {
        if (!empty($this->handler)) {
            throw new HandlerException("Binary generator has already been started, please finish it before next usage.");
        }

        $this->mode = $mode;
        $this->filename = $filename;
        $this->compression = $compression;

        $this->handler = $this->open();

        if (empty($this->handler)) {
            throw new HandlerException("Error during opening \"$this->filename\" file.");
        }
    }

    /**
     * Finish process.
     */
    public function finish()
    {
        if (!empty($this->handler)) {
            $this->close();
            $this->handler = null;
        }
    }

    /**
     * Write part of data into file.
     *
     * @param string $data
     * @return int
     * @throws HandlerException
     * @throws WriteDataException
     */
    public function writeData($data)
    {
        if (!$this->isWriteMode()) {
            throw new WriteDataException("Unable to write data into \"$this->filename\" file, read mode is set.");
        }

        if (empty($this->handler)) {
            throw new HandlerException("Unable to write data into \"$this->filename\" file, no resource is available.");
        }

        $packedLength = pack('L', mb_strlen($data));

        return $this->write($packedLength, $data);
    }

    /**
     * Write part of data.
     *
     * @return null|string
     * @throws HandlerException
     * @throws ReadDataException
     */
    public function readData()
    {
        if (!$this->isReadMode()) {
            throw new ReadDataException("Unable to read data from \"$this->filename\" file, write mode is set.");
        }

        if (empty($this->handler)) {
            throw new HandlerException("Unable to read data from \"$this->filename\" file, no resource is available.");
        }

        $length = $this->read(4);

        if (empty($length)) {
            return null;
        }

        if (mb_strlen($length) != 4) {
            throw new ReadDataException("Unable to read data from \"$this->filename\" file, data is corrupted.");
        }

        $length = unpack('L', $length);

        return $this->read((int)$length);
    }

    /**
     * Open file and get handler.
     *
     * @return resource
     */
    private function open()
    {
        if (!empty($this->compression)) {
            return gzopen($this->filename, $this->mode);
        } else {
            return fopen($this->filename, $this->mode);
        }
    }

    /**
     * Close handler file.
     */
    private function close()
    {
        if (!empty($this->compression)) {
            gzclose($this->handler);
        } else {
            fclose($this->handler);
        }
    }

    /**
     * Read piece of data with the defined length.
     *
     * @param int $length
     * @return string
     */
    private function read($length)
    {
        if (!empty($this->compression)) {
            return gzread($this->handler, $length);
        } else {
            return fread($this->handler, $length);
        }
    }

    /**
     * Write data into file.
     *
     * @param int    $length
     * @param string $data
     * @return int
     */
    private function write($length, $data)
    {
        if (!empty($this->compression)) {
            return gzwrite($this->handler, $length . $data);
        } else {
            return fwrite($this->handler, $length . $data);
        }
    }

    /**
     * If current file in read mode.
     *
     * @return bool
     */
    private function isReadMode()
    {
        return $this->mode === self::READ;
    }

    /**
     * If current file in write mode.
     *
     * @return bool
     */
    private function isWriteMode()
    {
        return in_array($this->mode, [self::WRITE, self::APPEND]);
    }

    /**
     * Check if file was closed.
     */
    public function __destruct()
    {
        if (!empty($this->handler)) {
            throw new HandlerException("Binary generator hasn't finished \"$this->filename\" file yet.");
        }
    }
}