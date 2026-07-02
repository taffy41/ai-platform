<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\HuggingFace\Command;

use Symfony\AI\Platform\Bridge\HuggingFace\ApiClient;
use Symfony\AI\Platform\Model;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand('ai:huggingface:model-list', 'Lists all available models on Hugging Face')]
final class ModelListCommand extends Command
{
    public function __construct(
        private readonly ApiClient $apiClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('provider', 'p', InputOption::VALUE_OPTIONAL, 'Name of the inference provider to filter models by')
            ->addOption('task', 't', InputOption::VALUE_OPTIONAL, 'Name of the task to filter models by')
            ->addOption('search', 's', InputOption::VALUE_OPTIONAL, 'Search term to filter models by')
            ->addOption('warm', 'w', InputOption::VALUE_NONE, 'Only list models that are "warm" (i.e. ready for inference without cold start)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $provider = $input->getOption('provider');
        $task = $input->getOption('task');
        $search = $input->getOption('search');
        $warm = $input->getOption('warm');

        $io->title('Hugging Face Model Listing');

        $models = $this->apiClient->getModels($provider, $task, $search, $warm);

        if (0 === \count($models)) {
            $io->error('No models found for the given filters.');

            return Command::FAILURE;
        }

        $formatModel = static function (Model $model) {
            return \sprintf('%s <comment>[%s]</>', $model->getName(), implode(', ', $model->getOptions()['tags'] ?? []));
        };

        $io->listing(array_map($formatModel, $models));

        $io->success(\sprintf('Found %d model(s).', \count($models)));

        return Command::SUCCESS;
    }
}
