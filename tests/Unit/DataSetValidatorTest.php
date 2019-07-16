<?php
/** @noinspection PhpDocSignatureInspection */

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

    public function testHandles()
    {
        $this->assertTrue($this->dataSetValidator->handles(new DataSet([])));
        $this->assertFalse($this->dataSetValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->dataSetValidator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(
        DataSetInterface $dataSet,
        array $context,
        int $expectedResultCode
    ) {
        $expectedResult = new InvalidResult($dataSet, TypeInterface::DATA_SET, $expectedResultCode);

        $this->assertEquals($expectedResult, $this->dataSetValidator->validate($dataSet, $context));
    }

    public function validateNotValidDataProvider(): array
    {
        return [
            'empty, does not have data parameter name' => [
                'dataSet' => new DataSet([]),
                'context' => [
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'username',
                ],
                'expectedResultCode' => DataSetValidator::CODE_DATA_SET_INCOMPLETE,
            ],
            'single data set, does not have data parameter name' => [
                'dataSet' => new DataSet([
                    'role' => 'user',
                ]),
                'context' => [
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'username',
                ],
                'expectedResultCode' => DataSetValidator::CODE_DATA_SET_INCOMPLETE,
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(DataSetInterface $dataSet, array $context)
    {
        $expectedResult = new ValidResult($dataSet);

        $this->assertEquals($expectedResult, $this->dataSetValidator->validate($dataSet, $context));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'empty, no data parameter name' => [
                'dataSet' => new DataSet([]),
                'context' => [],
            ],
            'single data set, no data parameter name' => [
                'dataSet' => new DataSet([
                    'foo' => 'bar',
                ]),
                'context' => [],
            ],
            'single data set, has data parameter name' => [
                'dataSet' => new DataSet([
                    'username' => 'user1',
                ]),
                'context' => [
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'username',
                ],
            ],
            'single data set, has data parameter name and additional parameter names' => [
                'dataSet' => new DataSet([
                    'username' => 'user1',
                    'role' => 'user',
                ]),
                'context' => [
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => 'username',
                ],
            ],
        ];
    }
}
