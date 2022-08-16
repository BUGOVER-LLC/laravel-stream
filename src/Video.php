<?php

declare(strict_types=1);

namespace Service\Stream;

class Video
{
    /**
     * Video path.
     *
     * @var string
     */
    private string $path;
    /**
     * Video MIME type.
     *
     * @var string
     */
    private string $mime;
    /**
     * Video size.
     *
     * @var int|float
     */
    private int|float $size;
    /**
     * Video progress.
     *
     * @var int
     */
    private int $progress;

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;

        $this->setMime(mime_content_type($path));

        $this->setSize(filesize($path));
    }

    /**
     * @param string $mime
     */
    private function setMime(string $mime): void
    {
        $this->mime = $mime;
    }

    /**
     * @param int $size
     */
    private function setSize(int $size): void
    {
        $this->size = $size;
    }

    /**
     * @return string
     */
    public function getMime(): string
    {
        return $this->mime;
    }

    /**
     * @return int
     */
    public function getSize(): int
    {
        return $this->size;
    }

    /**
     * @return int
     */
    public function getProgress(): int
    {
        return $this->progress;
    }

    /**
     * @param int $progress
     */
    public function setProgress(int $progress): void
    {
        $this->progress = $progress;
    }
}
