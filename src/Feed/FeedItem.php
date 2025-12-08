<?php

namespace App\Feed;

use Symfony\Component\Serializer\Annotation\Groups;

class FeedItem
{
    #[Groups(['feed:read'])]
    public string $type;  // rating | review | collection
    #[Groups(['feed:read'])]
    public mixed $data;   // Raw entity
    #[Groups(['feed:read'])]
    public \DateTimeInterface $createdAt;
}
