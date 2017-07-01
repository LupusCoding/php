<?php
/**
 * Magento n98-Magerun Conflict Command
 * 
 * Magento Magerun Command to show module conflicts.
 * 
 * @package 		LupusCoding\Magento\Command\System
 * @author          LupusCoding <https://github.com/LupusCoding/>
 * @version         0.1.0
 * @dependency      N98-Magerun Lib (n98-magerun.phar)
 *
 */

namespace LupusCoding\Magento\Command\System;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use N98\Magento\Command\AbstractMagentoCommand;
use Mage;

class ConflictCommand extends AbstractMagentoCommand
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
	 * @var Array 
	 */
	protected $types = array(
		'block', 
		'helper', 
		'model', 
		'controller'
	);

    protected function configure()
    {
        $this->setName('sys:info:conflicts')
        	->addOption('types', null, InputOption::VALUE_OPTIONAL, 'type to check, multiple types have to be comma separated list')
        	// ->addOption('mode', null, InputOption::VALUE_OPTIONAL, 'possible modes: show')
            ->setDescription('Show informations about module conflicts.');

        $help = <<<HELP
Usage:
    n98-magerun index:change [options]

Arguments:
	NONE

Options:
	--types					(optional) single type or comma-separated list of types.
							Do not use quotes ("" or '') or spaces in list.
							Possible values: block, helper, model

Examples:
	n98-magerun index:change --types=block,helper
HELP;

        $this->setHelp($help);
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function init(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $this->detectMagento($output, true);
        if (!$this->initMagento()) {
            return;
        }
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->init($input, $output);
        $this->prepareOptions();
        $this->showConflicts();
	}

	/**
	 * prepare vars by given options
	 *
	 * @return void
	 */
	protected function prepareOptions() {
		// prepare types
		if($this->input->getOption('types')) {
			if(strpos($this->input->getOption('types'), ',')) {
				$this->types = implode(',', $this->input->getOption('types'));
			} else if($this->input->getOption('types') != null && $this->input->getOption('types') != 'null') {
				$this->types = array($this->input->getOption('types'));
			}
		}
	}

	/**
	 * get module conflicts
	 *
	 * @return array
	 */
	protected function getConflicts() {

		$conflicts = array();
		$fixed = array();

		$modules = Mage::getConfig()->getNode('modules')->children();
		/* @var string $type */
		foreach ($this->types as $type) {
			/* @var SimpleXMLElement $modInfo */
		    foreach ($modules as $modName => $modInfo) {
		    	// if module is not active, we dont need to test it
		        if (!$modInfo->is('active')) {
		            continue;
		        }

		        // load config
		        $configFile = Mage::getConfig()->getModuleDir('etc', $modName) . DS . 'config.xml';
		        /* @var Mage_Core_Model_Config_Element $modConfig */
		        $modConfig = Mage::getModel('core/config_base');
		        $modConfig->loadFile($configFile);

		        /* @var Mage_Core_Model_Config_Element $baseConfig */
		        $baseConfig = Mage::getModel('core/config_base');
		        $baseConfig->loadString('<config/>');
		        $baseConfig->extend($modConfig, true);

		        /* @var SimpleXMLElement $node */
		        $node = $baseConfig->getNode()->global->{$type.'s'};
		        if (!$node) {
		            continue;
		        }

		        /* @var SimpleXMLElement $elems */
		        foreach ($node->children() as $target => $elems) {
		    		/* @var SimpleXMLElement $rewrites */
		            $rewrites = $elems->rewrite;

			        if ($rewrites) {
		        		/* @var SimpleXMLElement $config */
			            $config = Mage::getConfig()->getNode()->global->{$type.'s'}->{$target};

					    foreach ($rewrites->children() as $current => $final) {
					        $final = reset($final);
					        $rewritten = (string) $config->rewrite->$current;
					    }

					    if($rewritten == $final) {
					    	// no conflict
					    	continue;
					    } else if(is_subclass_of($rewritten, $final)) {
					    	// conflict found - fixed
					    	$fixed[] = "{$final}";
					    } else {
					    	// conflict found
					    	$conflicts[] = "{$final}";
					    }
			        }
		        }
		    }
		}

		return array('fixed' => $fixed, 'conflicts' => $conflicts);
	}

	/**
	 * show conflicts at command line
	 *
	 * @return void
	 */
	protected function showConflicts() {
		if(!is_array($this->types) && !is_string($this->types) && $this->types != null) {
			return array(
				'error' => 0x1,
				'errorMsg' => 'Wrong type string format.'
			);
		}

		$this->output->writeln('searching for conflicts...');

		$conflicts = $this->getConflicts();
		$conflictCount = count($Conflicts['fixed']) + count($Conflicts['conflicts']);

		$this->output->writeln('total of ' . $conflictCount . ' conflicts found');

		if(count($conflicts['fixed']) > 0) {
			$this->output->writeln('| fixed conflicts found | ' . count($conflicts['fixed']));
			foreach ($Conflicts['fixed'] as $fixed) {
				$this->output->writeln($fixed);
			}
			$this->output->writeln('-----');
		}

		if(count($Conflicts['conflicts']) > 0) {
			$this->output->writeln('| conflicts found | ' . count($Conflicts['conflicts']));
			foreach ($Conflicts['conflicts'] as $conflict) {
				$this->output->writeln($conflict);
			}
			$this->output->writeln('-----');
		}
	}

}
