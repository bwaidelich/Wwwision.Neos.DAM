<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\GraphQLResolvers;

use Flowpack\Media\Ui\GraphQL\Context\AssetSourceContext;
use Flowpack\Media\Ui\Service\AssetChangeLog;
use Flowpack\Media\Ui\Service\SimilarityService;
use Flowpack\Media\Ui\Service\UsageDetailsService;
use Neos\Flow\Annotations as Flow;
use Neos\Flow\Persistence\Doctrine\PersistenceManager;
use Neos\Utility\Exception\FilesException;
use Neos\Utility\Exception\PropertyNotAccessibleException;
use Neos\Utility\Files;
use t3n\GraphQL\ResolverInterface;
use Wwwision\DAM\DAM;
use Wwwision\DAM\Model\Asset;
use Wwwision\DAM\Model\AssetId;
use Wwwision\DAM\Model\AssetType;
use Wwwision\DAM\Model\Filter\AssetFilter;
use Wwwision\DAM\Model\Filter\Ordering;
use Wwwision\DAM\Model\Filter\OrderingDirection;
use Wwwision\DAM\Model\Filter\OrderingField;
use Wwwision\DAM\Model\Filter\Pagination;
use Wwwision\DAM\Model\Filter\SearchTerm;
use Wwwision\DAM\Model\Folder;
use Wwwision\DAM\Model\FolderId;
use Wwwision\DAM\Model\Folders;
use Wwwision\DAM\Model\Tag;
use Wwwision\DAM\Model\TagId;
use Wwwision\DAM\Model\Tags;

#[Flow\Scope('singleton')]
final class QueryResolver implements ResolverInterface
{

    /**
     * @var DAM
     */
    #[Flow\Inject]
    protected $dam;

    /**
     * @Flow\Inject
     * @var UsageDetailsService
     */
    protected $assetUsageService;

    /**
     * @Flow\Inject
     * @var AssetChangeLog
     */
    protected $assetChangeLog;

    /**
     * @Flow\Inject
     * @var SimilarityService
     */
    protected $similarityService;

    /**
     * @Flow\Inject
     * @var PersistenceManager
     */
    protected $persistenceManager;

    /**
     * @Flow\InjectConfiguration(package="Flowpack.Media.Ui")
     * @var array
     */
    protected $settings;

    /**
     * Returns total count of asset proxies in the given asset source
     * @noinspection PhpUnusedParameterInspection
     */
    public function assetCount($_, array $variables): int
    {
        return $this->dam->countAssets($this->buildAssetFilter($variables));
    }

    /**
     * Returns a list of accessible and inaccessible relations for the given asset
     */
    public function assetUsageDetails($_, array $variables): array
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682524404);
    }

    /**
     * Returns the total usage count for the given asset
     */
    public function assetUsageCount($_, array $variables): int
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682524418);
    }

    /**
     * Returns an array with helpful configurations for interacting with the API
     */
    public function config($_): array
    {
        return [
            'uploadMaxFileSize' => $this->getMaximumFileUploadSize(),
            'uploadMaxFileUploadLimit' => $this->getMaximumFileUploadLimit(),
            'currentServerTime' => (new \DateTime())->format(DATE_W3C),
        ];
    }

    /**
     * Returns the lowest configured maximum upload file size
     */
    protected function getMaximumFileUploadSize(): int
    {
        try {
            return (int)min(
                Files::sizeStringToBytes(ini_get('post_max_size')),
                Files::sizeStringToBytes(ini_get('upload_max_filesize'))
            );
        } catch (FilesException $e) {
            return 0;
        }
    }

    /**
     * Returns the maximum number of files that can be uploaded
     */
    protected function getMaximumFileUploadLimit(): int
    {
        return (int)($this->settings['maximumFileUploadLimit'] ?? 10);
    }

    public function assets(
        $_,
        array $variables,
        AssetSourceContext $assetSourceContext
    ) {
        return $this->dam->findAssets($this->buildAssetFilter($variables));
    }

    private function buildAssetFilter(array $variables): AssetFilter
    {
        $filter = AssetFilter::create();
        $assetType = $variables['assetType'] ?? $variables['mediaType'] ?? null;
        if (!empty($assetType) && $assetType !== 'all') {
            $filter = $filter->with(assetType: match ($assetType) {
                'audio' => AssetType::Audio,
                'document' => AssetType::Document,
                'image' => AssetType::Image,
                'video' => AssetType::Video,
            });
        }
        if (!empty($variables['tagId'])) {
            $filter = $filter->with(tagId: TagId::fromString($variables['tagId']));
        }
        if (!empty($variables['assetCollectionId'])) {
            $filter = $filter->with(folderId: FolderId::fromString($variables['assetCollectionId']));
        }
        if (!empty($variables['searchTerm'])) {
            $filter = $filter->with(searchTerm: SearchTerm::fromString($variables['searchTerm']));
        }
        if (isset($variables['sortBy'])) {
            $orderingField = match ($variables['sortBy']) {
                'lastModified' => OrderingField::LAST_MODIFIED,
                default => OrderingField::NAME,
            };
            $orderingDirection = ($variables['sortDirection'] ?? null) === 'DESC' ? OrderingDirection::DESCENDING : OrderingDirection::ASCENDING;
            $filter = $filter->with(ordering: Ordering::by($orderingField, $orderingDirection));
        }
        return $filter->with(
            pagination: Pagination::fromLimitAndOffset($variables['limit'] ?? 20, $variables['offset'] ?? 0),
        );
    }

    public function unusedAssets($_, array $variables): array
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682524453);
    }

    public function unusedAssetCount(): int
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682524467);
    }

    public function tags($_): Tags
    {
        return $this->dam->findTags();
    }

    public function tag($_, array $variables): ?Tag
    {
        return $this->dam->findTagById(TagId::fromString($variables['id']));
    }

    public function assetSources($_, array $variables, AssetSourceContext $assetSourceContext): array
    {
        return $assetSourceContext->getAssetSources();
    }

    public function assetCollections($_, array $variables): Folders
    {
        return $this->dam->findFolders();
    }

    /**
     * Returns an asset collection by id
     */
    public function assetCollection($_, array $variables): ?Folder
    {
        return $this->dam->findFolderById(FolderId::fromString($variables['id']));
    }

    /**
     * Returns an asset proxy by id
     */
    public function asset($_, array $variables): ?Asset
    {
        return $this->dam->findAssetById(AssetId::fromString($variables['id']));
    }

    public function assetVariants($_, array $variables): array
    {
        throw new \BadMethodCallException('TODO Implement ' . __METHOD__, 1682524757);
    }

    public function changedAssets($_, array $variables): array
    {
        // TODO implement
        return [
            'lastModified' => '1970-01-01T00:00:00+00:00',
            'changes' => [],
        ];
    }

    public function similarAssets($_, array $variables): array
    {
        // TODO implement
        return [];
    }
}
