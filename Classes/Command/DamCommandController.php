<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\Command;

use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Media\Domain\Model\Asset as MediaAsset;
use Neos\Media\Domain\Model\AssetCollection;
use Neos\Media\Domain\Model\ImageInterface;
use Neos\Media\Domain\Model\Tag as MediaTag;
use Neos\Media\Domain\Model\Video;
use Neos\Media\Domain\Repository\AssetCollectionRepository;
use Neos\Media\Domain\Repository\AssetRepository;
use Neos\Media\Domain\Repository\TagRepository;
use Symfony\Component\Console\Helper\ProgressBar;
use Wwwision\DAM\Command\AddAsset;
use Wwwision\DAM\Command\AddFolder;
use Wwwision\DAM\Command\AddTag;
use Wwwision\DAM\DAM;
use Wwwision\DAM\Model\AssetCaption;
use Wwwision\DAM\Model\AssetId;
use Wwwision\DAM\Model\AssetLabel;
use Wwwision\DAM\Model\Filename;
use Wwwision\DAM\Model\FolderId;
use Wwwision\DAM\Model\FolderLabel;
use Wwwision\DAM\Model\MediaType;
use Wwwision\DAM\Model\Metadata;
use Wwwision\DAM\Model\ResourcePointer;
use Wwwision\DAM\Model\Dimensions;
use Wwwision\DAM\Model\TagId;
use Wwwision\DAM\Model\TagIds;
use Wwwision\DAM\Model\TagLabel;

final class DamCommandController extends CommandController
{
    public function __construct(
        private readonly DAM $dam,
        private readonly AssetRepository $assetRepository,
        private readonly AssetCollectionRepository $assetCollectionRepository,
        private readonly TagRepository $tagRepository,
        private readonly PersistenceManagerInterface $persistenceManager,
    )
    {
        parent::__construct();
    }

    public function setupCommand(): void
    {
        $this->dam->setUp();
        $this->outputLine('<success>Done</success>');
    }

    public function importCommand(): void
    {
        $this->importTagsCommand();
        $this->importFoldersCommand();
        $this->importAssetsCommand();
    }

    public function importFoldersCommand(): void
    {
        $this->outputLine('Importing folders (aka AssetCollections)...');
        $collectionIds = [];
        $pendingCommands = [];
        $progressBar = new ProgressBar($this->output->getOutput());
        $progressBar->setFormat(ProgressBar::FORMAT_DEBUG);
        $progressBar->start($this->assetCollectionRepository->countAll());
        /** @var AssetCollection $assetCollection */
        foreach ($this->assetCollectionRepository->findAll() as $assetCollection) {
            $id = $this->persistenceManager->getIdentifierByObject($assetCollection);
            $parentCollection = method_exists($assetCollection, 'getParent') ? $assetCollection->getParent() : null;
            $parentId = $parentCollection !== null ? $this->persistenceManager->getIdentifierByObject($parentCollection) : null;

            $command = new AddFolder(
                FolderId::fromString($id),
                FolderLabel::fromString($assetCollection->getTitle()),
                $parentId !== null ? FolderId::fromString($parentId) : null,
            );
            $collectionIds[$id] = true;
            if ($parentId === null || array_key_exists($parentId, $collectionIds)) {
                $this->dam->handle($command);
            } else {
                $pendingCommands[] = $command;
            }
            $progressBar->advance();
        }
        foreach ($pendingCommands as $command) {
            $this->dam->handle($command);
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->outputLine();
        $this->outputLine('<success>Done</success>');
    }

    public function importTagsCommand(): void
    {
        $this->outputLine('Importing tags...');
        $progressBar = new ProgressBar($this->output->getOutput());
        $progressBar->setFormat(ProgressBar::FORMAT_DEBUG);
        $progressBar->start($this->tagRepository->countAll());
        /** @var MediaTag $mediaTag */
        foreach ($this->tagRepository->findAll() as $mediaTag) {
            $id = $this->persistenceManager->getIdentifierByObject($mediaTag);
            $this->dam->handle(new AddTag(
                TagId::fromString($id),
                TagLabel::fromString($mediaTag->getLabel()),
            ));
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->outputLine();
        $this->outputLine('<success>Done</success>');
    }

    public function importAssetsCommand(): void
    {
        $this->outputLine('Importing assets...');
        $progressBar = new ProgressBar($this->output->getOutput());
        $progressBar->setFormat(ProgressBar::FORMAT_DEBUG);
        $progressBar->start($this->assetRepository->countAll());
        /** @var MediaAsset $mediaAsset */
        foreach ($this->assetRepository->findAll() as $mediaAsset) {
            $resource = $mediaAsset->getResource();
            if ($resource === null) {
                continue;
            }
            if ($mediaAsset instanceof ImageInterface || $mediaAsset instanceof Video) {
                $size = Dimensions::fromWidthAndHeight($mediaAsset->getWidth(), $mediaAsset->getHeight());
            } else {
                $size = null;
            }
            $initialTagIds = TagIds::fromArray(array_map(fn (MediaTag $mediaTag) => TagId::fromString($this->persistenceManager->getIdentifierByObject($mediaTag)), $mediaAsset->getTags()->toArray()));
            $assetCollection = $mediaAsset->getAssetCollections()->first();
            $folderId = $assetCollection instanceof AssetCollection ? FolderId::fromString($this->persistenceManager->getIdentifierByObject($assetCollection)) : null;

            $this->dam->handle(new AddAsset(
                AssetId::fromString($mediaAsset->getIdentifier()),
                MediaType::fromString($mediaAsset->getMediaType()),
                ResourcePointer::fromString($resource->getSha1()),
                Filename::fromString($resource->getFilename()),
                Metadata::none(),
                AssetLabel::fromString($mediaAsset->getLabel()),
                AssetCaption::fromString($mediaAsset->getCaption()),
                $size,
                $folderId,
                $initialTagIds,
            ));
            $progressBar->advance();
        }
        $progressBar->finish();
        $this->outputLine();
        $this->outputLine('<success>Done</success>');
    }

}
