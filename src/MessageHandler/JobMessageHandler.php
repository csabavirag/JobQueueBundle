<?php

namespace TomAtom\JobQueueBundle\MessageHandler;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Process\Process;
use TomAtom\JobQueueBundle\Message\JobMessage;

#[AsMessageHandler]
class JobMessageHandler
{
    public function __invoke(JobMessage $message): void
    {
        // Command data from the job
        $jobId = $message->getJobId();

        $command = sprintf('php bin/console jobqueue:run %s', $jobId);
        $process = new Process(explode(' ', $command));
        $process->setWorkingDirectory(dirname(__DIR__, 5));
        $process->setEnv($message->getEnvVars());
        $process->disableOutput();
        $process->setTimeout(null);
        $process->run();
    }
}
