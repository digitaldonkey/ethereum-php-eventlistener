<?php

require_once __DIR__ . '/vendor/autoload.php';

use \Ethereum\Ethereum;
use \Ethereum\BlockProcessor;
use \Ethereum\DataType\Block;


try {
    $web3 = new Ethereum('http://192.168.99.100:8545');

    /**
     * BlockProcessor constructor.
     *
     * By default:
     * Process any Transaction from Block-0 to latest Block (at script run time).
     *
     * @param \Ethereum\Ethereum $web3
     *
     * @param callable $callback
     *   This function will be called at each block.
     *
     * @param int $fromBlockNumber
     *
     * @param bool $increment
     *   Count up by default.
     *
     * @param int|null $toBlockNumber
     *   Will default to latest at script start time or Block or 0.
     *
     * @param float $timePerLoop
     *   Time for each request in seconds.
     *    - Use for throttling or adjust to BlockTime for continuous evaluation.
     *    - If processing takes more time, lowering this value won't help.
     *
     * @throws \Exception
     */

    // Block 14 -> Block 0 --> decrement blocks
    new BlockProcessor(
      $web3,
      function (Block $block) {

          // This will be run on every Block.
          print "\n\n#### BLOCK NUMBER " . $block->number->val() . " ####\n";
          #print_r($block->toArray());
      },
      14,
      0
    );

}
catch (\Exception $exception) {
    throw new $exception;
}

