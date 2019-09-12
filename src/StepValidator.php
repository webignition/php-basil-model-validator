<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModel\Value\DataParameter;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\WrappedValueInterface;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class StepValidator implements ValidatorInterface
{
    const REASON_ACTION_INVALID = 'step-action-invalid';
    const REASON_ASSERTION_INVALID = 'step-assertion-invalid';
    const REASON_DATA_SET_INCOMPLETE = 'step-data-set-incomplete';
    const REASON_DATA_SET_EMPTY = 'step-data-set-empty';
    const REASON_NO_ASSERTIONS = 'no-assertions';
    const CONTEXT_VALUE_CONTAINER = 'value-container';

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

    public function handles(object $model): bool
    {
        return $model instanceof StepInterface;
    }

    public function validate(object $model, ?array $context = []): ResultInterface
    {
        if (!$model instanceof StepInterface) {
            return InvalidResult::createUnhandledModelResult($model);
        }

        foreach ($model->getActions() as $action) {
            $actionValidationResult = $this->actionValidator->validate($action);

            if ($actionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::REASON_ACTION_INVALID, $actionValidationResult);
            }

            if ($action instanceof InputActionInterface) {
                $actionValue = $action->getValue();

                if ($actionValue instanceof ValueInterface) {
                    $dataValueValidationResult = $this->validateDataValue($model, $actionValue, $action);

                    if ($dataValueValidationResult instanceof InvalidResultInterface) {
                        return $dataValueValidationResult;
                    }
                }
            }
        }

        $assertions = $model->getAssertions();
        if (0 === count($assertions)) {
            return $this->createInvalidResult($model, self::REASON_NO_ASSERTIONS);
        }

        foreach ($assertions as $assertion) {
            $assertionValidationResult = $this->assertionValidator->validate($assertion);

            if ($assertionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::REASON_ASSERTION_INVALID, $assertionValidationResult);
            }

            $examinedValue = $assertion->getExaminedValue();

            if ($examinedValue instanceof WrappedValueInterface) {
                $examinedValue = $examinedValue->getWrappedValue();
            }

            if ($examinedValue instanceof ValueInterface) {
                $examinedValueDataValueValidationResult = $this->validateDataValue($model, $examinedValue, $assertion);
                if ($examinedValueDataValueValidationResult instanceof InvalidResultInterface) {
                    return $examinedValueDataValueValidationResult;
                }
            }

            $expectedValue = $assertion->getExpectedValue();

            if ($expectedValue instanceof WrappedValueInterface) {
                $expectedValue = $expectedValue->getWrappedValue();
            }

            if ($expectedValue instanceof ValueInterface) {
                $expectedValueDataParameterValidationResult = $this->validateDataValue(
                    $model,
                    $expectedValue,
                    $assertion
                );

                if ($expectedValueDataParameterValidationResult instanceof InvalidResultInterface) {
                    return $expectedValueDataParameterValidationResult;
                }
            }
        }

        return new ValidResult($model);
    }

    private function validateDataValue(
        StepInterface $step,
        ValueInterface $value,
        $valueContainer
    ): ?InvalidResultInterface {
        if ($value instanceof DataParameter) {
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
        object $model,
        string $reason,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($model, TypeInterface::STEP, $reason, $previous);
    }
}
