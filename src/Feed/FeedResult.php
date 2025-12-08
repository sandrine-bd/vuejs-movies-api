<?php

namespace App\Feed;

use Symfony\Component\Serializer\Annotation\Groups;

class FeedResult
{
    /** @var FeedItem[] */
    #[Groups(['feed:read'])]
    public array $items = [];

    #[Groups(['feed:read'])]
    public ?string $nextCursor = null;
}
