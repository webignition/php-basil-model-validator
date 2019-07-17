<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModel\DataSet\DataSetCollection;
use webignition\BasilModel\DataSet\DataSetCollectionInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Identifier\IdentifierTypes;
use webignition\BasilModel\IdentifierContainerInterface;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModel\Value\ObjectValueInterface;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModel\ValueContainerInterface;
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
    const REASON_ELEMENT_IDENTIFIER_MISSING = 'step-element-identifier-missing';
    const CONTEXT_VALUE_CONTAINER = 'action';
    const CONTEXT_ELEMENT_IDENTIFIER_NAME = 'element-identifier-name';

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

        // *actions valid
        // *assertions valid
        // *actions with data parameters have data sets
        // *assertions with data parameters have data sets
        // actions with element parameters have element identifiers
        // assertions with element parameters have element identifiers
        // actions have actionable identifiers
        // assertions have assertable identifiers (?)
        // has more than zero assertions



        // actions requiring actionable identifiers
        // relevant action verbs:
        // click
        // set
        // submit
        // wait-for

        foreach ($model->getActions() as $action) {
            $actionValidationResult = $this->actionValidator->validate($action);

            if ($actionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::REASON_ACTION_INVALID, $actionValidationResult);
            }

            if ($action instanceof ValueContainerInterface) {
                $actionDataValidationResult = $this->validateValueContainerDataParameter($model, $action);

                if ($actionDataValidationResult instanceof InvalidResultInterface) {
                    return $actionDataValidationResult;
                }
            }

            if ($action instanceof IdentifierContainerInterface) {
                $identifier = $action->getIdentifier();

                if (IdentifierTypes::ELEMENT_PARAMETER === $identifier->getType()) {
                    $objectValue = $identifier->getValue();

                    if ($objectValue instanceof ObjectValueInterface) {
                        $identifierName = $objectValue->getObjectProperty();
                        $identifier = $model->getIdentifierCollection()->getIdentifier($identifierName);

                        if (!$identifier instanceof IdentifierInterface) {
                            return (new InvalidResult(
                                $model,
                                TypeInterface::STEP,
                                self::REASON_ELEMENT_IDENTIFIER_MISSING
                            ))->withContext([
                                self::CONTEXT_ELEMENT_IDENTIFIER_NAME => $identifierName,
                            ]);
                        }
                    }
                }
            }
        }

        foreach ($model->getAssertions() as $assertion) {
            $assertionValidationResult = $this->assertionValidator->validate($assertion);

            if ($assertionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::REASON_ASSERTION_INVALID, $assertionValidationResult);
            }

            if ($assertion instanceof ValueContainerInterface) {
                $actionDataValidationResult = $this->validateValueContainerDataParameter($model, $assertion);

                if ($actionDataValidationResult instanceof InvalidResultInterface) {
                    return $actionDataValidationResult;
                }
            }
        }

        return new ValidResult($model);
    }

    private function validateValueContainerDataParameter(
        StepInterface $step,
        ValueContainerInterface $valueContainer
    ): ?InvalidResultInterface {
        $value = $valueContainer->getValue();

        if ($value instanceof ObjectValueInterface && ValueTypes::DATA_PARAMETER === $value->getType()) {
            $parameterName = $value->getObjectProperty();
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
