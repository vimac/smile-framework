<?php
/**
 * Slim Framework (https://slimframework.com)
 *
 * @link      https://github.com/slimphp/Slim
 * @copyright Copyright (c) 2011-2016 Josh Lockhart
 * @license   https://github.com/slimphp/Slim/blob/3.x/LICENSE.md (MIT License)
 */
namespace Smile\Http;

use InvalidArgumentException;
use Psr\Http\Message\StreamInterface;
use RuntimeException;

/**
 * Represents a data stream as defined in PSR-7.
 *
 * 这个类用来表达 PSR-7 中定义的数据 stream
 *
 * @link https://github.com/php-fig/http-message/blob/master/src/StreamInterface.php
 */
class Stream implements StreamInterface
{
    /**
     * Bit mask to determine if the stream is a pipe
     *
     * This is octal as per header stat.h
     *
     * 来自 POSIX 的 stat.h 的常量 (八进制保存)
     * 用来表达某个数据 stream 是一个先进先出的管道
     */
    const FSTAT_MODE_S_IFIFO = 0010000;

    /**
     * Resource modes
     *
     * 资源读写模式
     *
     * @var  array
     * @link http://php.net/manual/function.fopen.php
     */
    protected static $modes = [
        'readable' => ['r', 'r+', 'w+', 'a+', 'x+', 'c+'],
        'writable' => ['r+', 'w', 'w+', 'a', 'a+', 'x', 'x+', 'c', 'c+'],
    ];

    /**
     * The underlying stream resource
     *
     * 优先的 stream 资源
     *
     * @var resource
     */
    protected $stream;

    /**
     * Stream metadata
     *
     *  stream 的元信息
     *
     * @var array
     */
    protected $meta;

    /**
     * Is this stream readable?
     *
     * 表达此 stream 是否可读
     *
     * @var bool
     */
    protected $readable;

    /**
     * Is this stream writable?
     *
     * 表达此 stream 是否可写
     *
     * @var bool
     */
    protected $writable;

    /**
     * Is this stream seekable?
     *
     * 表达此 stream 是否可移动读写指针
     *
     * @var bool
     */
    protected $seekable;

    /**
     * The size of the stream if known
     *
     * 已知的 stream 的数据大小
     *
     * @var null|int
     */
    protected $size;

    /**
     * Is this stream a pipe?
     *
     * 表达这个 stream 是否是一个管道
     *
     * @var bool
     */
    protected $isPipe;

    /**
     * Create a new Stream.
     *
     * 创建一个新的 stream
     *
     * @param  resource $stream A PHP resource handle. 一个 PHP 资源句柄
     *
     * @throws InvalidArgumentException If argument is not a resource.
     */
    public function __construct($stream)
    {
        $this->attach($stream);
    }

    /**
     * Get stream metadata as an associative array or retrieve a specific key.
     *
     * The keys returned are identical to the keys returned from PHP's
     * stream_get_meta_data() function.
     *
     * @link http://php.net/manual/en/function.stream-get-meta-data.php
     *
     * 获取 stream 的元信息
     *
     * @param string $key Specific metadata to retrieve.
     *
     * @return array|mixed|null Returns an associative array if no key is
     *     provided. Returns a specific key value if a key is provided and the
     *     value is found, or null if the key is not found.
     */
    public function getMetadata($key = null)
    {
        $this->meta = stream_get_meta_data($this->stream);
        if (is_null($key) === true) {
            return $this->meta;
        }

        return isset($this->meta[$key]) ? $this->meta[$key] : null;
    }

    /**
     * Is a resource attached to this stream?
     *
     * 获取这个资源是否和一个 stream 绑定
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @return bool
     */
    protected function isAttached()
    {
        return is_resource($this->stream);
    }

    /**
     * Attach new resource to this object.
     *
     * Note: This method is not part of the PSR-7 standard.
     *
     * @param resource $newStream A PHP resource handle.
     *
     * @throws InvalidArgumentException If argument is not a valid PHP resource.
     */
    protected function attach($newStream)
    {
        if (is_resource($newStream) === false) {
            throw new InvalidArgumentException(__METHOD__ . ' argument must be a valid PHP resource');
        }

        if ($this->isAttached() === true) {
            $this->detach();
        }

        $this->stream = $newStream;
    }

    /**
     * Separates any underlying resources from the stream.
     *
     * After the stream has been detached, the stream is in an unusable state.
     *
     * @return resource|null Underlying PHP stream, if any
     */
    public function detach()
    {
        $oldResource = $this->stream;
        $this->stream = null;
        $this->meta = null;
        $this->readable = null;
        $this->writable = null;
        $this->seekable = null;
        $this->size = null;
        $this->isPipe = null;

        return $oldResource;
    }

    /**
     * Reads all data from the stream into a string, from the beginning to end.
     *
     * This method MUST attempt to seek to the beginning of the stream before
     * reading data and read the stream until the end is reached.
     *
     * Warning: This could attempt to load a large amount of data into memory.
     *
     * This method MUST NOT raise an exception in order to conform with PHP's
     * string casting operations.
     *
     * 从头到尾将整个 stream 读取到一个字符串中
     *
     * 这个方法会尝试着将指针定位到 stream 头部读取到尾部
     * 有可能会导致巨大的内存占用
     * 根据 php 的魔术方法 __tostring 的官方文档, 这个方法不能在运行中抛出任何异常 (否则将导致 fatal error)
     *
     * @see http://php.net/manual/en/language.oop5.magic.php#object.tostring
     * @return string
     */
    public function __toString()
    {
        if (!$this->isAttached()) {
            return '';
        }

        try {
            $this->rewind();
            return $this->getContents();
        } catch (RuntimeException $e) {
            return '';
        }
    }

    /**
     * Closes the stream and any underlying resources.
     *
     * 关闭 stream
     */
    public function close()
    {
        if ($this->isAttached() === true) {
            if ($this->isPipe()) {
                pclose($this->stream);
            } else {
                fclose($this->stream);
            }
        }

        $this->detach();
    }

    /**
     * Get the size of the stream if known.
     *
     * 如果可以, 获得 stream 的大小, 用字节表达
     *
     * @return int|null Returns the size in bytes if known, or null if unknown.
     */
    public function getSize()
    {
        if (!$this->size && $this->isAttached() === true) {
            $stats = fstat($this->stream);
            $this->size = isset($stats['size']) && !$this->isPipe() ? $stats['size'] : null;
        }

        return $this->size;
    }

    /**
     * Returns the current position of the file read/write pointer
     *
     * 获取当前的指针
     *
     * @return int Position of the file pointer
     *
     * @throws RuntimeException on error.
     */
    public function tell()
    {
        if (!$this->isAttached() || ($position = ftell($this->stream)) === false || $this->isPipe()) {
            throw new RuntimeException('Could not get the position of the pointer in stream');
        }

        return $position;
    }

    /**
     * Returns true if the stream is at the end of the stream.
     *
     * 获取 stream 是否已经读到 eof
     *
     * @return bool
     */
    public function eof()
    {
        return $this->isAttached() ? feof($this->stream) : true;
    }

    /**
     * Returns whether or not the stream is readable.
     *
     * 获取 stream 是否可读
     *
     * @return bool
     */
    public function isReadable()
    {
        if ($this->readable === null) {
            if ($this->isPipe()) {
                $this->readable = true;
            } else {
                $this->readable = false;
                if ($this->isAttached()) {
                    $meta = $this->getMetadata();
                    foreach (self::$modes['readable'] as $mode) {
                        if (strpos($meta['mode'], $mode) === 0) {
                            $this->readable = true;
                            break;
                        }
                    }
                }
            }
        }

        return $this->readable;
    }

    /**
     * Returns whether or not the stream is writable.
     *
     * 获取 stream 是否可写
     *
     * @return bool
     */
    public function isWritable()
    {
        if ($this->writable === null) {
            $this->writable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                foreach (self::$modes['writable'] as $mode) {
                    if (strpos($meta['mode'], $mode) === 0) {
                        $this->writable = true;
                        break;
                    }
                }
            }
        }

        return $this->writable;
    }

    /**
     * Returns whether or not the stream is seekable.
     *
     * 获取 stream 是否可移动指针
     *
     * @return bool
     */
    public function isSeekable()
    {
        if ($this->seekable === null) {
            $this->seekable = false;
            if ($this->isAttached()) {
                $meta = $this->getMetadata();
                $this->seekable = !$this->isPipe() && $meta['seekable'];
            }
        }

        return $this->seekable;
    }

    /**
     * Seek to a position in the stream.
     *
     * 直接在 stream 中移动读写指针
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @param int $offset Stream offset
     * @param int $whence Specifies how the cursor position will be calculated
     *     based on the seek offset. Valid values are identical to the built-in
     *     PHP $whence values for `fseek()`.  SEEK_SET: Set position equal to
     *     offset bytes SEEK_CUR: Set position to current location plus offset
     *     SEEK_END: Set position to end-of-stream plus offset.
     *
     * @throws RuntimeException on failure.
     */
    public function seek($offset, $whence = SEEK_SET)
    {
        // Note that fseek returns 0 on success!
        if (!$this->isSeekable() || fseek($this->stream, $offset, $whence) === -1) {
            throw new RuntimeException('Could not seek in stream');
        }
    }

    /**
     * Seek to the beginning of the stream.
     *
     * If the stream is not seekable, this method will raise an exception;
     * otherwise, it will perform a seek(0).
     *
     * 将 stream 指针移动到头部, 如果 stream 不可以移动指针, 则会抛出异常
     *
     * @see seek()
     *
     * @link http://www.php.net/manual/en/function.fseek.php
     *
     * @throws RuntimeException on failure.
     */
    public function rewind()
    {
        if (!$this->isSeekable() || rewind($this->stream) === false) {
            throw new RuntimeException('Could not rewind stream');
        }
    }

    /**
     * Read data from the stream.
     *
     * 从 stream 中读取数据
     *
     * @param int $length Read up to $length bytes from the object and return
     *     them. Fewer than $length bytes may be returned if underlying stream
     *     call returns fewer bytes.
     *
     * @return string Returns the data read from the stream, or an empty string
     *     if no bytes are available.
     *
     * @throws RuntimeException if an error occurs.
     */
    public function read($length)
    {
        if (!$this->isReadable() || ($data = fread($this->stream, $length)) === false) {
            throw new RuntimeException('Could not read from stream');
        }

        return $data;
    }

    /**
     * Write data to the stream.
     *
     * 写入数据到 stream
     *
     * @param string $string The string that is to be written.
     *
     * @return int Returns the number of bytes written to the stream.
     *
     * @throws RuntimeException on failure.
     */
    public function write($string)
    {
        if (!$this->isWritable() || ($written = fwrite($this->stream, $string)) === false) {
            throw new RuntimeException('Could not write to stream');
        }

        // reset size so that it will be recalculated on next call to getSize()
        $this->size = null;

        return $written;
    }

    /**
     * Returns the remaining contents in a string
     *
     * 返回 stream 未读部分内容的字符串
     *
     * @return string
     *
     * @throws RuntimeException if unable to read or an error occurs while
     *     reading.
     */
    public function getContents()
    {
        if (!$this->isReadable() || ($contents = stream_get_contents($this->stream)) === false) {
            throw new RuntimeException('Could not get contents of stream');
        }

        return $contents;
    }

    /**
     * Returns whether or not the stream is a pipe.
     *
     * 获取某个 stream 是否是一个管道
     *
     * @return bool
     */
    public function isPipe()
    {
        if ($this->isPipe === null) {
            $this->isPipe = false;
            if ($this->isAttached()) {
                $mode = fstat($this->stream)['mode'];
                $this->isPipe = ($mode & self::FSTAT_MODE_S_IFIFO) !== 0;
            }
        }

        return $this->isPipe;
    }
}
