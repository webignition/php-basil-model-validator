<?php
/** @noinspection PhpDocSignatureInspection */

namespace webignition\BasilModelValidator\Tests\Unit;

use webignition\BasilModel\DataSet\DataSet;
use webignition\BasilModel\DataSet\DataSetCollection;
use webignition\BasilModel\DataSet\DataSetCollectionInterface;
use webignition\BasilModelValidator\DataSetCollectionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class DataSetCollectionValidatorTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var DataSetCollectionValidator
     */
    private $dataSetCollectionValidator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dataSetCollectionValidator = DataSetCollectionValidator::create();
    }

    public function testHandles()
    {
        $this->assertTrue($this->dataSetCollectionValidator->handles(new DataSetCollection()));
        $this->assertFalse($this->dataSetCollectionValidator->handles(new \stdClass()));
    }

    public function testValidateWrongModelTypeIsNotValid()
    {
        $model = new \stdClass();
        $expectedResult = InvalidResult::createUnhandledModelResult($model);

        $this->assertEquals($expectedResult, $this->dataSetCollectionValidator->validate($model));
    }

    /**
     * @dataProvider validateNotValidDataProvider
     */
    public function testValidateNotValid(DataSetCollectionInterface $dataSetCollection, int $expectedResultCode)
    {
        $expectedResult = new InvalidResult($dataSetCollection, TypeInterface::DATA_SET, $expectedResultCode);

        $this->assertEquals($expectedResult, $this->dataSetCollectionValidator->validate($dataSetCollection));
    }

    public function validateNotValidDataProvider(): array
    {
        return [
            'key mismatch, one set is empty' => [
                'dataSetCollection' => new DataSetCollection([
                    new DataSet([
                        'username' => 'user',
                        'role' => 'admin',
                    ]),
                    new DataSet([]),
                ]),
                'expectedResultCode' => DataSetCollectionValidator::CODE_KEY_MISMATCH,
            ],
            'key mismatch, keys do not match' => [
                'dataSetCollection' => new DataSetCollection([
                    new DataSet([
                        'username' => 'user',
                        'role' => 'admin',
                    ]),
                    new DataSet([
                        'foo' => 'bar',
                    ]),
                ]),
                'expectedResultCode' => DataSetCollectionValidator::CODE_KEY_MISMATCH,
            ],
        ];
    }

    /**
     * @dataProvider validateIsValidDataProvider
     */
    public function testValidateIsValid(DataSetCollectionInterface $dataSetCollection)
    {
        $expectedResult = new ValidResult($dataSetCollection);

        $this->assertEquals($expectedResult, $this->dataSetCollectionValidator->validate($dataSetCollection));
    }

    public function validateIsValidDataProvider(): array
    {
        return [
            'empty' => [
                'dataset' => new DataSetCollection(),
            ],
            'single data set' => [
                'dataset' => new DataSetCollection([
                    new DataSet([
                        'foo'
                    ]),
                ])
            ],
        ];
    }
}
