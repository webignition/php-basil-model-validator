<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\DataSet\DataSet;
use webignition\BasilModel\DataSet\DataSetInterface;
use webignition\BasilModelValidator\DataSetValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class DataSetValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataSetValidator
     */
    private $dataSetValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataSetValidator = DataSetValidator::create();
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(
        DataSetInterface $dataSet,
        ?string $dataParameterName,
        string $expectedReason
    ) {
        $expectedResult = new InvalidResult($dataSet, TypeInterface::DATA_SET, $expectedReason);

        $this->assertEquals($expectedResult, $this->dataSetValidator->validate($dataSet, $dataParameterName));
    }

    public function validateNotValidDataProvider(): array
    {
        return [
            'empty, does not have data parameter name' => [
                'dataSet' => new DataSet('0', []),
                'dataParameterName' => 'username',
                'expectedReason' => DataSetValidator::REASON_DATA_SET_INCOMPLETE,
            ],
            'single data set, does not have data parameter name' => [
                'dataSet' => new DataSet('0', [
                    'role' => 'user',
                ]),
                'dataParameterName' => 'username',
                'expectedReason' => DataSetValidator::REASON_DATA_SET_INCOMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(DataSetInterface $dataSet, ?string $dataParameterName)
    {
        $expectedResult = new ValidResult($dataSet);

        $this->assertEquals($expectedResult, $this->dataSetValidator->validate($dataSet, $dataParameterName));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'empty, no data parameter name' => [
                'dataSet' => new DataSet('0', []),
                'dataParameterName' => null,
            ],
            'single data set, no data parameter name' => [
                'dataSet' => new DataSet('0', [
                    'foo' => 'bar',
                ]),
                'dataParameterName' => null,
            ],
            'single data set, has data parameter name' => [
                'dataSet' => new DataSet('0', [
                    'username' => 'user1',
                ]),
                'dataParameterName' => 'username',
            ],
            'single data set, has data parameter name and additional parameter names' => [
                'dataSet' => new DataSet('0', [
                    'username' => 'user1',
                    'role' => 'user',
                ]),
                'dataParameterName' => 'username',
            ],
        ];
    }
}
