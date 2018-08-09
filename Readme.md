# Ethereum-php Listener and Indexer

**TL;DR**

When developing dapps you might need some Backend process to react data of the latest block or on on-chain Events (Solidity Events). You might fill up some database by indexing all blocks or set up a daemon process to do something every time a new Block is created. 

--------------------------------

As much as we want real decentralization, the reality is actually different. 
Mots Dapp's require a Backend process. Sometimes for additional data which is too expensive to store (semi decentralized apps) or at least for monitoring what is happening at on chain.


You can do this in PHP very easily. E.g Indexing a chain from block 0 to the latest at script start time:

```php
$web3 = new Ethereum('http://192.168.99.100:8545');
// Block 0 -> last block.
new BlockProcessor($web3, function (Block $block) {

    // This will be run on every Block.
    print "\n\n#### BLOCK NUMBER " . $block->number->val() . " ####\n";

    // Add to database... 
    print_r($block->toArray());
  }
);

``` 

Run the script. E.g: 

```bash 
php app/basic-blockProcessor-example.php
```

You need to run `composer install` in the app directory to load the dependencies.

## Integration with Truffle and Contract Events

If you are using [Truffle](http://truffleframework.com/) to develop you Dapp you can easily set up a monitoring system for your smart contracts:

```php 
// Extend a \Ethereum\SmartContract with EventHandlers
class CallableEvents extends SmartContract {
  public function onCalledTrigger1 (EthEvent $event) {
    echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
    var_dump($event);
  }
  public function onCalledTrigger2 (EthEvent $event) {
    echo '### ' . substr(__FUNCTION__, 2) . "(\Ethereum\EmittedEvent)\n";
    var_dump($event);
  }
}

$web3 = new Ethereum('http://192.168.99.100:8545');
$networkId = '5777';

// Contract Classes must have same name as the solidity classes for this to work.
$contracts = SmartContract::createFromTruffleBuildDirectory(
  'YOUR/truffle/build/contracts',
   $web3,
   $networkId
);

// process any Transaction from current Block to the future.
new ContractEventProcessor(
  $web3,
  $contracts,
  'latest',
  'latest'
);

```

## Background 

The Loop script is based on [reactphp](https://github.com/reactphp/react) which is actually older that Javascript React. 

The Ethereum part is based on [Ethereum-php](https://github.com/digitaldonkey/ethereum-php) library.

You might use [ganache-cli-docker-compose](https://github.com/digitaldonkey/ganache-cli-docker-compose) for testing.

You may use the indexer with [Infura](https://infura.io) Ethereum as a Service, as it doesn't rely on filters, which Infura does not support.

I used a very simple [DAPP](https://github.com/digitaldonkey/react-box-event-handling) to interact with the CallableEvents smart contract used as a example here. 


##Docker

```bash 
# Build
docker build -t ethereum-php-event-handler .

# Login
docker run -v $(pwd)/app:/opt/local/php-ethereum-listener -it ethereum-php-event-handler bash

# Deamon 
docker run -v $(pwd)/app:/opt/local/php-ethereum-listener -it ethereum-php-event-handler bash -c '/opt/local/bin/docker-run.sh'
```
