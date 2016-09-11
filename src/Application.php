<?php

namespace TBureck\SplitPhp;

use Symfony\Component\Console\Application as BaseApplication;
use TBureck\SplitPhp\Command\SplitCommand;

/**
 * Class Application
 * @package TBureck\SplitPhp
 *
 * @author Tim Bureck
 * @since 2016-08-18
 */
class Application extends BaseApplication
{

    const NAME = 'splitphp';
    const VERSION = 'v1.0.0';

    public function __construct()
    {
        parent::__construct(self::NAME, self::VERSION);

        $this->addCommands([
            new SplitCommand()
        ]);
    }
}