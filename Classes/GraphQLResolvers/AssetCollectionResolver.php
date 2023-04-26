<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\GraphQLResolvers;

use Neos\Flow\Annotations as Flow;
use t3n\GraphQL\ResolverInterface;
use Wwwision\DAM\DAM;
use Wwwision\DAM\Model\Filter\AssetFilter;
use Wwwision\DAM\Model\Folder;
use Wwwision\DAM\Model\Folders;

#[Flow\Scope('singleton')]
final class AssetCollectionResolver implements ResolverInterface
{
    public function __construct(
        private readonly DAM $dam,
    ) {}

    public function title(Folder $folder): string
    {
        return $folder->label->value;
    }

    public function children(Folder $folder): Folders
    {
        return $this->dam->findChildFolders($folder->id);
    }

    public function parent(Folder $folder): ?Folder
    {
        return $this->dam->findParentFolder($folder->id);
    }

    public function tags(Folder $folder): array
    {
        return [];
    }

    public function assetCount(Folder $folder): int
    {
        return $this->dam->countAssets(AssetFilter::create()->with(folderId: $folder->id));
    }
}
