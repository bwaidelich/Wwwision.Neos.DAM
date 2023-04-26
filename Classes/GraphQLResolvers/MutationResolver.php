<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\GraphQLResolvers;

use Flowpack\Media\Ui\Exception;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\ResourceManagement\Exception as ResourceException;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Http\Factories\FlowUploadedFile;
use Neos\Media\Domain\Service\ImageService;
use t3n\GraphQL\ResolverInterface;
use Wwwision\DAM\Command\AddAsset;
use Wwwision\DAM\Command\AddFolder;
use Wwwision\DAM\Command\AddTag;
use Wwwision\DAM\Command\AddTagToAsset;
use Wwwision\DAM\Command\DeleteAsset;
use Wwwision\DAM\Command\DeleteFolder;
use Wwwision\DAM\Command\DeleteTag;
use Wwwision\DAM\Command\MoveAsset;
use Wwwision\DAM\Command\MoveFolder;
use Wwwision\DAM\Command\RenameFolder;
use Wwwision\DAM\Command\RenameTag;
use Wwwision\DAM\Command\SetAssetTags;
use Wwwision\DAM\DAM;
use Wwwision\DAM\Model\Asset;
use Wwwision\DAM\Model\AssetCaption;
use Wwwision\DAM\Model\AssetId;
use Wwwision\DAM\Model\AssetLabel;
use Wwwision\DAM\Model\AssetType;
use Wwwision\DAM\Model\Dimensions;
use Wwwision\DAM\Model\Filename;
use Wwwision\DAM\Model\Folder;
use Wwwision\DAM\Model\FolderId;
use Wwwision\DAM\Model\FolderLabel;
use Wwwision\DAM\Model\MediaType;
use Wwwision\DAM\Model\Metadata;
use Wwwision\DAM\Model\ResourcePointer;
use Wwwision\DAM\Model\Tag;
use Wwwision\DAM\Model\TagId;
use Wwwision\DAM\Model\TagIds;
use Wwwision\DAM\Model\TagLabel;

#[Flow\Scope('singleton')]
final class MutationResolver implements ResolverInterface
{

    /**
     * @var ResourceManager
     */
    #[Flow\Inject]
    protected $resourceManager;

    /**
     * @var ImageService
     */
    #[Flow\Inject]
    protected $imageService;

    /**
     * @var DAM
     */
    #[Flow\Inject]
    protected $dam;

    public function deleteAsset($_, array $variables): bool
    {
        $command = new DeleteAsset(AssetId::fromString($variables['id']));
        $this->dam->handle($command);
        return true;
    }

    public function updateAsset($_, array $variables): Asset
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682515058);
    }

    public function tagAsset($_, array $variables): Asset
    {
        $assetId = AssetId::fromString($variables['id']);
        $command = new AddTagToAsset(
            $assetId,
            TagId::fromString($variables['tagId']),
        );
        $this->dam->handle($command);

        return $this->dam->findAssetById($assetId);
    }

    public function setAssetTags($_, array $variables): Asset
    {
        $assetId = AssetId::fromString($variables['id']);
        $command = new SetAssetTags(
            $assetId,
            TagIds::fromArray($variables['tagIds'])
        );
        $this->dam->handle($command);
        return $this->dam->findAssetById($assetId);
    }

    public function setAssetCollections($_, array $variables): Asset
    {
        $assetId = AssetId::fromString($variables['id']);
        $firstAssetCollectionId = $variables['assetCollectionIds'][array_key_first($variables['assetCollectionIds'])];
        $command = new MoveAsset(
            $assetId,
            FolderId::fromString($firstAssetCollectionId)
        );
        $this->dam->handle($command);
        return $this->dam->findAssetById($assetId);
    }

    public function untagAsset($_, array $variables): Asset
    {
        // This mutation is not really used by the Media UI it seems
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682514971);
    }

    /**
     * Stores the given file and returns an array with the result
     *
     * @return array{filename: string, success: bool, result: string}
     */
    public function uploadFile($_, array $variables): array
    {
        /** @var FlowUploadedFile $file */
        $file = $variables['file'];

        $filename = $file->getClientFilename();
        try {
            $resource = $this->resourceManager->importResource($file->getStream()->detach());
        } catch (ResourceException $e) {
            return [
                'filename' => $filename,
                'success' => false,
                'result' => 'ERROR',
            ];
        }

        // TODO: check whether asset with the same resource already exists?

        $resource->setFilename($filename);
        $resource->setMediaType($file->getClientMediaType());

        $assetId = AssetId::create();

        $folderId = $variables['assetCollectionId'] === null ? null : FolderId::fromString($variables['assetCollectionId']);
        $initialTags = $variables['tagId'] === null ? TagIds::createEmpty() : TagIds::fromArray([$variables['tagId']]);

        $mediaType = MediaType::fromString($resource->getMediaType());
        $assetType = AssetType::fromMediaType($mediaType);

        $size = null;
        if ($assetType->supportsDimensions()) {
            $imageSize = $this->imageService->getImageSize($resource);
            if (is_int($imageSize['width']) && $imageSize['width'] > 0 && is_int($imageSize['height']) && $imageSize['height'] > 0) {
                $size = Dimensions::fromArray($imageSize);
            }
        }

        $this->dam->handle(new AddAsset(
            $assetId,
            $mediaType,
            ResourcePointer::fromString($resource->getSha1()),
            Filename::fromString($resource->getFilename()),
            Metadata::none(),
            AssetLabel::fromString($resource->getFilename()),
            AssetCaption::fromString(''),
            $size,
            $folderId,
            $initialTags,
        ));

        return [
            'filename' => $filename,
            'success' => true,
            'result' => 'ADDED',
        ];
    }

    /**
     * Stores all given files and returns an array of results for each upload
     *
     * @return array<array{filename: string, success: bool, result: string}>
     */
    public function uploadFiles($_, array $variables): array
    {
        /** @var array<FlowUploadedFile> $files */
        $files = $variables['files'];
        $tagId = $variables['tagId'] ?? null;
        $assetCollectionId = $variables['assetCollectionId'] ?? null;

        $results = [];
        foreach ($files as $file) {
            $results[$file->getClientFilename()] = $this->uploadFile($_, [
                'file' => $file,
                'tagId' => $tagId,
                'assetCollectionId' => $assetCollectionId,
            ]);
        }
        return $results;
    }

    /**
     * Replaces an asset and its usages
     *
     * @return array{filename: string, success: bool, result: string}
     * @throws Exception
     */
    public function replaceAsset($_, array $variables): array
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682515608);
    }

    public function editAsset($_): array
    {
        // This mutation is not really used by the Media UI it seems
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682515650);
    }

    public function importAsset($_): Asset
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682515671);
    }

    public function createAssetCollection($_, array $variables): Folder
    {
        $folderId = FolderId::create();
        $command = new AddFolder(
            $folderId,
            FolderLabel::fromString($variables['title']),
            isset($variables['parent']) ? FolderId::fromString($variables['parent']): null,
        );
        $this->dam->handle($command);

        return $this->dam->findFolderById($folderId);
    }

    public function deleteAssetCollection($_, array $variables): array
    {
        $folderId = FolderId::fromString($variables['id']);
        $command = new DeleteFolder($folderId);
        $this->dam->handle($command);

        return [
            'success' => true,
        ];
    }

    public function updateAssetCollection($_, array $variables): Folder
    {
        $folderId = FolderId::fromString($variables['id']);
        if (isset($variables['title'])) {
            $command = new RenameFolder($folderId, FolderLabel::fromString($variables['title']));
            $this->dam->handle($command);
        }
        if (isset($variables['parent'])) {
            $command = new MoveFolder($folderId, FolderId::fromString($variables['parent']));
            $this->dam->handle($command);
        }
        return $this->dam->findFolderById($folderId);
    }

    public function createTag($_, array $variables): Tag
    {
        $tagId = TagId::create();
        $this->dam->handle(new AddTag($tagId, TagLabel::fromString($variables['label'])));
        return $this->dam->findTagById($tagId);
    }

    public function updateTag($_, array $variables): ?Tag
    {
        [
            'id' => $id,
            'label' => $label,
        ] = $variables + ['label' => null];
        $tagId = TagId::fromString($variables['id']);
        $command = new RenameTag(
            $tagId,
            TagLabel::fromString($variables['label'])
        );
        $this->dam->handle($command);
        return $this->dam->findTagById($tagId);
    }

    public function deleteTag($_, array $variables): bool
    {
        $command = new DeleteTag(TagId::fromString($variables['id']));
        $this->dam->handle($command);
        return true;
    }
}
