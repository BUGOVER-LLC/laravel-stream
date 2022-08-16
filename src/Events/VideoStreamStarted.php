<?php

declare(strict_types=1);

namespace Service\Stream\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use JetBrains\PhpStorm\ArrayShape;
use JetBrains\PhpStorm\Pure;
use Service\Stream\Video;

class VideoStreamStarted implements ShouldBroadcastNow
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    /**
     * Create a new event instance.
     *
     * @param Video $video
     */
    public function __construct(private Video $video)
    {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|PrivateChannel|array
     */
    public function broadcastOn(): Channel|PrivateChannel|array
    {
        return new PrivateChannel('channel-name');
    }

    /**
     * @return Video[]
     */
    #[Pure] #[ArrayShape([
        'stream' => Video::class,
    ])] public function broadcastAs(): array
    {
        return ['stream' => $this->getVideo()];
    }

    /**
     * @return Video
     */
    public function getVideo(): Video
    {
        return $this->video;
    }
}
