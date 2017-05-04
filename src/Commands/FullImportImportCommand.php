<?php

namespace Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FullImportImportCommand extends BaseImportCommand {

    private $availableImporters = [
        [
            'name' => 'remote',
            'description' => 'Fetches all title datasets from the configured remote dirs',
            'command' => 'import:remote'
        ],
        [
            'name' => 'import-titles',
            'description' => 'Runs the title importing step',
            'command' => 'import:titles'
        ],
        [
            'name' => 'update-titles',
            'description' => 'Runs the title updating step',
            'command' => 'update:titles'
        ],
        [
            'name' => 'import-users',
            'description' => 'Runs the user importing step',
            'command' => 'import:users'
        ],
        [
            'name' => 'update-users',
            'description' => 'Runs the user updating step',
            'command' => 'update:users'
        ],
        [
            'name' => 'covers',
            'description' => 'Runs the cover importing step',
            'command' => 'import:covers'
        ]
    ];

    protected function configure() {
        parent::configure();

        $this->setName('import:run')
            ->setDescription('Starts the import.')
            ->setHelp('Runs either all or the specified steps of the import process. If no flags are specified, all steps are executed.');

        foreach ($this->availableImporters as $availableImporter) {
            $this->addOption($availableImporter['name'], null, InputOption::VALUE_NONE ,$availableImporter['description']);
        }
    }

    protected function executeImport(InputInterface $input, OutputInterface $output) {

        $selectedImporters = [];
        foreach ($this->availableImporters as $availableImporter) {
            if ($input->hasParameterOption([$availableImporter['name'], '--'.$availableImporter['name']])) {
                $selectedImporters[] = $availableImporter['name'];
            }
        }

        if (count($selectedImporters) === 0) {
            $selectedImporters = array_map(function ($imp) { return $imp['name']; }, $this->availableImporters);
        }

        var_dump($selectedImporters);
    }
}