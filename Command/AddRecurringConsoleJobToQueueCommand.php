<?php

namespace Markup\JobQueueBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * This command reads the recurring job configuration
 * and adds any reurring commands to the specified job queue
 *
 * This command should be run every minute via crontab
 */
class AddRecurringConsoleJobToQueueCommand extends ContainerAwareCommand
{

    /**
     * @see Command
     */
    protected function configure()
    {
        $this
            ->setName('markup:job_queue:recurring:add')
            ->setDescription('Adds any configured recurring jobs, which are due NOW, to the specified job queue');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $recurringConsoleCommandReader = $this->getContainer()->get('markup_admin_job_queue_recurring_console_command_reader');

        $due = $recurringConsoleCommandReader->getDue();

        foreach ($due as $configuration) {
            $this->getContainer()->get('jobby')->addCommandJob(
                $configuration->getCommand(),
                $configuration->getTopic(),
                $configuration->getTimeout(),
                $configuration->getTimeout()
            );
            $message = sprintf('Added command `%s` with the topic `%s`', $configuration->getCommand(), $configuration->getTopic());
            if ($configuration->nextRun()) {
                $message = sprintf('%s. Will next be added %s', $message, $configuration->nextRun()->format('r'));
            }
            $output->writeLn(sprintf('<info>%s</info>', $message));
        }
    }
}
