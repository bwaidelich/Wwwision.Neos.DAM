<?php
declare(strict_types=1);

namespace Wwwision\Neos\DAM\GraphQLResolvers;

use Flowpack\Media\Ui\GraphQL\Context\AssetSourceContext;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Media\Domain\Model\AssetSource\AssetSourceInterface;
use t3n\GraphQL\ResolverInterface;
use Wwwision\DAM\DAM;
use Wwwision\DAM\Model\Asset;
use Wwwision\DAM\Model\Tags;

#[Flow\Scope('singleton')]
final class AssetResolver implements ResolverInterface
{
    public function __construct(
        private readonly DAM $dam,
        private readonly ResourceManager $resourceManager
    ) {}


    public function id(Asset $asset): ?string
    {
        return $asset->id->value;
    }

    public function assetSource($_, array $variables, AssetSourceContext $assetSourceContext): AssetSourceInterface
    {
        return $assetSourceContext->getAssetSource('neos');
    }

    public function label(Asset $asset): ?string
    {
        return $asset->label->value;
    }

    public function isInUse(Asset $asset): ?bool
    {
        // TODO implement
        return false;
    }

    public function caption(Asset $asset): ?string
    {
        return $asset->label->value;
    }

    public function imported(Asset $asset): bool
    {
        // TODO implement ?
        return false;
    }

    public function file(Asset $asset): array
    {
        return [
            'extension' => '.jpg',
            'mediaType' => 'image/jpeg',
            'typeIcon' => [
                'width' => 16,
                'height' => 16,
                'url' => $this->thumbnailUrl($asset),
                'alt' => 'icon alt text',
            ],
            'size' => 12345,
            'url' => $this->thumbnailUrl($asset),
        ];
    }

    public function iptcProperty(Asset $asset, array $variables): ?string
    {
        // TODO implement
        return null;
    }

    public function iptcProperties(Asset $asset): array
    {
        // TODO implement
        return [];
    }

    public function copyrightNotice(Asset $asset, array $variables): ?string
    {
        // TODO implement
        return null;
    }

    public function lastModified(Asset $asset, array $variables): ?string
    {
        // TODO implement
        return null;
    }

    public function tags(Asset $asset, array $variables): Tags
    {
        return $this->dam->findTagsByAssetId($asset->id);
    }

    public function collections(Asset $asset, array $variables): array
    {
        $parentFolder = $this->dam->findParentFolder($asset->id);
        return $parentFolder !== null ? [$parentFolder] : [];
    }

    public function width(Asset $asset): int
    {
        return $asset?->dimensions->width ?? 0;
    }

    public function height(Asset $asset): int
    {
        return $asset?->dimensions->height ?? 0;
    }

    public function thumbnailUrl(Asset $asset): string
    {
        // TODO implement thumbnail handling
        return $this->previewUrl($asset);
    }

    public function previewUrl(Asset $asset): string
    {
        // TODO implement image resizing
        $resource = $this->resourceManager->getResourceBySha1($asset->resourcePointer->value);
        if ($resource === null) {
            return '';
        }
        return $this->resourceManager->getPublicPersistentResourceUri($resource);
    }

    /**
     * @param Asset $asset
     * @param int $maximumWidth
     * @param int $maximumHeight
     * @param string $ratioMode
     * @param bool $allowUpScaling
     * @param bool $allowCropping
     * @return array
     * @throws \Exception
     */
    public function thumbnail(
        Asset $asset,
        int $maximumWidth,
        int $maximumHeight,
        string $ratioMode,
        bool $allowUpScaling,
        bool $allowCropping
    ): array {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682525148);
    }
}
