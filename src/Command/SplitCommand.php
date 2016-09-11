<?php

namespace TBureck\SplitPhp\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class SplitCommand
 * @package TBureck\SplitPhp\Command
 *
 * @author Tim Bureck
 * @since 2016-08-25
 */
class SplitCommand extends Command
{

    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this
            ->setName('split')
            ->setDescription('Splits the current repository to its sub repositories using the split configuration file.')
            ->addArgument('branch', InputArgument::OPTIONAL, 'The branch to split from.', 'master')
            ->addOption('config', 'c', InputOption::VALUE_REQUIRED, 'The configuration file to be used.', 'splitsh.json');
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Load configuration file
        $configFile = $input->getOption('config');

        if (!file_exists($configFile)) {
            $output->writeln(sprintf('<error>Passed configuration file "%s" does not exist.</error>', $configFile));
            return 1;
        }

        // Change to the given branch:
        $theBranch = $input->getArgument('branch');
        exec(sprintf('git checkout %s', $theBranch));

        $configuration = json_decode(file_get_contents($configFile), true);

        foreach ($configuration as $subTreeName => $subTreeConfiguration) {
            // First check for the branch. If the current commit was made in one of $subTreeConfiguration['branches'], go on:
            if (!in_array($theBranch, $subTreeConfiguration['branches'])) {
                $output->writeln('<info>Skipped subtree "%s", because of branch configuration.</info>', OutputInterface::VERBOSITY_VERBOSE);
                continue;
            }

            // Assemble prefix options for splitsh:
            $prefixOptions = '';
            foreach ($subTreeConfiguration['prefixes'] as $srcDir => $targetDir) {
                $prefixOptions .= sprintf('--prefix=%s:%s ', $srcDir, $targetDir ?: '.');
            }

            // Split action :)
            exec(sprintf('splitsh %s --target=%s', $prefixOptions, $subTreeName));

            // Checkout the splitted branch and push it to the configured remote end:
            exec(sprintf('git checkout %s', $subTreeName));
            exec(sprintf('git push %s %s:refs/heads/%s', $subTreeConfiguration['target'], $theBranch, $theBranch));

            // Finally, change back to the source branch:
            exec(sprintf('git checkout %s', $theBranch));
        }

        return 0;
    }
}