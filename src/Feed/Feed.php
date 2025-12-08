<?php

namespace App\Feed;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(
    shortName: 'Feed',
    operations: [
        new GetCollection(
            uriTemplate: '/feed',
            paginationEnabled: false,
            security: "is_granted('ROLE_USER')",
            output: FeedResult::class,
            name: 'user_feed',
            provider: FeedStateProvider::class
        )
    ],
    normalizationContext: ['groups' => ['feed:read']]
)]
class Feed
{
}
