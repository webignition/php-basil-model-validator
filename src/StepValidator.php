<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModel\Assertion\ComparisonAssertionInterface;
use webignition\BasilModel\Assertion\ExaminationAssertionInterface;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModel\Value\ObjectValue;
use webignition\BasilModel\Value\ObjectValueType;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class StepValidator
{
    public const REASON_ACTION_INVALID = 'step-action-invalid';
    public const REASON_ASSERTION_INVALID = 'step-assertion-invalid';
    public const REASON_DATA_SET_INCOMPLETE = 'step-data-set-incomplete';
    public const REASON_DATA_SET_EMPTY = 'step-data-set-empty';
    public const REASON_NO_ASSERTIONS = 'no-assertions';
    public const CONTEXT_VALUE_CONTAINER = 'value-container';

    private $actionValidator;
    private $assertionValidator;
    private $dataSetValidator;

    public function __construct(
        ActionValidator $actionValidator,
        AssertionValidator $assertionValidator,
        DataSetValidator $dataSetValidator
    ) {
        $this->actionValidator = $actionValidator;
        $this->assertionValidator = $assertionValidator;
        $this->dataSetValidator = $dataSetValidator;
    }

    public static function create(): StepValidator
    {
        return new StepValidator(
            ActionValidator::create(),
            AssertionValidator::create(),
            DataSetValidator::create()
        );
    }

    public function validate(StepInterface $step): ResultInterface
    {
        foreach ($step->getActions() as $action) {
            $actionValidationResult = $this->actionValidator->validate($action);

            if ($actionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($step, self::REASON_ACTION_INVALID, $actionValidationResult);
            }

            if ($action instanceof InputActionInterface) {
                $actionValue = $action->getValue();

                if ($actionValue instanceof ValueInterface) {
                    $dataValueValidationResult = $this->validateDataValue($step, $actionValue, $action);

                    if ($dataValueValidationResult instanceof InvalidResultInterface) {
                        return $dataValueValidationResult;
                    }
                }
            }
        }

        $assertions = $step->getAssertions();
        if (0 === count($assertions)) {
            return $this->createInvalidResult($step, self::REASON_NO_ASSERTIONS);
        }

        foreach ($assertions as $assertion) {
            $assertionValidationResult = $this->assertionValidator->validate($assertion);

            if ($assertionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($step, self::REASON_ASSERTION_INVALID, $assertionValidationResult);
            }

            if ($assertion instanceof ExaminationAssertionInterface) {
                $examinedValue = $assertion->getExaminedValue();

                $examinedValueDataValueValidationResult = $this->validateDataValue($step, $examinedValue, $assertion);
                if ($examinedValueDataValueValidationResult instanceof InvalidResultInterface) {
                    return $examinedValueDataValueValidationResult;
                }
            }

            if ($assertion instanceof ComparisonAssertionInterface) {
                $expectedValue = $assertion->getExpectedValue();

                if ($expectedValue instanceof ValueInterface) {
                    $expectedValueDataParameterValidationResult = $this->validateDataValue(
                        $step,
                        $expectedValue,
                        $assertion
                    );

                    if ($expectedValueDataParameterValidationResult instanceof InvalidResultInterface) {
                        return $expectedValueDataParameterValidationResult;
                    }
                }
            }
        }

        return new ValidResult($step);
    }

    private function validateDataValue(
        StepInterface $step,
        ValueInterface $value,
        $valueContainer
    ): ?InvalidResultInterface {
        if ($value instanceof ObjectValue && ObjectValueType::DATA_PARAMETER === $value->getType()) {
            $parameterName = $value->getProperty();
            $dataSetCollection = $step->getDataSetCollection();

            if (count($dataSetCollection) === 0) {
                return (new InvalidResult(
                    $step,
                    TypeInterface::STEP,
                    self::REASON_DATA_SET_EMPTY
                ))->withContext([
                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => $parameterName,
                    StepValidator::CONTEXT_VALUE_CONTAINER => $valueContainer,
                ]);
            }

            foreach ($dataSetCollection as $dataSet) {
                $dataSetValidationResult = $this->dataSetValidator->validate(
                    $dataSet,
                    [
                        DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => $parameterName,
                    ]
                );

                if ($dataSetValidationResult instanceof InvalidResult) {
                    return new InvalidResult(
                        $step,
                        TypeInterface::STEP,
                        self::REASON_DATA_SET_INCOMPLETE,
                        $dataSetValidationResult->withContext([
                            DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => $parameterName,
                            StepValidator::CONTEXT_VALUE_CONTAINER => $valueContainer,
                        ])
                    );
                }
            }
        }

        return null;
    }

    private function createInvalidResult(
        StepInterface $step,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($step, TypeInterface::STEP, $reason, $previous);
    }
}
