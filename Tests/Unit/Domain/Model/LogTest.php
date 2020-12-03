<?php
declare(strict_types=1);
namespace WEBprofil\WpFalcleaner\Tests\Unit\Domain\Model;

/**
 * Test case.
 *
 * @author Gernot Ploiner <gp@webprofil.at>
 */
class LogTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @var \WEBprofil\WpFalcleaner\Domain\Model\Log
     */
    protected $subject = null;

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new \WEBprofil\WpFalcleaner\Domain\Model\Log();
    }

    protected function tearDown()
    {
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getFilenameReturnsInitialValueForString()
    {
        self::assertSame(
            '',
            $this->subject->getFilename()
        );
    }

    /**
     * @test
     */
    public function setFilenameForStringSetsFilename()
    {
        $this->subject->setFilename('Conceived at T3CON10');

        self::assertAttributeEquals(
            'Conceived at T3CON10',
            'filename',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function getReasonReturnsInitialValueForInt()
    {
        self::assertSame(
            0,
            $this->subject->getReason()
        );
    }

    /**
     * @test
     */
    public function setReasonForIntSetsReason()
    {
        $this->subject->setReason(12);

        self::assertAttributeEquals(
            12,
            'reason',
            $this->subject
        );
    }
}
