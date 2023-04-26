<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\GraphQLResolvers;

use Neos\Flow\Annotations as Flow;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use Neos\Media\Domain\Model\AssetSource\SupportsCollectionsInterface;
use Neos\Media\Domain\Model\AssetSource\SupportsTaggingInterface;
use t3n\GraphQL\ResolverInterface;

#[Flow\Scope('singleton')]
final class AssetSourceResolver implements ResolverInterface
{
    public function id(AssetSourceInterface $assetSource): string
    {
        return $assetSource->getIdentifier();
    }

    public function supportsTagging(AssetSourceInterface $assetSource): bool
    {
        return $assetSource->getAssetProxyRepository() instanceof SupportsTaggingInterface;
    }

    public function supportsCollections(AssetSourceInterface $assetSource): bool
    {
        return $assetSource->getAssetProxyRepository() instanceof SupportsCollectionsInterface;
    }
}
