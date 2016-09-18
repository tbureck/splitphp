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

        $output->writeln(sprintf('Using configuration file "%s".', $configFile), OutputInterface::VERBOSITY_VERBOSE);

        // Change to the given branch:
        $theBranch = $targetBranch = $input->getArgument('branch');
        if (strpos($theBranch, 'refs/heads/') !== 0) {
            $targetBranch = 'refs/heads/' . $theBranch;
        } else {
            $theBranch = substr($theBranch, 11);
        }

        $output->writeln(sprintf('Splitting from branch "%s" (%s)...', $theBranch, $targetBranch), OutputInterface::VERBOSITY_VERBOSE);

        exec(sprintf('git checkout %s', $theBranch));

        $output->writeln('Reading configuration file...', OutputInterface::VERBOSITY_VERBOSE);
        $configuration = json_decode(file_get_contents($configFile), true);

        if (!count($configuration)) {
            $output->writeln('Configuration is empty.');

            var_dump($configuration);
        }

        foreach ($configuration as $subTreeName => $subTreeConfiguration) {
            // First check for the branch. If the current commit was made in one of $subTreeConfiguration['branches'], go on:
            if (!in_array($theBranch, $subTreeConfiguration['branches'])) {
                $output->writeln(sprintf('<info>Skipped subtree "%s", because of branch configuration.</info>', $theBranch), OutputInterface::VERBOSITY_VERBOSE);
                continue;
            }

            $output->writeln(sprintf('Splitting "%s" to "%s"...', $subTreeName, $subTreeConfiguration['target']), OutputInterface::VERBOSITY_VERBOSE);

            // Assemble prefix options for splitsh:
            $prefixOptions = '';
            foreach ($subTreeConfiguration['prefixes'] as $srcDir => $targetDir) {
                $prefixOptions .= sprintf('--prefix=%s:%s ', $srcDir, $targetDir ?: '.');
            }

            $output->writeln(sprintf('  Splitting prefixes: %s', $prefixOptions), OutputInterface::VERBOSITY_VERY_VERBOSE);

            // Split action :)
            exec(sprintf('splitsh-lite %s --target=%s', $prefixOptions, $subTreeName));

            $output->writeln(sprintf('  Checking out splitted branch "%s"...', $subTreeName), OutputInterface::VERBOSITY_VERY_VERBOSE);

            // Checkout the splitted branch and push it to the configured remote end:
            exec(sprintf('git checkout %s', $subTreeName));
            $output->writeln(sprintf('  Pushing changes to "%s"...', $subTreeConfiguration['target']), OutputInterface::VERBOSITY_VERY_VERBOSE);
            exec(sprintf('git push %s %s:%s', $subTreeConfiguration['target'], $subTreeName, $targetBranch));

            $output->writeln('...done!', OutputInterface::VERBOSITY_VERBOSE);
            // Finally, change back to the source branch:
            exec(sprintf('git checkout %s', $theBranch));
        }

        return 0;
    }
}