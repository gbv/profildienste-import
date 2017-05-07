<?php
/**
 * Created by PhpStorm.
 * User: luca
 * Date: 07.05.17
 * Time: 14:35
 */

namespace Commands;


use Importer\UserUpdater;
use Services\LogService;
use Services\MailerService;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UserUpdateCommand extends BaseImportCommand {

    private $userUpdater;

    public function __construct(LogService $logService, MailerService $mailerService, UserUpdater $userUpdater) {
        parent::__construct($logService, $mailerService);
        $this->userUpdater = $userUpdater;
    }

    protected function configure() {
        parent::configure();
        $this->setName('update:users')
            ->setDescription('Starts the user data update.');
    }


    protected function executeImport(InputInterface $input, OutputInterface $output) {
        $this->userUpdater->run();
    }
}