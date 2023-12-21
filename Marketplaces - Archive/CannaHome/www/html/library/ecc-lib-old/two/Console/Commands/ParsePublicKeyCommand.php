<?php

namespace Mdanter\Ecc\Console\Commands;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Mdanter\Ecc\Console\Commands\Helper\KeyTextDumper;

class ParsePublicKeyCommand extends AbstractCommand
{
    /**
     *
     */
    protected function configure()
    {
        $this->setName('parse-pubkey')->setDescription('Parse a PEM encoded public key, without its delimiters.')
            ->addArgument('data', InputArgument::OPTIONAL)
            ->addOption('infile', null, InputOption::VALUE_OPTIONAL)
            ->addOption(
                'in',
                null,
                InputOption::VALUE_OPTIONAL,
                'Input format (der or pem). Defaults to pem.',
                'pem'
            )
            ->addOption('rewrite', null, InputOption::VALUE_NONE, 'Regenerate and output the PEM data from the parsed key.', null);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $parser = $this->getPublicKeySerializer($input, 'in');
        $loader = $this->getLoader($input, 'in');

        $data = $this->getPublicKeyData($input, $loader, 'infile', 'data');
        $key = $parser->parse($data);

        $output->writeln('');
        KeyTextDumper::dumpPublicKey($output, $key);
        $output->writeln('');

        if ($input->getOption('rewrite')) {
            $output->writeln($parser->serialize($key));
        }
    }
}
