<?php

namespace webignition\BasilModelValidator;

use webignition\BasilModel\Action\ActionInterface;
use webignition\BasilModel\Action\InputActionInterface;
use webignition\BasilModel\DataSet\DataSetCollectionInterface;
use webignition\BasilModel\Identifier\IdentifierInterface;
use webignition\BasilModel\Step\StepInterface;
use webignition\BasilModel\Value\ObjectValueInterface;
use webignition\BasilModel\Value\ValueInterface;
use webignition\BasilModel\Value\ValueTypes;
use webignition\BasilModelValidator\Action\ActionValidator;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;

class StepValidator implements ValidatorInterface
{
    const CODE_ACTION_INVALID = 1;
    const CODE_ASSERTION_INVALID = 2;
    const CODE_DATA_SET_INCOMPLETE = 3;
    const CODE_DATA_SET_EMPTY = 4;
    const CONTEXT_ACTION = 'action';

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

        // actions valid
        // assertions valid
        // actions with data parameters have data sets
        // assertions with data parameters have data sets
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
                return $this->createInvalidResult($model, self::CODE_ACTION_INVALID, $actionValidationResult);
            }

            if ($action instanceof InputActionInterface) {
                $value = $action->getValue();

                if ($value instanceof ObjectValueInterface && ValueTypes::DATA_PARAMETER === $value->getType()) {
                    $parameterName = $value->getObjectProperty();

                    $dataSetCollection = $model->getDataSetCollection();

                    if (count($dataSetCollection) === 0) {
                        return (new InvalidResult(
                            $model,
                            TypeInterface::STEP,
                            self::CODE_DATA_SET_EMPTY
                        ))->withContext([
                            DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => $parameterName,
                            StepValidator::CONTEXT_ACTION => $action,
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
                                $model,
                                TypeInterface::STEP,
                                self::CODE_DATA_SET_INCOMPLETE,
                                $dataSetValidationResult->withContext([
                                    DataSetValidator::CONTEXT_DATA_PARAMETER_NAME => $parameterName,
                                    StepValidator::CONTEXT_ACTION => $action,
                                ])
                            );
                        }
                    }
                }
            }

            // validate datasetcollection only if the collection is being used
            // ... and only verify that each dataset contains the required keys ... additional keys don't matters

//            $this->findActionsWithDataParameterValues($st)

//            if ($action instanceof InputActionInterface) {
//                $identifier = $action->getIdentifier();
//
//                if ($identifier instanceof IdentifierInterface) {
//                    $identifier->getType();
//                }
//
//                $action->getValue();
//                $action->getIdentifier();
//            }
        }

        foreach ($model->getAssertions() as $assertion) {
            $assertionValidationResult = $this->assertionValidator->validate($assertion);

            if ($assertionValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult($model, self::CODE_ASSERTION_INVALID, $assertionValidationResult);
            }
        }

        return new ValidResult($model);
    }

    private function createInvalidResult(
        object $model,
        int $code,
        ?InvalidResultInterface $previous = null
    ): InvalidResultInterface {
        return new InvalidResult($model, TypeInterface::STEP, $code, $previous);
    }
}
