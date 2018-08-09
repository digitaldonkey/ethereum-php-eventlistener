<?php

namespace Ethereum;

use React\EventLoop\Factory as EventLoopFactory;
use Ethereum\DataType\EthBlockParam;
use Ethereum\DataType\EthB;

class BlockProcessor {

    /* @var \Ethereum\Ethereum $web3 */
    protected $web3;
    protected $fromBlockNumber;
    protected $toBlockNumber;
    protected $increment;

    protected $isInfinite;
    protected $isPersistent;

    private $timePerLoop;
    /* @var \React\EventLoop\LoopInterface $loop */
    private $loop;

    /**
     * BlockProcessor constructor.
     *
     * @param Ethereum $web3
     *
     * @param callable $callback
     *   This function will be called at each block.
     *
     * @param int $fromBlockNumber
     *
     * @param int|null $toBlockNumber
     *   Will default to latest at script start time or Block or 0.
     *
     * @param bool $persistent
     *   Make sure we can resume with latest block after script restart.
     *
     * @param float $timePerLoop
     *   Time for each request in seconds.
     *    - Use for throttling or adjust to BlockTime for continuous evaluation.
     *    - If processing takes more time, lowering this value won't help.
     *
     * @throws \Exception
     */
    public function __construct(
      Ethereum $web3,
      callable $callback,
      $fromBlockNumber = null,
      $toBlockNumber = null,
      ?bool $persistent = false,
      ?float $timePerLoop = 0.3
    )
    {
        $this->web3 = $web3;

        // Int || 'latest'
        $this->isInfinite = self::isInfinite($toBlockNumber);

        // $fromBlockNumber integer || null --> Block 0  || 'latest' -> current block
        $fromBlockNumber = $this->startBlock($fromBlockNumber);

        $this->toBlockNumber = $this->endBlock($toBlockNumber);

        $this->increment = $this->upOrDown($fromBlockNumber);

        if ($persistent) {
            $fromBlockNumber = $this->checkPersistency($fromBlockNumber);
        }

        $this->fromBlockNumber = $fromBlockNumber;


        $this->timePerLoop = $timePerLoop;

        $this->isPersistent = $persistent;


        // Validate input.
        self::verifyCountLogic();
        $this->runLoop($callback);

    }


    /**
     * Run the Loop.
     *
     * @param callable $callback
     */
    private function runLoop (callable $callback) {
        $this->loop = EventLoopFactory::create();

        $nextBlock = $this->fromBlockNumber;
        $updateCounter = array($this, 'updateCounter');

        $this->loop->addPeriodicTimer($this->timePerLoop , function() use (&$nextBlock, &$callback, &$updateCounter) {

            // @see https://github.com/ethereum/wiki/wiki/JSON-RPC#eth_getblockbynumber
            $block = $this->web3->eth_getBlockByNumber(
              new EthBlockParam($nextBlock),
              new EthB(true) // Request TX data.
            );
            if (!is_null($block)) {
                call_user_func($callback, $block);
                $nextBlock = call_user_func($updateCounter, $block->number->val());
            }

            if ($nextBlock === FALSE) {
                $this->loop->stop();
            }
        });
        $this->loop->run();
    }


    /**
     * @throws \Exception
     */
    protected function verifyCountLogic() {
        // Ensure this can work.
        if ($this->isInfinite) {
            return;
        }
        if (
          ($this->increment && $this->fromBlockNumber <= $this->toBlockNumber) ||
          (!$this->increment && $this->fromBlockNumber >= $this->toBlockNumber)
        ) {
           return;
        }
        throw new \Exception('Check your counting logic.');
    }


    /**
     * @param string|int|null $lastBlock
     * @return bool
     * @throws \Exception
     */
    private static function isInfinite($lastBlock) {
        if (!is_null($lastBlock) && $lastBlock === 'latest') {
            return true;
        }
        return false;
    }


    /**
     * @param $fromBlockNumber
     * @param $default
     * @return mixed
     */
    private function startBlock($fromBlockNumber, $default = 0) {
        if (is_null($fromBlockNumber)) {
            return $default;
        }
        if ($fromBlockNumber === 'latest') {
            return $this->web3->eth_blockNumber()->val();
        }
        return $fromBlockNumber;
    }


    /**
     * @param $fromBlockNumber
     * @return bool
     */
    private function upOrDown($fromBlockNumber) {
        if ($this->isInfinite) {
            // $this->toBlockNumber == 'latest'
            return true;
        }
        return ($fromBlockNumber < $this->toBlockNumber);

    }


    /**
     * @param $toBlockNumber
     * @return mixed
     */
    private function endBlock($toBlockNumber) {
        if (is_null($toBlockNumber)) {
            return $this->web3->eth_blockNumber()->val();
        }
        return $toBlockNumber;
    }


    /**
     * @param $fromBlockNumber
     * @return bool|string
     * @throws \Exception
     */
    protected function checkPersistency($fromBlockNumber)
    {
        $file = self::persistenceFile();
        if (file_exists($file)) {
            $value = file_get_contents($file);
            if ($value === false) {
                throw new \Exception('Can not read temp file.');
            }
            return $this->nextBock($value);
        }
        return $fromBlockNumber;
    }

    /**
     * @param string $blockNumber
     * @throws \Exception
     */
    protected function setLastBlock(string $blockNumber)
    {
        $file = self::persistenceFile();
        if (
          (file_exists($file) && !is_writable($file)) ||
          file_put_contents($file, (string) $blockNumber) === FALSE
        ) {
            throw new \Exception('Can not write temp file.');
        }
    }

    protected function persistenceDone() {
        unlink(self::persistenceFile());
    }

    /**
     * @return string FilePath.
     */
    protected static function persistenceFile() {
        return sys_get_temp_dir() . '/' . md5(__DIR__ . __FILE__ . __CLASS__);
    }


    /**
     * @param $blockNumber
     * @return int|null
     * @throws \Exception
     */
    protected function updateCounter($blockNumber) {
        $nextBlock = null;
        if ($this->isPersistent) {
            $this->setLastBlock((string) $blockNumber);
        }

        // Update counter
        $nextBlock = $this->nextBock($blockNumber);

        if ($this->isInfinite) {
            // Check if there is a next block > this one before going on.
            $latestBlockNumber = $this->web3->eth_blockNumber()->val();
            if ($nextBlock <= $latestBlockNumber) {
                return $nextBlock;
            }
            else {
                while ($nextBlock > $latestBlockNumber) {
                    sleep(1);
                    $latestBlockNumber = $this->web3->eth_blockNumber()->val();
                }
                return $latestBlockNumber;
            }
        }
        else {
            if (
              ($this->increment && $nextBlock > $this->toBlockNumber) ||
              (!$this->increment && $nextBlock < $this->toBlockNumber)
            ) {
                $nextBlock = FALSE; // Will end the loop.
                if ($this->isPersistent) {
                    self::persistenceDone();
                }
            }
            return $nextBlock;
        }
    }


    /**
     * @param $currentBlock
     * @return int
     */
    private function nextBock($currentBlock) {
        return $this->increment ? $currentBlock + 1 : $currentBlock - 1;
    }
}
