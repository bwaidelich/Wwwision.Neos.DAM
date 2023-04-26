<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\Factory;

use Neos\ContentRepository\Core\Factory\ContentRepositoryId;
use Neos\ContentRepository\Core\NodeType\NodeTypeManager;
use Neos\ContentRepositoryRegistry\Factory\NodeTypeManager\NodeTypeManagerFactoryInterface;
use Neos\Flow\ObjectManagement\ObjectManagerInterface;
use Wwwision\DAM\ContentRepository\AssetNodeTypes;

final class NodeTypeManagerFactory implements NodeTypeManagerFactoryInterface
{
    public function __construct(
        private readonly ObjectManagerInterface $objectManager,
    ) {
    }

    public function build(ContentRepositoryId $contentRepositoryId, array $options): NodeTypeManager
    {
        return new NodeTypeManager(
            AssetNodeTypes::getConfiguration(...),
            $this->objectManager,
            null
        );
    }
}
