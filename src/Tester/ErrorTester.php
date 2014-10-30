<?php

namespace Rmiller\ErrorExtension\Tester;

use Behat\Testwork\Environment\Environment;
use Behat\Testwork\Output\Printer\OutputPrinter;
use Behat\Testwork\Specification\SpecificationIterator;
use Behat\Testwork\Tester\Result\TestResult;
use Behat\Testwork\Tester\Setup\Setup;
use Behat\Testwork\Tester\Setup\Teardown;
use Behat\Testwork\Tester\SuiteTester;
use Symfony\Component\Console\Output\OutputInterface;

class ErrorTester implements SuiteTester
{
    private $baseTester;
    private $observers;
    private $output;

    public function __construct(
        SuiteTester $baseTester,
        OutputInterface $output,
        array $observers = null)
    {
        $this->baseTester = $baseTester;
        $this->output = $output;
        $this->observers = $observers;
    }

    /**
     * Tests provided suite specifications.
     *
     * @param Environment $env
     * @param SpecificationIterator $iterator
     * @param Boolean $skip
     *
     * @return TestResult
     */
    public function test(Environment $env, SpecificationIterator $iterator, $skip)
    {
        return $this->baseTester->test($env, $iterator, $skip);
    }

    /**
     * Tears down suite after a test.
     *
     * @param Environment $env
     * @param SpecificationIterator $iterator
     * @param Boolean $skip
     * @param TestResult $result
     *
     * @return Teardown
     */
    public function tearDown(Environment $env, SpecificationIterator $iterator, $skip, TestResult $result)
    {
        return $this->baseTester->tearDown($env, $iterator, $skip, $result);
    }

    /**
     * Sets up suite for a test.
     *
     * @param Environment $env
     * @param SpecificationIterator $iterator
     * @param Boolean $skip
     *
     * @return Setup
     */
    public function setUp(Environment $env, SpecificationIterator $iterator, $skip)
    {
        $this->turnOffErrorDisplayingIfNotVerbose();
        $this->registerShutdownFunction();

        return $this->baseTester->setUp($env, $iterator, $skip);
    }

    private function turnOffErrorDisplayingIfNotVerbose()
    {
        if ($this->output->getVerbosity() == OutputPrinter::VERBOSITY_NORMAL) {
            error_reporting(E_ALL & ~E_ERROR);
        }
    }

    private function registerShutdownFunction()
    {
        register_shutdown_function(function () {
            if ($error = error_get_last()) {

                foreach ($this->observers as $observer) {
                    $observer->notify($error);
                }

                $this->output->writeln('');
                $this->output->writeln(sprintf(
                    '<error>The error "%s" occurred at line %s in file %s</error>',
                    $error['message'],
                    $error['file'],
                    $error['line']
                ));
                $this->output->writeln('');
                exit;
            }
        });
    }
}