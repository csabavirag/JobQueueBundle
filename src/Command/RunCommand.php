<?php

namespace TomAtom\JobQueueBundle\Command;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use TomAtom\JobQueueBundle\Entity\Job;

#[AsCommand(
    name: 'jobqueue:run',
    description: 'Run a job from the queue',
)]
class RunCommand extends Command
{

    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('jobId', InputArgument::REQUIRED, 'The ID of the job to run');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Command data from the job
        $jobId = $input->getArgument('jobId');
        $job = $this->entityManager->getRepository(Job::class)->findOneBy(['id' => $jobId]);
        $commandName = $job->getCommand();
        $params = $job->getCommandParams();
        $command = 'php bin/console ' . $commandName;
        if ($params !== null && $params !== [] && $params !== '') {
            if (is_array($params)) {
                $command .= ' ' . implode(' ', $params);
            } else {
                $command .= ' ' . $params;
            }
        }

        // Start the process
        $process = new Process(explode(' ', $command));
        $process->setWorkingDirectory(dirname(__DIR__, 5));
        $process->enableOutput();
        $process->setTimeout(null);
        $process->start();

        // Update the job
        $job->setStatus(Job::STATUS_RUNNING);
        $job->setStartedAt(new \DateTimeImmutable());
        $this->entityManager->persist($job);
        $this->entityManager->flush();

        // Wait for the process to finish and save the command buffer to the output
        $process->wait(function ($type, $buffer) use ($job): void {
            $job->setOutput($job->getOutput() . $buffer);
            $this->entityManager->persist($job);
            $this->entityManager->flush();
        });

        $job->setStatus($process->isSuccessful() ? Job::STATUS_COMPLETED : Job::STATUS_FAILED);
        $job->setClosedAt(new \DateTimeImmutable());
        $job->setRuntime($job->getStartedAt()->diff($job->getClosedAt()));

        $this->entityManager->persist($job);
        $this->entityManager->flush();
        return Command::SUCCESS;
    }
}
