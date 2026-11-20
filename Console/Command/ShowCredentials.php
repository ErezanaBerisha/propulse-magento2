<?php

declare(strict_types=1);

namespace Solu1\ProPulse\Console\Command;

use Magento\Framework\Console\Cli;
use Solu1\ProPulse\Model\CredentialProvisioner;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ShowCredentials extends Command
{
    public function __construct(
        private readonly CredentialProvisioner $provisioner
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('propulse:credentials');
        $this->setDescription('Print the proPulse shop URL, username and password.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $credentials = $this->provisioner->credentials();
        if ($credentials['username'] === '' || $credentials['password'] === '') {
            $output->writeln('<error>Could not create the proPulse API user.</error>');

            return Cli::RETURN_FAILURE;
        }

        $output->writeln('Shop URL: ' . $credentials['shopUrl']);
        $output->writeln('Username: ' . $credentials['username']);
        $output->writeln('Password: ' . $credentials['password']);
        $output->writeln('Platform: ' . $credentials['platformType']);

        return Cli::RETURN_SUCCESS;
    }
}
