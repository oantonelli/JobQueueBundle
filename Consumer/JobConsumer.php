<?php

namespace Markup\JobQueueBundle\Consumer;

use Markup\JobQueueBundle\Exception\JobMissingClassException;
use Markup\JobQueueBundle\Model\Job;
use OldSound\RabbitMqBundle\RabbitMq\ConsumerInterface;
use PhpAmqpLib\Message\AMQPMessage;
use Symfony\Component\DependencyInjection\ContainerAware;

class JobConsumer extends ContainerAware implements ConsumerInterface
{
    public function execute(AMQPMessage $message)
    {
        try {
            $data = json_decode($message->body, $array = true);
            if (!isset($data['job_class'])) {
                throw new JobMissingClassException('`job_class` must be set in the message');
            }
            // rehydrate job class
            $jobClass = $data['job_class'];
            unset($data['job_class']);
            $job = new $jobClass($data);
            if (!$job instanceof Job) {
                throw new \LogicException('This consumer can only consume instances of Markup\JobQueueBundle\Model\Job but job of following type was given: '.get_class($job));
            }
            $job->validate();
            $job->run($this->container);
        } catch (\Exception $e) {
            $this->container->get('logger')->error(
                sprintf(
                    'Unable to process job due to `%s`: %s in `%s` line `%s`',
                    get_class($e),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                )
            );

            return ConsumerInterface::MSG_REJECT;
        }
    }
}
