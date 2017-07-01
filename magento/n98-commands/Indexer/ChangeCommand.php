<?php
/**
 * Magento n98-Magerun Indexer Command
 * 
 * Magento Magerun Command to enable or disable indexer.
 * 
 * @package         LupusCoding\Magento\Command\Indexer
 * @author          LupusCoding <https://github.com/LupusCoding/>
 * @version         0.1.0
 * @dependency      N98-Magerun Lib (n98-magerun.phar)
 *
 */

namespace LupusCoding\Magento\Command\Indexer;

use N98\Magento\Command\Indexer\AbstractIndexerCommand;
use Exception;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ChangeCommand extends AbstractIndexerCommand
{
    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var array
     */
    protected $errors = array();

    /**
     * @var string
     */
    protected $mode;

    /**
     * @var bool
     */
    protected $track = false;

    /**
     * @var array
     */
    protected $table = null;

    
    protected function configure()
    {
        $this
            ->setName('index:change')
            ->addArgument('indexer', InputArgument::OPTIONAL, 'select indexer by code')
            ->addOption('mode', null, InputOption::VALUE_REQUIRED, 'enable|disable')
            ->addOption('track-time', null, InputOption::VALUE_OPTIONAL, 'track the time all indexers need to be processed')
            ->setDescription('Change magento indexes mode')
        ;

        $help = <<<HELP
Usage:
    n98-magerun index:change [options] -- [arguments]

Arguments:
    indexer                 (optional) select indexer by Code

Options:
    --mode                  (optional) [enable|disable] indexers
    --track-time            (optional) [true|false] specify if script should track processing time

Examples:
    n98-magerun index:change --mode=disable --track-time=true -- catalog_product_attribute
HELP;

        $this->setHelp($help);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function init(InputInterface $input, OutputInterface $output)
    {
        set_time_limit(0);
        
        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }

        $this->input = $input;
        $this->output = $output;

        if($input->getOption('mode') && $input->getOption('mode') == 'enable') {
            $this->mode = \Mage_Index_Model_Process::MODE_REAL_TIME;
        } else {
            $this->mode = \Mage_Index_Model_Process::MODE_MANUAL;
        }

        if($input->getOption('track-time') && $input->getOption('track-time') == 'true') {
            $this->track = true;
        }

    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     *
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);

        $this->disableObservers();

        $this->errors = array();

        if($this->track) {
            $startTime = time();
        }

        if($input->getArgument('indexer') && $input->getArgument('indexer') != '') {
            /** @var \Mage_Index_Model_Process $indexer */
            $indexer = array($this->_getIndexerModel()->getProcessByCode($input->getArgument('indexer')));
        } else {
            /** @var \Mage_Index_Model_Resource_Process_Collection $indexCollection */
            $indexCollection = $this->_getIndexerModel()->getProcessesCollection();
        }

        /** @var \Mage_Index_Model_Process $indexer */
        foreach ($indexCollection as $indexer) {
            $this->changeMode($indexer);
        }

        if($this->track) {
            $output->writeln(
                '<info>Indexer processed after '.(time()-$startTime).' seconds</info>'
            );
        }

        if(!empty($this->errors)) {
            foreach ($this->errors as $code => $msg) {
                $output->writeln('<error>ERROR: '.$code.' => '.$msg.'</error>');
            }
        }
    }

    /**
     * @param \Mage_Index_Model_Process $indexer
     * @return bool
     */
    protected function changeMode($indexer)
    {
        if ($indexer->getId() && $indexer->getIndexer()->isVisible()) {
            $indexer->setMode($this->mode);

            try {
                $indexer->save();
                if($this->mode == \Mage_Index_Model_Process::MODE_REAL_TIME) {
                    $indexer->reindexEverything();
                }
                $this->output->writeln(
                    '<success>Indexer: <comment>' . $indexer->getIndexerCode() . '</comment> - new Mode: <comment>' . $this->mode . '</comment></success>'
                );
                return true;

            } catch (\Mage_Core_Exception $me) {
                $this->errors[$me->getCode()] = '<comment>' . $indexer->getIndexerCode() . '</comment>: '.$me->getMessage();

            } catch (\Exception $e) {
                $this->errors[$e->getCode()] = '<comment>' . $indexer->getIndexerCode() . '</comment>: '.$e->getMessage();
            }   

        } else {
            $output->writeln(
                '<info>Cannot set Indexer: <comment>' . $indexer->getIndexerCode() . '</comment> to <comment>' . $this->mode . '</comment></info>'
            );
        }

        return false;
    }

}