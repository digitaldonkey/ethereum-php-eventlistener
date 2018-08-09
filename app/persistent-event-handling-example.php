<?php

require_once __DIR__ . '/vendor/autoload.php';

use \Ethereum\Ethereum;
use \Ethereum\SmartContract;
use \Ethereum\ContractEventProcessor;
use \Ethereum\EmittedEvent as EthEvent;


/**
 * Class CallableEvents
 *
 * Create your own SmartContract class and implement the Solidity Events you
 * want to handle, by adding "onEventName" methods.
 *
 * Note: Class must be the same name as the contract to auto initialize.
 */
class CallableEvents extends SmartContract {

    public function onCalledTrigger1 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
    public function onCalledTrigger2 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
    public function onCalledTrigger3 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
    public function onCalledTrigger4 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
    public function onCalledTrigger5 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
    public function onCalledTrigger6 (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
    public function MoneyReceived (EthEvent $event) {
        echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
        var_dump($event);
    }
}


/*
 * @var $contractMeta
 *
 * The Json file matches Truffle build output
 *
 *   CallableEvents.sol --> build/contracts/CallableEvents.json
 *
 * @see https://github.com/digitaldonkey/ethereum-php/tree/dev/tests/TestEthClient/test_contracts
 * @see: https://truffleframework.com
 */
try {

    $web3 = new Ethereum('http://192.168.99.100:8545');
    $networkId = '5777';

    $contracts = SmartContract::createFromTruffleBuildDirectory(__DIR__ . '/truffle/build/contracts', $web3, $networkId);

    // process any Transaction from Block-0 to the future.
    new ContractEventProcessor(
      $web3,
      $contracts,
      0,
      'latest',
      true
    );
}
catch (\Exception $exception) {
    throw new $exception;
}

