<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\AI\Platform\Bridge\Bedrock\Command;

use Symfony\AI\Platform\Bridge\Bedrock\BedrockClient;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @author Christopher Hertel <mail@christopher-hertel.de>
 */
#[AsCommand('ai:bedrock:model-list', 'Lists available foundation models on Amazon Bedrock')]
final class ModelListCommand extends Command
{
    public function __construct(
        private readonly BedrockClient $bedrockClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('provider', 'p', InputOption::VALUE_OPTIONAL, 'Filter by provider name (e.g. "Anthropic", "Amazon", "Meta")')
            ->addOption('output-modality', 'o', InputOption::VALUE_OPTIONAL, 'Filter by output modality (e.g. "TEXT", "IMAGE", "EMBEDDING")')
            ->addOption('inference-type', 'i', InputOption::VALUE_OPTIONAL, 'Filter by inference type (e.g. "ON_DEMAND", "PROVISIONED")')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $provider = $input->getOption('provider');
        $outputModality = $input->getOption('output-modality');
        $inferenceType = $input->getOption('inference-type');

        $io->title('Amazon Bedrock Foundation Models');

        $models = $this->bedrockClient->listFoundationModels($provider, $outputModality, $inferenceType);

        if ([] === $models) {
            $io->warning('No models found for the given filters.');

            return Command::FAILURE;
        }

        $rows = [];
        foreach ($models as $model) {
            $rows[] = [
                $model['modelId'],
                $model['modelName'],
                $model['providerName'],
                implode(', ', $model['inputModalities']),
                implode(', ', $model['outputModalities']),
            ];
        }

        $io->table(
            ['Model ID', 'Name', 'Provider', 'Input Modalities', 'Output Modalities'],
            $rows,
        );

        $io->success(\sprintf('Found %d model(s).', \count($models)));

        return Command::SUCCESS;
    }
}
