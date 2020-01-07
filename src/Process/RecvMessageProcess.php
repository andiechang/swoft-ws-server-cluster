<?php declare(strict_types=1);

namespace Jcsp\WsCluster\Process;

use Jcsp\Queue\Annotation\Mapping\Pull;
use Jcsp\Queue\Result;
use Jcsp\WsCluster\Cluster;
use Jcsp\WsCluster\State\RedisState;
use Swoft\Bean\Annotation\Mapping\Bean;
use Swoft\Bean\Annotation\Mapping\Inject;
use Swoft\Bean\BeanFactory;
use Swoft\Db\Exception\DbException;
use Swoft\Process\Process;
use Jcsp\Queue\Contract\UserProcess;

/**
 * Class MonitorProcess
 *
 * @since 2.0
 *
 * @Bean()
 */
class RecvMessageProcess extends UserProcess
{
    private $state;

    public function init()
    {
        $this->state = BeanFactory::getBean(Cluster::STATE);
    }

    /**
     * @param Process $process
     * @Pull()
     */
    public function run(Process $process): void
    {
        d('start');
        /** @var RedisState $redisState */
        $redisState = BeanFactory::getBean(Cluster::STATE);
        //add queue
        $this->queue = $redisState->getPrefix() . ':message:' . $redisState->getServerId();
        //waite
    }

    /**
     * customer
     * @param $message
     * @return string
     */
    public function receive($message): string
    {
        $message = $this->state->getSerializer()->unserialize($message);
        if (is_array($message) && count($message) === 2) {
            [$content, $fd] = $message;
            if (is_null($fd)) {
                server()->sendToAll($message);
            }
            if (is_array($fd)) {
                foreach ($fd as $id) {
                    server()->push((int)$id, $content);
                }
            }

        }
        return Result::ACK;
    }

    /**
     * when error callback
     * @param $message
     * @return string
     */
    public function fallback(\Throwable $throwable): void
    {
        d($throwable);
    }
}
