<?php

declare(strict_types=1);

namespace webignition\BasilModelValidator\Test;

use webignition\BasilModel\Test\TestInterface;
use webignition\BasilModelValidator\Result\InvalidResult;
use webignition\BasilModelValidator\Result\InvalidResultInterface;
use webignition\BasilModelValidator\Result\ResultInterface;
use webignition\BasilModelValidator\Result\TypeInterface;
use webignition\BasilModelValidator\Result\ValidResult;
use webignition\BasilModelValidator\StepValidator;

class TestValidator
{
    public const REASON_CONFIGURATION_INVALID = 'test-configuration-invalid';
    public const REASON_NO_STEPS = 'test-no-steps';
    public const REASON_STEP_INVALID = 'test-step-invalid';

    private $configurationValidator;
    private $stepValidator;

    public function __construct(ConfigurationValidator $configurationValidator, StepValidator $stepValidator)
    {
        $this->configurationValidator = $configurationValidator;
        $this->stepValidator = $stepValidator;
    }

    public static function create(): TestValidator
    {
        return new TestValidator(
            ConfigurationValidator::create(),
            StepValidator::create()
        );
    }

    public function validate(TestInterface $test): ResultInterface
    {
        $configurationValidationResult = $this->configurationValidator->validate($test->getConfiguration());
        if ($configurationValidationResult instanceof InvalidResultInterface) {
            return $this->createInvalidResult(
                $test,
                self::REASON_CONFIGURATION_INVALID,
                $configurationValidationResult
            );
        }

        $steps = $test->getSteps();
        if (0 === count($steps)) {
            return $this->createInvalidResult($test, self::REASON_NO_STEPS);
        }

        foreach ($steps as $step) {
            $stepValidationResult = $this->stepValidator->validate($step);

            if ($stepValidationResult instanceof InvalidResultInterface) {
                return $this->createInvalidResult(
                    $test,
                    self::REASON_STEP_INVALID,
                    $stepValidationResult
                );
            }
        }

        return new ValidResult($test);
    }

    private function createInvalidResult(
        object $model,
        string $reason,
        ?InvalidResultInterface $invalidResult = null
    ): ResultInterface {
        return new InvalidResult($model, TypeInterface::TEST, $reason, $invalidResult);
    }
}
