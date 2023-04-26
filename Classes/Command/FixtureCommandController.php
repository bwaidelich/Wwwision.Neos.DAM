<?php
declare(strict_types=1);
namespace Wwwision\Neos\DAM\Command;

use GuzzleHttp\Client;
use Neos\Flow\Cli\CommandController;
use Neos\Flow\Persistence\PersistenceManagerInterface;
use Neos\Flow\ResourceManagement\ResourceManager;
use Neos\Flow\Utility\Environment;
use Neos\Media\Domain\Model\Image;
use Neos\Media\Domain\Repository\AssetRepository;

final class FixtureCommandController extends CommandController
{

    private const BATCH_SIZE = 30;

    public function __construct(
        private readonly AssetRepository $assetRepository,
        private readonly ResourceManager $resourceManager,
        private readonly Environment $environment,
        private readonly PersistenceManagerInterface $persistenceManager,
    )
    {
        parent::__construct();
    }

    /**
     * @param int $numberOfImages Number of images to import
     */
    public function createCommand(int $numberOfImages = 100): void
    {
        $httpClient = new Client();
        $this->output->progressStart($numberOfImages);
        for ($i = 0; $i < $numberOfImages; $i ++) {

            $tempFileName = tempnam($this->environment->getPathToTemporaryDirectory(), 'import');
            $httpClient->get('https://picsum.photos/' . random_int(500, 3000), ['headers' => ['User-Agent' => 'Test Importer'], 'sink' => $tempFileName]);

            $resource = $this->resourceManager->importResourceFromContent(file_get_contents($tempFileName), uniqid('picsum', false) . '.jpg');
            $asset = new Image($resource);
            $this->assetRepository->add($asset);
            if ($i % self::BATCH_SIZE === 0) {
                $this->persistenceManager->persistAll();
            }
            $this->output->progressAdvance();
        }
        $this->persistenceManager->persistAll();
        $this->output->progressFinish();
    }
}
