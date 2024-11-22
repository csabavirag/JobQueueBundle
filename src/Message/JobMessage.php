<?php

namespace TomAtom\JobQueueBundle\Message;

class JobMessage
{
    private int $jobId;
    private array $envVars;

    public function __construct(int $jobId, array $envVars)
    {
        $this->jobId = $jobId;
        $this->envVars = $envVars;
    }

    /**
     * @return int
     */
    public function getJobId(): int
    {
        return $this->jobId;
    }

    public function getEnvVars(): array
    {
        return $this->envVars;
    }
}