<?php

declare(strict_types=1);

namespace Service\Stream;

use Exception;
use JetBrains\PhpStorm\NoReturn;
use RuntimeException;
use Service\Stream\Events\VideoStreamEnded;
use Service\Stream\Events\VideoStreamStarted;

class VideoStreamer
{
    /**
     * @var
     */
    private $stream;
    /**
     * @var string
     */
    private string $path;
    /**
     * @var int
     */
    private int $buffer = 102400;
    /**
     * @var int
     */
    private int $start = -1;
    /**
     * @var int
     */
    private int $end = -1;
    /**
     * @var int
     */
    private int $size = 0;
    /**
     * @var string|bool
     */
    private string|bool $mime;
    /**
     * @var Video|null
     */
    private ?Video $video = null;

    /**
     * @param string $file_path
     * @param $stream
     */
    public function __construct(string $file_path, $stream)
    {
        $this->path = $file_path;
        $this->stream = $stream;
        $this->mime = mime_content_type($file_path);

        $this->video = new Video();
        $this->video->setPath($this->path);
    }

    /**
     * @throws Exception
     */
    public static function streamFile($path): void
    {
        $stream = fopen($path, 'rb');

        if (!$stream) {
            throw new RuntimeException("File not found in: $path", 6542);
        }

        (new static($path, $stream))->start();
    }

    /**
     * Start streaming video content.
     */
    #[NoReturn] private function start(): void
    {
        $this->setHeader();
        $this->stream();
        $this->end();
    }

    /**
     * Set proper header to serve the video content.
     */
    private function setHeader(): void
    {
        ob_get_clean();
        header("Content-Type: $this->mime");
        header('Cache-Control: max-age=2592000, public');
        header('Expires: ' . gmdate('D, d M Y H:i:s', time() + 2592000) . ' GMT');
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s', @filemtime($this->path)) . ' GMT');
        $this->start = 0;
        $this->size = filesize($this->path);
        $this->end = $this->size - 1;
        header("Accept-Ranges: 0-$this->end");

        if (!isset($_SERVER['HTTP_RANGE'])) {
            header("Content-Length: $this->size");

            return;
        }

        $c_end = $this->end;
        [
            ,
            $range,
        ] = explode('=', $_SERVER['HTTP_RANGE'], 2);

        if (str_contains($range, ',')) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $this->start-$this->end/$this->size");
            exit;
        }

        if ('-' === $range) {
            $c_start = $this->size - substr($range, 1);
        } else {
            $range = explode('-', $range);
            $c_start = $range[0];

            $c_end = (isset($range[1]) && is_numeric($range[1])) ? $range[1] : $c_end;
        }

        $c_end = ($c_end > $this->end) ? $this->end : $c_end;

        if ($c_start > $c_end || $c_start > $this->size - 1 || $c_end >= $this->size) {
            header('HTTP/1.1 416 Requested Range Not Satisfiable');
            header("Content-Range: bytes $this->start-$this->end/$this->size");
            exit;
        }

        $this->start = $c_start;
        $this->end = $c_end;
        $length = $this->end - $this->start + 1;
        fseek($this->stream, $this->start);
        header('HTTP/1.1 206 Partial Content');
        header("Content-Length: $length");
        header("Content-Range: bytes $this->start-$this->end/$this->size");
    }

    /**
     * perform the streaming of calculated range.
     */
    private function stream(): void
    {
        $this->video->setProgress($this->start);
        VideoStreamStarted::dispatch($this->video);

        $i = $this->start;
        set_time_limit(0);
        while (!feof($this->stream) && $i <= $this->end) {
            $this->video->setProgress($i);

            $bytes_ro_read = $this->buffer;
            if (($i + $bytes_ro_read) > $this->end) {
                $bytes_ro_read = $this->end - $i + 1;
            }
            $data = fread($this->stream, $bytes_ro_read);
            echo $data;
            flush();
            $i += $bytes_ro_read;
        }
    }

    /**
     * close curretly opened stream.
     */
    #[NoReturn] private function end(): void
    {
        fclose($this->stream);
        VideoStreamEnded::dispatch($this->video);
        exit;
    }
}
