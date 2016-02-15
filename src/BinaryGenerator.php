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
     * todo read about fopen
     */
    const READ = 'rb';
    const WRITE = 'wb';
    const APPEND = 'ab';

    private $filename = null;
    private $compression = null;
    private $handler = null;
    private $mode = self::READ;

    /**
     * @param $compression
     * @return $this
     * @throws HandlerException
     */
    public function setCompression($compression)
    {
        if (!empty($this->handler))
        {
            throw new HandlerException("Binary generator has already started with \"$this->filename\" file.");
        }

        $this->compression = $compression;

        return $this;
    }

    /**
     * @param $filename
     * @param $mode
     * @throws HandlerException
     */
    public function start($filename, $mode)
    {
        $this->mode = $mode;
        $this->filename = $filename;

        if (!empty($this->handler))
        {
            throw new HandlerException("Binary generator has already started with \"$this->filename\" file.");
        }

        if (!empty($this->compression))
        {
            $this->handler = gzopen($this->filename, $this->mode);
        }
        else
        {
            $this->handler = fopen($this->filename, $this->mode);
        }

        if (empty($this->handler))
        {
            throw new HandlerException("Error during opening \"$this->filename\" file.");
        }
    }

    /**
     *
     */
    public function finish()
    {
        if (!empty($this->handler))
        {
            if (!empty($this->compression))
            {
                gzclose($this->handler);
            }
            else
            {
                fclose($this->handler);
            }

            $this->handler = null;
        }
    }

    /**
     * @param $data
     * @return int
     * @throws HandlerException
     * @throws WriteDataException
     */
    public function writeData($data)
    {
        if (!$this->isWriteMode())
        {
            throw new WriteDataException("Unable to write data into \"$this->filename\" file, read mode is set.");
        }

        if (empty($this->handler))
        {
            throw new HandlerException("Unable to write data into \"$this->filename\" file, no resource is available.");
        }

        $packedLength = pack('L', mb_strlen($data));

        if (!empty($this->compression))
        {
            return gzwrite($this->handler, $packedLength . $data);
        }

        return fwrite($this->handler, $packedLength . $data);
    }

    /**
     * @return null|string
     * @throws HandlerException
     * @throws ReadDataException
     */
    public function readData()
    {
        if (!$this->isReadMode())
        {
            throw new ReadDataException("Unable to read data from \"$this->filename\" file, write mode is set.");
        }

        if (empty($this->handler))
        {
            throw new HandlerException("Unable to read data from \"$this->filename\" file, no resource is available.");
        }

        if (!empty($this->compression))
        {
            $length = gzread($this->handler, 4);
        }
        else
        {
            $length = fread($this->handler, 4);
        }

        if (empty($length))
        {
            return null;
        }

        if (mb_strlen($length) != 4)
        {
            throw new ReadDataException("Unable to read data from \"$this->filename\" file, data is corrupted.");
        }

        $length = unpack('L', $length);

        if (!empty($this->compression))
        {
            return gzread($this->handler, $length[1]);
        }
        else
        {
            return fread($this->handler, $length[1]);
        }
    }

    /**
     * @return bool
     */
    private function isReadMode()
    {
        return $this->mode === self::READ;
    }

    /**
     * @return bool
     */
    private function isWriteMode()
    {
        return in_array($this->mode, [self::WRITE, self::APPEND]);
    }

    /**
     * Check if file was closed.
     *
     */
    public function __destruct()
    {
        if (!empty($this->handler))
        {
            throw new HandlerException("Binary generator hasn't finished \"$this->filename\" file yet.");
        }
    }
}