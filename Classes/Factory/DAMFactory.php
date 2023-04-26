<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\Factory;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepositoryRegistry\ContentRepositoryRegistry;
use Neos\Flow\Annotations as Flow;
use Wwwision\DAM\DAM;

#[Flow\Scope("singleton")]
final class DAMFactory
{
    public function __construct(
        private readonly ContentRepositoryRegistry $contentRepositoryRegistry,
    )
    {}

    public function create(): DAM
    {
        $contentRepository = $this->contentRepositoryRegistry->get(ContentRepositoryId::fromString('wwwision_dam'));
        return new DAM($contentRepository);
    }
}
